<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Support\Config;
use Boshnik\PbAuth\Support\Totp;
use Boshnik\PbAuth\Support\TwoFactor;

/**
 * Двухфакторная проверка: и шаг ввода кода при входе, и подключение в профиле.
 *
 * Ключевое здесь — что до ввода кода пользователь **не залогинен**. В сессии
 * лежит только его id и срок годности этого ожидания; сессия контекста
 * открывается лишь после верного кода.
 */
class TwoFactorController extends AuthController
{
    public const PENDING = 'pbauth.2fa';
    public const SETUP = 'pbauth.2fa_setup';
    public const CODES = 'pbauth.2fa_codes';

    /* ------------------------------------------------------------------ */
    /* Шаг входа                                                           */
    /* ------------------------------------------------------------------ */

    public function show()
    {
        if (!$this->pending()) {
            return redirect(route('pageLogin'));
        }

        return $this->page('auth', 'two_factor_challenge', [
            'title' => lang('auth.two_factor_title'),
        ]);
    }

    public function verify(Request $request)
    {
        $pending = $this->pending();
        if (!$pending) {
            return response()->error(lang('auth.two_factor_expired'), [], 419);
        }

        $request->validate(Config::rules('two_factor_challenge'));

        $maxAttempts = (int)Config::get('two_factor_attempts', 5);
        if ($maxAttempts > 0 && $pending['attempts'] >= $maxAttempts) {
            $this->forget(static::PENDING);

            return response()->error(lang('auth.two_factor_too_many'), [], 429);
        }

        $user = $this->modx->getObject($this->userClassKey, (int)$pending['id']);
        if (!$user) {
            $this->forget(static::PENDING);

            return response()->error(lang('auth.two_factor_expired'), [], 419);
        }

        if (!TwoFactor::verify($user, $request->code, (int)Config::get('two_factor_window', 1))) {
            $_SESSION[static::PENDING]['attempts'] = $pending['attempts'] + 1;

            return response()->error('', [
                'code' => lang('auth.two_factor_invalid'),
            ]);
        }

        $this->forget(static::PENDING);
        $this->authenticate($user);

        Dispatcher::fire(Dispatcher::AFTER_LOGIN, ['user' => $user, 'two_factor' => true]);

        return response()->success('', $pending['redirect'] ?: $this->redirectTo('login'));
    }

    /* ------------------------------------------------------------------ */
    /* Подключение в профиле                                               */
    /* ------------------------------------------------------------------ */

    public function settings()
    {
        $this->guardEnabled();

        $user = $this->modx->user;
        $enabled = TwoFactor::isEnabled($user);

        $data = [
            'title' => lang('auth.two_factor_settings_title'),
            'two_factor_on' => $enabled,
            'backup_codes_left' => $enabled ? TwoFactor::backupCodesLeft($user) : 0,
            // Показываются один раз, сразу после включения или перевыпуска.
            'backup_codes' => $this->pull(static::CODES) ?: [],
        ];

        if (!$enabled) {
            $secret = $this->startSetup();
            $data['secret'] = $secret;
            $data['secret_readable'] = Totp::readable($secret);
            $data['otpauth_uri'] = Totp::uri(
                $secret,
                $user->getOne('Profile')->get('email') ?: $user->get('username'),
                $this->issuer()
            );
        }

        return $this->page('profile', 'two_factor', $data);
    }

