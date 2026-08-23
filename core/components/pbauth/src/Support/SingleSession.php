<?php

namespace Boshnik\PbAuth\Support;

/**
 * Один вход на учётную запись: новый вход выкидывает предыдущий.
 *
 * Зачем: логин и пароль от платного аккаунта иначе передают по кругу, и вместо
 * десяти подписок сайт получает одну на десятерых.
 *
 * Как: при входе запоминаем идентификатор сессии, а на каждом запросе сверяем
 * его с текущим. Не совпал — значит этой сессии уже нашлась замена, и её
 * закрываем. Никаких попыток дотянуться до чужой сессии и убить её на месте:
 * хранилище сессий бывает разное (файлы, база, redis), а так работает везде.
 */
class SingleSession
{
    /** Сессия, которую выкидывать нельзя (менеджер зашёл под пользователем). */
    public const EXEMPT = 'pbauth.session_exempt';

    /** Флаг для страницы входа: «вас выкинуло, потому что вошли в другом месте». */
    public const KICKED = 'pbauth.session_kicked';

    protected const FIELD = 'session';

    public static function enabled(): bool
    {
        return (bool)Config::get('single_session', false);
    }

    /**
     * Закрепляет учётную запись за текущей сессией.
     */
    public static function claim($user): void
    {
        if (!static::enabled() || !$user || empty($user->id) || static::isExempt()) {
            return;
        }

        static::write($user, session_id());
    }

    /**
     * Помечает сессию неприкосновенной. Нужно для входа менеджера под чужой
     * учётной записью: посмотреть сайт глазами пользователя — не повод
     * выставлять самого пользователя за дверь.
     */
    public static function exempt(): void
    {
        $_SESSION[static::EXEMPT] = true;
    }

    public static function isExempt(): bool
    {
        return !empty($_SESSION[static::EXEMPT]);
    }

    /**
     * Проверка на каждом запросе.
     */
    public static function check(\modX $modx): void
    {
        if (!static::enabled() || static::isExempt()) {
            return;
        }

        $context = $modx->context->key ?? 'web';
        // Менеджер живёт своей жизнью: вход в панель не должен закрывать
        // пользователю сайт и наоборот.
        if ($context === 'mgr') {
            return;
        }

        $user = $modx->user ?? null;
        if (!$user || empty($user->id) || !$user->isAuthenticated($context)) {
            return;
        }

        $claimed = static::read($user);
        // Пусто — значит вход был ещё до включения проверки. Не выкидываем, а
        // тихо закрепляем: иначе включение настройки разом выбросило бы всех.
        if ($claimed === '') {
            static::write($user, session_id());

            return;
        }

        if ($claimed === session_id()) {
            return;
        }

        static::kick($modx, $user);
    }

    protected static function kick(\modX $modx, $user): void
    {
        foreach (array_keys($_SESSION['modx.user.contextTokens'] ?? []) as $context) {
            if ($context === 'mgr') {
                continue;
            }

            $user->removeSessionContext($context);
            // isAuthenticated() смотрит ещё и сюда, а removeSessionContext
            // этот ключ не трогает.
            unset($_SESSION[$context . 'Validated']);
        }

        $_SESSION[static::KICKED] = true;
    }

    protected static function read($user): string
    {
        $extended = static::extended($user);

        return (string)($extended[TwoFactor::KEY][static::FIELD] ?? '');
    }

    protected static function write($user, string $sessionId): void
    {
        $profile = $user->getOne('Profile');
        if (!$profile) {
            return;
        }

        $extended = static::extended($user);
        $extended[TwoFactor::KEY][static::FIELD] = $sessionId;

        $profile->set('extended', $extended);
        $profile->save();
    }

    protected static function extended($user): array
    {
        $profile = $user->getOne('Profile');
        if (!$profile) {
            return [];
        }

        $extended = $profile->get('extended');
        if (!is_array($extended)) {
            $extended = json_decode((string)$extended, true) ?: [];
        }

        return $extended;
    }
}
