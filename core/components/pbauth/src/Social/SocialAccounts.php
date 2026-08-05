<?php

namespace Boshnik\PbAuth\Social;

use Boshnik\PbAuth\Models\PbaSocialAccount;
use Boshnik\PbAuth\Support\TwoFactor;

/**
 * Привязки соцсетей и заведение пользователей по ним.
 */
class SocialAccounts
{
    /**
     * Пользователь создан входом через соцсеть и своего пароля не знает.
     *
     * Отмечаем это, чтобы не дать ему отвязать последнюю сеть и остаться совсем
     * без способа войти. Снимается, как только пароль задан осознанно — при
     * смене пароля или восстановлении.
     */
    public static function markPasswordless($user, bool $value = true): void
    {
        $profile = $user ? $user->getOne('Profile') : null;
        if (!$profile) {
            return;
        }

        $extended = $profile->get('extended');
        if (!is_array($extended)) {
            $extended = json_decode((string)$extended, true) ?: [];
        }

        if ($value) {
            $extended[TwoFactor::KEY]['social_only'] = true;
        } else {
            unset($extended[TwoFactor::KEY]['social_only']);
            if (empty($extended[TwoFactor::KEY])) {
                unset($extended[TwoFactor::KEY]);
            }
        }

        $profile->set('extended', $extended);
        $profile->save();
    }

    public static function isPasswordless($user): bool
    {
        $profile = $user ? $user->getOne('Profile') : null;
        if (!$profile) {
            return false;
        }

        $extended = $profile->get('extended');
        if (!is_array($extended)) {
            $extended = json_decode((string)$extended, true) ?: [];
        }

        return !empty($extended[TwoFactor::KEY]['social_only']);
    }

    public static function link(int $userId, string $provider, SocialUser $socialUser): PbaSocialAccount
    {
        $account = PbaSocialAccount::findAccount($provider, $socialUser->id)
            ?: new PbaSocialAccount(['provider' => $provider, 'provider_id' => $socialUser->id]);

        $account->fill([
            'user_id' => $userId,
            'provider' => $provider,
            'provider_id' => $socialUser->id,
            'email' => $socialUser->email,
            'nickname' => $socialUser->nickname,
            'avatar' => $socialUser->avatar,
            'properties' => $socialUser->raw,
        ]);
        $account->save();

        return $account;
    }

    /**
     * Свободное имя пользователя на основе того, что прислал провайдер.
     */
    public static function username(\modX $modx, SocialUser $socialUser, string $provider): string
    {
        $base = $socialUser->nickname ?: strstr($socialUser->email, '@', true) ?: $provider;
        $base = preg_replace('/[^a-zA-Z0-9_.-]/', '', static::translit($base));
        $base = trim(substr($base, 0, 24), '._-');

        if (strlen($base) < 3) {
            $base = $provider . '_user';
        }

        $username = $base;
        $suffix = 0;
        while ($modx->getObject(\modUser::class, ['username' => $username])) {
            $suffix++;
            $username = $base . $suffix;
        }

        return $username;
    }

    /**
     * Имена из соцсетей чаще всего кириллические, а `username` в MODX проверяется
     * на латиницу. Транслитерация нужна только чтобы получить читаемую основу —
     * при совпадении к ней всё равно добавится число.
     */
    protected static function translit(string $value): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'і' => 'i', 'ї' => 'i', 'є' => 'e', 'ґ' => 'g',
        ];

        return strtr(mb_strtolower($value, 'UTF-8'), $map);
    }
}
