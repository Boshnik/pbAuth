<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Models\PbaSocialAccount;
use Boshnik\PbAuth\Social\DriverRegistry;
use Boshnik\PbAuth\Social\SocialAccounts;
use Boshnik\PbAuth\Social\SocialUser;
use Boshnik\PbAuth\Support\Config;
use Boshnik\PbAuth\Support\TwoFactor;

/**
 * Вход и регистрация через сторонние службы.
 *
 * Главное правило здесь — **связывание только по подтверждённой почте**. Если
 * провайдер не ручается, что адрес принадлежит вошедшему, совпадение почты
 * ничего не доказывает: достаточно завести у такого провайдера аккаунт с чужим
 * адресом, чтобы забрать чужой профиль на сайте. В этом случае человека просят
 * войти паролем и привязать сеть уже изнутри профиля.
 */
class SocialController extends AuthController
{
    public const STATE = 'pbauth.social';
    public const PENDING = 'pbauth.social_pending';

    /**
     * Уводим к провайдеру.
     */
    public function redirect(string $provider, Request $request)
    {
        $driver = $this->driver($provider);

        $state = bin2hex(random_bytes(16));
        $_SESSION[static::STATE] = [
            'state' => $state,
            'provider' => $provider,
            // Привязка к уже открытому аккаунту или вход — от этого зависит,
            // что делать с найденным профилем.
            'intent' => $this->modx->user && $this->modx->user->id ? 'link' : 'login',
            'redirect' => $this->safeRedirect($request->get('redirect', '')),
            'expires' => time() + 600,
        ];

        $url = $driver->redirectUrl($this->callbackUrl($provider), $state);

        // Провайдер без перенаправления (Telegram) начинается с виджета на
        // странице входа — уводить некуда.
        if ($url === '') {
            return redirect(route('pageLogin'));
        }

        return redirect($url);
    }

    /**
     * Вернулись от провайдера.
     */
    public function callback(string $provider, Request $request)
    {
        $driver = $this->driver($provider);
        $params = $request->all();

        $pending = $_SESSION[static::STATE] ?? null;
        unset($_SESSION[static::STATE]);

        // У провайдеров с перенаправлением проверяем метку: без неё чужой сайт
        // мог бы подсунуть свой код авторизации и привязать свой аккаунт к
        // сессии нашего пользователя. Telegram метки не использует — там
        // подлинность даёт подпись самих данных.
        if ($driver->isRedirectBased()) {
            if (!is_array($pending)
                || ($pending['expires'] ?? 0) < time()
                || ($pending['provider'] ?? '') !== $provider
                || !hash_equals((string)($pending['state'] ?? ''), (string)($params['state'] ?? ''))
            ) {
                return $this->fail('auth.social_state_error');
            }
        }

        $socialUser = $driver->user($params, $this->callbackUrl($provider));
        if (!$socialUser || $socialUser->id === '') {
            return $this->fail('auth.social_failed');
        }

        $intent = $pending['intent'] ?? 'login';
        $redirect = $pending['redirect'] ?? '';

        return $this->resolve($provider, $socialUser, $intent, $redirect);
    }

