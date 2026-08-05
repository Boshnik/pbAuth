<?php

namespace Boshnik\PbAuth\Social\Drivers;

use Boshnik\PbAuth\Social\AbstractDriver;
use Boshnik\PbAuth\Social\SocialUser;

class GithubDriver extends AbstractDriver
{
    public static function key(): string
    {
        return 'github';
    }

    protected function authUrl(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function userUrl(): string
    {
        return 'https://api.github.com/user';
    }

    protected function scope(): string
    {
        return 'read:user user:email';
    }

    protected function fetchUser(array $token): ?array
    {
        $headers = [
            'Authorization: Bearer ' . $token['access_token'],
            // GitHub отказывает запросам без User-Agent.
            'User-Agent: pbAuth',
        ];

        $user = $this->get($this->userUrl(), $headers);
        if ($user === null) {
            return null;
        }

        // Профиль отдаёт почту, только если она публичная. Остальные лежат
        // отдельным списком - оттуда берём основную и подтверждённую.
        if (empty($user['email'])) {
            foreach ($this->get('https://api.github.com/user/emails', $headers) ?? [] as $item) {
                if (!empty($item['primary']) && !empty($item['verified'])) {
                    $user['email'] = $item['email'];
                    $user['email_verified'] = true;
                    break;
                }
            }
        }

        return $user;
    }

    protected function mapUser(array $response, array $token): SocialUser
    {
        return new SocialUser(
            id: (string)($response['id'] ?? ''),
            email: (string)($response['email'] ?? ''),
            // Публичная почта из профиля тоже подтверждена: GitHub не даёт
            // выставить напоказ непроверенный адрес.
            emailVerified: !empty($response['email']),
            nickname: (string)($response['name'] ?: $response['login'] ?? ''),
            avatar: (string)($response['avatar_url'] ?? ''),
            raw: $response
        );
    }
}
