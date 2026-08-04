<?php

namespace Boshnik\PbAuth\Support;

/**
 * Состояние двухфакторной проверки у пользователя.
 *
 * Хранится в `modx_user_attributes.extended` — своей таблицы компоненту для
 * этого не нужно. Всё лежит под ключом `pbauth`, чтобы не столкнуться с полями,
 * которые сайт кладёт в `extended` сам.
 */
class TwoFactor
{
    public const KEY = 'pbauth';

    public static function state($user): array
    {
        $profile = static::profile($user);
        if (!$profile) {
            return [];
        }

        $extended = $profile->get('extended');
        if (!is_array($extended)) {
            $extended = json_decode((string)$extended, true) ?: [];
        }

        return $extended[static::KEY]['totp'] ?? [];
    }

    public static function isEnabled($user): bool
    {
        $state = static::state($user);

        return !empty($state['enabled']) && !empty($state['secret']);
    }

    /**
     * Включает проверку и выдаёт резервные коды — единственный раз, когда они
     * видны открытым текстом.
     *
     * @return string[]
     */
    public static function enable($user, string $secret, int $backupCount = 8): array
    {
        $codes = static::generateBackupCodes($backupCount);

        static::write($user, [
            'secret' => $secret,
            'enabled' => true,
            'confirmed_at' => time(),
            'last_counter' => 0,
            'backup' => array_map([static::class, 'hashCode'], $codes),
        ]);

        return $codes;
    }

    public static function disable($user): void
    {
        static::write($user, null);
    }

    /**
     * @return string[] новые коды
     */
    public static function regenerateBackupCodes($user, int $count = 8): array
    {
        $state = static::state($user);
        if (!$state) {
            return [];
        }

        $codes = static::generateBackupCodes($count);
        $state['backup'] = array_map([static::class, 'hashCode'], $codes);
        static::write($user, $state);

        return $codes;
    }

    /**
     * Принимает код из приложения либо резервный код.
     *
     * Оба одноразовые: подошедший интервал запоминается, а использованный
     * резервный код вычёркивается. Без этого подсмотренный код работал бы ещё
     * полминуты, а резервный — вечно.
     */
    public static function verify($user, string $code, int $window = 1): bool
    {
        $state = static::state($user);
        if (empty($state['secret'])) {
            return false;
        }

        $counter = Totp::verify($state['secret'], $code, $window);
        if ($counter !== null) {
            if ($counter <= (int)($state['last_counter'] ?? 0)) {
                return false;
            }

            $state['last_counter'] = $counter;
            static::write($user, $state);

            return true;
        }

        return static::consumeBackupCode($user, $code, $state);
    }

    public static function backupCodesLeft($user): int
    {
        return count(static::state($user)['backup'] ?? []);
    }

    protected static function consumeBackupCode($user, string $code, array $state): bool
    {
        $hash = static::hashCode($code);
        $remaining = [];
        $used = false;

        foreach ($state['backup'] ?? [] as $stored) {
            if (!$used && hash_equals($stored, $hash)) {
                $used = true;
                continue;
            }
            $remaining[] = $stored;
        }

        if (!$used) {
            return false;
        }

        $state['backup'] = $remaining;
        static::write($user, $state);

        return true;
    }

    /**
     * @return string[]
     */
    protected static function generateBackupCodes(int $count): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Десять знаков из алфавита base32 — полсотни бит, подобрать нельзя.
            // В нём нет нуля и единицы, поэтому переписанные с экрана на бумагу
            // O и I ни с чем не спутать.
            $code = '';
            for ($j = 0; $j < 10; $j++) {
                $code .= Totp::ALPHABET[random_int(0, strlen(Totp::ALPHABET) - 1)];
            }
            $codes[] = substr($code, 0, 5) . '-' . substr($code, 5);
        }

        return $codes;
    }

    /**
     * Резервный код — случайные полсотни бит, перебрать его нельзя, поэтому
     * медленный хеш не нужен. Наоборот, вреден: восемь bcrypt на каждую попытку
     * дали бы дешёвый способ нагрузить сервер.
     */
    protected static function hashCode(string $code): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $code));

        return hash('sha256', $normalized);
    }

    protected static function write($user, ?array $state): void
    {
        $profile = static::profile($user);
        if (!$profile) {
            return;
        }

        $extended = $profile->get('extended');
        if (!is_array($extended)) {
            $extended = json_decode((string)$extended, true) ?: [];
        }

        if ($state === null) {
            unset($extended[static::KEY]['totp']);
            if (empty($extended[static::KEY])) {
                unset($extended[static::KEY]);
            }
        } else {
            $extended[static::KEY]['totp'] = $state;
        }

        $profile->set('extended', $extended);
        $profile->save();
    }

    protected static function profile($user)
    {
        return $user ? $user->getOne('Profile') : null;
    }
}