    /**
     * Кого пускать и куда.
     */
    protected function resolve(string $provider, SocialUser $socialUser, string $intent, string $redirect)
    {
        $current = $this->modx->user && $this->modx->user->id ? $this->modx->user : null;
        $account = PbaSocialAccount::findAccount($provider, $socialUser->id);

        // Аккаунт уже за кем-то закреплён.
        if ($account) {
            if ($current && (int)$account->user_id !== (int)$current->id) {
                return $this->fail('auth.social_taken');
            }

            $user = $current ?: $this->modx->getObject($this->userClassKey, (int)$account->user_id);
            if (!$user) {
                return $this->fail('auth.social_failed');
            }

            SocialAccounts::link((int)$user->id, $provider, $socialUser);

            return $current ? $this->done('auth.social_linked', $redirect) : $this->signIn($user, $redirect);
        }

        // Привязка изнутри профиля — самый простой случай, кто вошёл, к тому и цепляем.
        if ($current) {
            SocialAccounts::link((int)$current->id, $provider, $socialUser);
            Dispatcher::fire(Dispatcher::SOCIAL_LINKED, ['user' => $current, 'provider' => $provider]);

            return $this->done('auth.social_linked', $redirect);
        }

        // Почта есть и провайдер за неё ручается — можно узнать существующий аккаунт.
        if ($socialUser->hasEmail()) {
            $profile = $this->modx->getObject($this->profileClassKey, ['email' => $socialUser->email]);

            if ($profile && $user = $profile->getOne('User')) {
                if (!$socialUser->emailVerified) {
                    // Совпадение есть, но доказательства нет.
                    return $this->fail('auth.social_email_unverified');
                }

                SocialAccounts::link((int)$user->id, $provider, $socialUser);
                Dispatcher::fire(Dispatcher::SOCIAL_LINKED, ['user' => $user, 'provider' => $provider]);

                return $this->signIn($user, $redirect);
            }

            if ($socialUser->emailVerified) {
                return $this->register($provider, $socialUser, $redirect, true);
            }
        }

        // Почты нет вовсе (Telegram) либо она не подтверждена.
        if (Config::get('social.require_email', true)) {
            $_SESSION[static::PENDING] = [
                'provider' => $provider,
                'user' => (array)$socialUser,
                'redirect' => $redirect,
                'expires' => time() + 900,
            ];

            return redirect(route('pageSocialEmail'));
        }

        return $this->register($provider, $socialUser, $redirect, false);
    }

    /* ------------------------------------------------------------------ */
    /* Дозапрос почты, когда провайдер её не дал                           */
    /* ------------------------------------------------------------------ */

    public function emailForm()
    {
        if (!$this->pendingRegistration()) {
            return redirect(route('pageLogin'));
        }

        return $this->page('auth', 'social_email', [
            'title' => lang('auth.social_email_title'),
        ]);
    }

    public function saveEmail(Request $request)
    {
        $pending = $this->pendingRegistration();
        if (!$pending) {
            return response()->error(lang('auth.social_expired'), [], 419);
        }

        $validated = $request->validate(Config::rules('social_email'));

        unset($_SESSION[static::PENDING]);

        $saved = $pending['user'];
        $socialUser = new SocialUser(
            id: (string)($saved['id'] ?? ''),
            // Адрес набран руками, провайдер за него не ручается.
            email: $validated['email'],
            emailVerified: false,
            nickname: (string)($saved['nickname'] ?? ''),
            avatar: (string)($saved['avatar'] ?? ''),
            raw: (array)($saved['raw'] ?? [])
        );

        return $this->register($pending['provider'], $socialUser, $pending['redirect'] ?? '', false);
    }

    /* ------------------------------------------------------------------ */
    /* Отвязка                                                             */
    /* ------------------------------------------------------------------ */

    public function unlink(string $provider)
    {
        $user = $this->modx->user;
        $accounts = PbaSocialAccount::forUser((int)$user->id);

        $account = $accounts->firstWhere('provider', $provider);
        if (!$account) {
            return response()->error(lang('auth.social_not_linked'));
        }

        // У пользователя, заведённого соцсетью, своего пароля нет. Отвязав
        // последнюю сеть, он останется вообще без способа войти.
        if ($accounts->count() === 1 && SocialAccounts::isPasswordless($user)) {
            return response()->error(lang('auth.social_last_account'));
        }

        $account->delete();

        Dispatcher::fire(Dispatcher::SOCIAL_UNLINKED, ['user' => $user, 'provider' => $provider]);

        return response()->success(lang('auth.social_unlinked'), route('pageProfile'));
    }

    /* ------------------------------------------------------------------ */

