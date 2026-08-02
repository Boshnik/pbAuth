<?php

namespace Boshnik\PbAuth\Events;

use Boshnik\PbAuth\Support\Config;

/**
 * Точки, куда сайт вешает свою логику вокруг регистрации и профиля.
 *
 * Системные события MODX как основной механизм не используются: их приходится
 * руками прикреплять в менеджере после каждого деплоя, а на SFTP-сервере этот
 * шаг молча забывается. Слушатели — обычные классы, перечисленные в
 * `App/config/pbauth.php`. `invokeEvent()` вызывается дополнительно, чтобы
 * сторонние плагины тоже могли слушать.
 */
class Dispatcher
{
    /** Перед сохранением пользователя: и при регистрации, и при правке профиля. */
    public const USER_SAVING = 'pbAuthUserSaving';

    public const AFTER_REGISTER = 'pbAuthAfterRegister';
    public const AFTER_LOGIN = 'pbAuthAfterLogin';
    public const AFTER_LOGOUT = 'pbAuthAfterLogout';
    public const AFTER_PROFILE_UPDATE = 'pbAuthAfterProfileUpdate';
    public const AFTER_VERIFY_EMAIL = 'pbAuthAfterVerifyEmail';
    public const AFTER_RESET_PASSWORD = 'pbAuthAfterResetPassword';
    public const AFTER_CHANGE_PASSWORD = 'pbAuthAfterChangePassword';

    protected static ?self $instance = null;

    protected array $listeners = [];
    protected bool $loaded = false;

    public static function instance(): self
    {
        return static::$instance ??= new static();
    }

    public static function listen(string $event, $listener): void
    {
        static::instance()->listeners[$event][] = $listener;
    }

    public static function fire(string $event, array $params = []): array
    {
        return static::instance()->dispatch($event, $params);
    }

    public function dispatch(string $event, array $params = []): array
    {
        $this->loadListeners();

        $results = [];
        foreach ($this->listeners[$event] ?? [] as $listener) {
            try {
                $results[] = $this->call($listener, $event, $params);
            } catch (\Throwable $e) {
                // Сломанный слушатель не должен ронять регистрацию: пользователь
                // уже создан, а письмо ему ещё не ушло.
                $this->log("[pbAuth] Ошибка слушателя на {$event}: " . $e->getMessage());
            }
        }

        if (isset($GLOBALS['modx']) && $GLOBALS['modx'] instanceof \modX) {
            $GLOBALS['modx']->invokeEvent($event, $this->scalarParams($params));
        }

        return $results;
    }

    /**
     * Плагинам MODX уходят только скаляры: объект modUser в $scriptProperties
     * плагину бесполезен и ломает кэширование событий.
     */
    protected function scalarParams(array $params): array
    {
        $scalar = [];
        foreach ($params as $key => $value) {
            if (is_object($value)) {
                $scalar[$key . '_id'] = $value->id ?? 0;
                continue;
            }
            if (is_array($value)) {
                continue;
            }
            $scalar[$key] = $value;
        }

        return $scalar;
    }

    protected function call($listener, string $event, array $params)
    {
        if (is_string($listener)) {
            if (!class_exists($listener)) {
                $this->log("[pbAuth] Класс слушателя не найден: {$listener}");
                return null;
            }
            $listener = new $listener();
        }

        if (is_object($listener) && !$listener instanceof \Closure && method_exists($listener, 'handle')) {
            return $listener->handle($params, $event);
        }

        return $listener($params, $event);
    }

    protected function loadListeners(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        foreach (Config::get('listeners', []) as $event => $listeners) {
            foreach ((array)$listeners as $listener) {
                $this->listeners[$event][] = $listener;
            }
        }
    }

    protected function log(string $message): void
    {
        if (isset($GLOBALS['modx']) && $GLOBALS['modx'] instanceof \modX) {
            $GLOBALS['modx']->log(\modX::LOG_LEVEL_ERROR, $message);
        }
    }
}