    public function enable(Request $request)
    {
        $this->guardEnabled();

        $request->validate(Config::rules('two_factor_enable'));

        $user = $this->modx->user;
        if (TwoFactor::isEnabled($user)) {
            return response()->error(lang('auth.two_factor_already_enabled'));
        }

        $setup = $_SESSION[static::SETUP] ?? null;
        if (!is_array($setup) || ($setup['expires'] ?? 0) < time()) {
            $this->forget(static::SETUP);

            return response()->error(lang('auth.two_factor_setup_expired'));
        }

        // Проверяем код до включения: иначе можно запереть себя, сохранив
        // секрет, который в приложение так и не попал.
        if (Totp::verify($setup['secret'], $request->code, (int)Config::get('two_factor_window', 1)) === null) {
            return response()->error('', [
                'code' => lang('auth.two_factor_invalid'),
            ]);
        }

        $codes = TwoFactor::enable($user, $setup['secret'], (int)Config::get('two_factor_backup_codes', 8));
        $this->forget(static::SETUP);
        $_SESSION[static::CODES] = $codes;

        Dispatcher::fire(Dispatcher::TWO_FACTOR_ENABLED, ['user' => $user]);

        return response()
            ->append(['backup_codes' => $codes])
            ->success(lang('auth.two_factor_enabled_success'), route('pageTwoFactorSettings'));
    }

    public function disable(Request $request)
    {
        $request->validate(Config::rules('two_factor_disable'));

        $user = $this->modx->user;
        if (!$this->passwordMatches($user, $request->password)) {
            return response()->error('', [
                'password' => lang('auth.old_password_error'),
            ]);
        }

        TwoFactor::disable($user);

        Dispatcher::fire(Dispatcher::TWO_FACTOR_DISABLED, ['user' => $user]);

        return response()->success(
            lang('auth.two_factor_disabled_success'),
            route('pageTwoFactorSettings')
        );
    }

    public function backupCodes(Request $request)
    {
        $request->validate(Config::rules('two_factor_disable'));

        $user = $this->modx->user;
        if (!TwoFactor::isEnabled($user)) {
            return response()->error(lang('auth.two_factor_not_enabled'));
        }

        if (!$this->passwordMatches($user, $request->password)) {
            return response()->error('', [
                'password' => lang('auth.old_password_error'),
            ]);
        }

        $codes = TwoFactor::regenerateBackupCodes($user, (int)Config::get('two_factor_backup_codes', 8));
        $_SESSION[static::CODES] = $codes;

        return response()
            ->append(['backup_codes' => $codes])
            ->success(lang('auth.two_factor_codes_regenerated'), route('pageTwoFactorSettings'));
    }

    /* ------------------------------------------------------------------ */

    /**
     * Общий выключатель. Выключенный второй фактор перестаёт требоваться при
     * входе и исчезает из профиля, но сохранённые секреты остаются — так сайт,
     * запертый снаружи, снова открывается одной строкой в конфиге.
     */
    protected function guardEnabled(): void
    {
        if (!Config::get('two_factor_enabled', true)) {
            abort(404);
        }
    }

    /**
     * Ожидание ввода кода, если оно ещё живо.
     */
    protected function pending(): ?array
    {
        $pending = $_SESSION[static::PENDING] ?? null;

        if (!is_array($pending) || ($pending['expires'] ?? 0) < time()) {
            $this->forget(static::PENDING);

            return null;
        }

        $pending['attempts'] = (int)($pending['attempts'] ?? 0);

        return $pending;
    }

    /**
     * Секрет живёт в сессии, а не в профиле, пока пользователь не доказал, что
     * завёл его в приложении.
     */
    protected function startSetup(): string
    {
        $setup = $_SESSION[static::SETUP] ?? null;

        if (is_array($setup) && ($setup['expires'] ?? 0) >= time() && !empty($setup['secret'])) {
            return $setup['secret'];
        }

        $secret = Totp::generateSecret();
        $_SESSION[static::SETUP] = [
            'secret' => $secret,
            'expires' => time() + 900,
        ];

        return $secret;
    }

    protected function issuer(): string
    {
        return (string)(Config::get('two_factor_issuer')
            ?: $this->modx->getOption('site_name', null, 'MODX'));
    }

    protected function passwordMatches($user, ?string $password): bool
    {
        return !empty($password) && $user->passwordMatches($password);
    }

    protected function pull(string $key)
    {
        $value = $_SESSION[$key] ?? null;
        $this->forget($key);

        return $value;
    }

    protected function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
