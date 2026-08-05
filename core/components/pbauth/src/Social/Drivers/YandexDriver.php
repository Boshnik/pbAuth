<?php

namespace Boshnik\PbAuth\Social\Drivers;

use Boshnik\PbAuth\Social\AbstractDriver;
use Boshnik\PbAuth\Social\SocialUser;

class YandexDriver extends AbstractDriver
{
    public static function key(): string
    {
        return 'yandex';
    }

    protected function authUrl(): string
    {
        return 'https://oauth.yandex.ru/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://oauth.yandex.ru/token';
    }

    protected function userUrl(): string
    {
        return 'https://login.yandex.ru/info?format=json';
    }

    protected function scope(): string
    {
        return 'login:email login:info login:avatar';
    }

    protected function fetchUser(array $token): ?array
    {
        // Яндекс ждёт свою схему авторизации, не Bearer.
        return $this->get($this->userUrl(), [
            'Authorization: OAuth ' . $token['access_token'],
        ]);
    }

    protected function mapUser(array $response, array $token): SocialUser
    {
        $avatarId = (string)($response['default_avatar_id'] ?? '');

        return new SocialUser(
            id: (string)($response['id'] ?? ''),
            email: (string)($response['default_email'] ?? ''),
            // Отдельного признака нет, но почта у Яндекса своя же, доменная.
            emailVerified: !empty($response['default_email']),
            nickname: (string)($response['real_name'] ?: $response['display_name'] ?? ''),
            avatar: $avatarId ? "https://avatars.yandex.net/get-yapic/{$avatarId}/islands-200" : '',
            raw: $response
        );
    }
}
