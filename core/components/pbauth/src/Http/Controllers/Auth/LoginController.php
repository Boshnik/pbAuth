<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Support\Config;
use Boshnik\PbAuth\Support\SingleSession;
use Boshnik\PbAuth\Support\TwoFactor;

class LoginController extends AuthController
{
    public function show()
    {
        return $this->page('auth', 'login', [
            'title' => lang('auth.login_title'),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate(Config::rules('login'));

        $user = $this->modx->getObject($this->userClassKey, ['username' => $request->username]);
        if (!$user) {
            $profile = $this->modx->getObject($this->profileClassKey, ['email' => $request->username])
                ?: $this->findByPhone($request->username);

            if (!$profile || !$user = $profile->getOne('User')) {
                return response()->error('', [
                    'username' => $this->modx->lexicon('user_err_nf'),
                ]);
            }

            $request->username = $user->username;
        }

        if (Config::get('two_factor_enabled', true) && TwoFactor::isEnabled($user)) {
            return $this->startTwoFactorChallenge($user, $request);
        }

        $response = $this->modx->runProcessor($this->getProccesorPath('login'), [
            'username' => $request->username,
            'password' => $request->password,
            'rememberme' => $request->remember ?? false,
            'login_context' => $this->modx->context->key ?? 'web',
            'add_contexts' => implode(',', $this->getContexts()),
        ]);

        if ($response->isError()) {
            return $this->getProcessorError($response);
        }

        // Обычный вход идёт через процессор MODX, а он про наш учёт сессий не
        // знает — закрепляем сами.
        SingleSession::claim($user);

        Dispatcher::fire(Dispatcher::AFTER_LOGIN, ['user' => $user]);

        return response()->success('', $this->loginRedirect($request));
    }

    /**
     * Телефоны в базе лежат одними цифрами, а вводят их как угодно: со знаком
     * плюс, пробелами, скобками. Сравниваем нормализованное с нормализованным,
     * иначе «+373 60 41 34 13» не находит собственную запись `37360413413`.
     *
     * Короткий ввод по телефону не ищем вовсе: среди легаси-записей есть обрывки
     * вроде `998` и одиночные пробелы, и по ним нашёлся бы чужой профиль.
     */
    protected function findByPhone(?string $value)
    {
        $digits = preg_replace('/\D+/', '', (string)$value);

        if (strlen((string)$digits) < 7) {
            return null;
        }

        return $this->modx->getObject($this->profileClassKey, ['phone' => $digits]);
    }

    /**
     * Пароль верный, но нужен ещё код: сессию не открываем.
     *
     * Процессор входа MODX здесь не годится — он сразу логинит. Поэтому пароль
     * сверяем сами и проверяем то же, что проверил бы он: активен ли аккаунт и
     * не заблокирован ли. В сессии остаётся только id и срок ожидания; ни пароля,
     * ни открытого контекста до верного кода.
     */
    protected function startTwoFactorChallenge($user, Request $request)
    {
        if (!$user->passwordMatches($request->password)) {
            return response()->error('', [
                'password' => $this->modx->lexicon('user_err_password'),
            ]);
        }

        if (!$user->get('active')) {
            return response()->error('', [
                'username' => $this->modx->lexicon('user_err_not_activated'),
            ]);
        }

        $profile = $user->getOne('Profile');
        $blockedUntil = (int)($profile ? $profile->get('blockeduntil') : 0);
        if ($profile && ($profile->get('blocked') || ($blockedUntil && $blockedUntil > time()))) {
            return response()->error('', [
                'username' => $this->modx->lexicon('login_blocked'),
            ]);
        }

        $_SESSION[TwoFactorController::PENDING] = [
            'id' => $user->get('id'),
            'expires' => time() + (int)Config::get('two_factor_challenge_ttl', 300),
            'attempts' => 0,
            'redirect' => $this->loginRedirect($request),
        ];

        return response()->success('', route('pageTwoFactorChallenge'));
    }

    public function logout()
    {
        $user = $this->modx->user;
        $this->modx->runProcessor($this->getProccesorPath('logout'));

        Dispatcher::fire(Dispatcher::AFTER_LOGOUT, ['user' => $user]);

        return redirect($this->redirectTo('logout'));
    }

    /**
     * Форма может попросить вернуть пользователя туда, откуда его завернули на
     * вход. Принимается только путь внутри сайта — со схемой, хостом или
     * протокол-относительным `//host` форма стала бы открытым редиректом.
     */
    protected function loginRedirect(Request $request): string
    {
        $default = $this->redirectTo('login');
        $param = Config::get('login_redirect_param');

        if (empty($param)) {
            return $default;
        }

        $target = trim((string)$request->get($param, ''));
        if ($target === '' || str_starts_with($target, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target)) {
            return $default;
        }

        return '/' . ltrim($target, '/');
    }
}
