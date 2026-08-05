<?php

namespace Boshnik\PbAuth\Social\Drivers;

use Boshnik\PbAuth\Social\AbstractDriver;
use Boshnik\PbAuth\Social\SocialUser;

class GoogleDriver extends AbstractDriver
{
    public static function key(): string
    {
        return 'google';
    }

    protected function authUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function tokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function userUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v3/userinfo';
    }

    protected function scope(): string
    {
        return 'openid email profile';
    }

    protected function mapUser(array $response, array $token): SocialUser
    {
        return new SocialUser(
            id: (string)($response['sub'] ?? ''),
            email: (string)($response['email'] ?? ''),
            // Google говорит прямо, проверял ли он адрес.
            emailVerified: !empty($response['email_verified']),
            nickname: (string)($response['name'] ?? ''),
            avatar: (string)($response['picture'] ?? ''),
            raw: $response
        );
    }
}