    /**
     * Заводит нового пользователя по данным провайдера.
     *
     * Аккаунт сразу активен только если почту подтвердил провайдер. Набранный
     * руками адрес проверяется обычным письмом — иначе через соцсеть можно было
     * бы занять чужой адрес.
     */
    protected function register(string $provider, SocialUser $socialUser, string $redirect, bool $emailVerified)
    {
        $username = SocialAccounts::username($this->modx, $socialUser, $provider);
        $token = bin2hex(random_bytes(32));

        $user = $this->modx->newObject($this->userClassKey);
        $user->fromArray([
            'username' => $username,
            'class_key' => $user->class_key,
            'active' => $emailVerified ? 1 : 0,
            'remote_key' => $emailVerified ? null : $token,
            // Пароль случайный: пользователь его не знает и знать не должен,
            // входит он через соцсеть. Задать свой сможет восстановлением.
            'password' => bin2hex(random_bytes(16)),
        ]);

        $profile = $this->modx->newObject($this->profileClassKey);
        $profile->fromArray([
            'email' => $socialUser->email,
            'fullname' => $socialUser->nickname,
            'photo' => $socialUser->avatar,
        ]);
        $user->addOne($profile, 'Profile');

        Dispatcher::fire(Dispatcher::USER_SAVING, [
            'user' => $user,
            'profile' => $profile,
            'validated' => ['email' => $socialUser->email, 'fullname' => $socialUser->nickname],
            'action' => 'social',
        ]);

        if (!$user->save()) {
            return $this->fail('auth.register_error');
        }

        SocialAccounts::link((int)$user->id, $provider, $socialUser);
        SocialAccounts::markPasswordless($user);
        $this->joinGroups($user);

        Dispatcher::fire(Dispatcher::AFTER_REGISTER, [
            'user' => $user,
            'profile' => $profile,
            'validated' => [],
            'provider' => $provider,
        ]);
        Dispatcher::fire(Dispatcher::SOCIAL_LINKED, ['user' => $user, 'provider' => $provider]);

        if (!$emailVerified && $socialUser->hasEmail()) {
            $this->sendNotificationEmail([
                'username' => $username,
                'email' => $socialUser->email,
                'token' => $token,
            ]);

            return $this->done('auth.social_confirm_email', route('pageLogin'));
        }

        return $this->signIn($user, $redirect);
    }

    protected function joinGroups($user): void
    {
        foreach (Config::get('user_groups', []) as $group) {
            $user->joinGroup($group);
        }
    }

    /**
     * Второй фактор соцсеть не отменяет: она доказывает владение аккаунтом у
     * провайдера, а не то, что телефон на месте.
     */
    protected function signIn($user, string $redirect)
    {
        if (Config::get('two_factor_enabled', true) && TwoFactor::isEnabled($user)) {
            $_SESSION[TwoFactorController::PENDING] = [
                'id' => $user->get('id'),
                'expires' => time() + (int)Config::get('two_factor_challenge_ttl', 300),
                'attempts' => 0,
                'redirect' => $redirect ?: $this->redirectTo('login'),
            ];

            return redirect(route('pageTwoFactorChallenge'));
        }

        $this->authenticate($user);

        Dispatcher::fire(Dispatcher::AFTER_LOGIN, ['user' => $user, 'social' => true]);

        return redirect($redirect ?: $this->redirectTo('login'));
    }

    protected function driver(string $provider)
    {
        if (!Config::get('social.enabled', true)) {
            abort(404);
        }

        $driver = DriverRegistry::make($provider);
        if (!$driver) {
            abort(404);
        }

        return $driver;
    }

    protected function callbackUrl(string $provider): string
    {
        return rtrim(MODX_SITE_URL, '/') . '/auth/' . $provider . '/callback';
    }

    protected function pendingRegistration(): ?array
    {
        $pending = $_SESSION[static::PENDING] ?? null;

        if (!is_array($pending) || ($pending['expires'] ?? 0) < time()) {
            unset($_SESSION[static::PENDING]);

            return null;
        }

        return $pending;
    }

    protected function safeRedirect(?string $target): string
    {
        $target = trim((string)$target);

        if ($target === '' || str_starts_with($target, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target)) {
            return '';
        }

        return '/' . ltrim($target, '/');
    }

    /**
     * Провайдер возвращает пользователя переходом по ссылке, а не запросом из
     * скрипта, поэтому и на успех, и на ошибку отвечаем перенаправлением с
     * сообщением, а не JSON.
     */
    protected function fail(string $key)
    {
        $_SESSION['pbauth.social_message'] = lang($key);

        return redirect(route('pageLogin'));
    }

    protected function done(string $key, string $redirect)
    {
        $_SESSION['pbauth.social_message'] = lang($key);

        return redirect($redirect ?: route('pageProfile'));
    }
}
