<?php

namespace Boshnik\PbAuth\Social\Drivers;

use Boshnik\PbAuth\Social\AbstractDriver;
use Boshnik\PbAuth\Social\SocialUser;

class MailRuDriver extends AbstractDriver
{
    public static function key(): string
    {
        return 'mailru';
    }

    protected function authUrl(): string
    {
        return 'https://oauth.mail.ru/login';
    }

    protected function tokenUrl(): string
    {
        return 'https://oauth.mail.ru/token';
    }

    protected function userUrl(): string
    {
        return 'https://oauth.mail.ru/userinfo';
    }

    protected function scope(): string
    {
        return 'userinfo';
    }

    protected function fetchUser(array $token): ?array
    {
        return $this->get($this->userUrl() . '?access_token=' . urlencode($token['access_token']));
    }

    protected function mapUser(array $response, array $token): SocialUser
    {
        return new SocialUser(
            id: (string)($response['id'] ?? ''),
            email: (string)($response['email'] ?? ''),
            emailVerified: !empty($response['email']),
            nickname: trim(($response['first_name'] ?? '') . ' ' . ($response['last_name'] ?? '')),
            avatar: (string)($response['image'] ?? ''),
            raw: $response
        );
    }
}
