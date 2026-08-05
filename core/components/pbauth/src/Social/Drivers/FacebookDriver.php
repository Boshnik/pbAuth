<?php

namespace Boshnik\PbAuth\Social\Drivers;

use Boshnik\PbAuth\Social\AbstractDriver;
use Boshnik\PbAuth\Social\SocialUser;

class FacebookDriver extends AbstractDriver
{
    protected const VERSION = 'v21.0';

    public static function key(): string
    {
        return 'facebook';
    }

    protected function authUrl(): string
    {
        return 'https://www.facebook.com/' . static::VERSION . '/dialog/oauth';
    }

    protected function tokenUrl(): string
    {
        return 'https://graph.facebook.com/' . static::VERSION . '/oauth/access_token';
    }

    protected function userUrl(): string
    {
        return 'https://graph.facebook.com/' . static::VERSION . '/me';
    }

    protected function fetchUser(array $token): ?array
    {
        return $this->get($this->userUrl() . '?' . http_build_query([
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $token['access_token'],
        ]));
    }

    protected function mapUser(array $response, array $token): SocialUser
    {
        return new SocialUser(
            id: (string)($response['id'] ?? ''),
            // Почта приходит не всегда: разрешение email выдаётся приложению
            // только после ревью, да и пользователь может его снять.
            email: (string)($response['email'] ?? ''),
            emailVerified: !empty($response['email']),
            nickname: (string)($response['name'] ?? ''),
            avatar: (string)($response['picture']['data']['url'] ?? ''),
            raw: $response
        );
    }
}
