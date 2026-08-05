<?php

namespace Boshnik\PbAuth\Social;

/**
 * Общая часть входа через OAuth2: увести к провайдеру, вернуться с кодом,
 * обменять код на токен, спросить о пользователе.
 *
 * Драйверу остаётся описать четыре адреса и разобрать ответ. Библиотека для
 * этого не нужна: весь протокол — два HTTP-запроса, а composer на SFTP-сервере
 * всё равно не запустить.
 */
abstract class AbstractDriver implements SocialDriver
{
    public function __construct(
        protected array $config = []
    ) {
    }

    abstract protected function authUrl(): string;

    abstract protected function tokenUrl(): string;

    abstract protected function userUrl(): string;

    abstract protected function mapUser(array $response, array $token): SocialUser;

    protected function scope(): string
    {
        return 'email';
    }

    /**
     * Дополнительные параметры адреса авторизации — там, где провайдер требует
     * своего (например, `access_type=offline` у Google).
     */
    protected function authParams(): array
    {
        return [];
    }

    public function isRedirectBased(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['client_id']) && !empty($this->config['client_secret']);
    }

    public function redirectUrl(string $redirectUri, string $state): string
    {
        return $this->authUrl() . '?' . http_build_query(array_merge([
            'client_id' => $this->config['client_id'] ?? '',
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $this->scope(),
            'state' => $state,
        ], $this->authParams()));
    }

    public function user(array $request, string $redirectUri): ?SocialUser
    {
        $code = (string)($request['code'] ?? '');
        if ($code === '') {
            return null;
        }

        $token = $this->exchangeCode($code, $redirectUri);
        if (empty($token['access_token'])) {
            return null;
        }

        $profile = $this->fetchUser($token);
        if ($profile === null) {
            return null;
        }

        return $this->mapUser($profile, $token);
    }

    protected function exchangeCode(string $code, string $redirectUri): array
    {
        return $this->post($this->tokenUrl(), [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->config['client_id'] ?? '',
            'client_secret' => $this->config['client_secret'] ?? '',
            'redirect_uri' => $redirectUri,
        ]);
    }

    protected function fetchUser(array $token): ?array
    {
        return $this->get($this->userUrl(), [
            'Authorization: Bearer ' . $token['access_token'],
        ]);
    }

    protected function post(string $url, array $data, array $headers = []): array
    {
        return $this->request($url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        ]) ?? [];
    }

    protected function get(string $url, array $headers = []): ?array
    {
        return $this->request($url, [
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        ]);
    }

    protected function request(string $url, array $options): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, $options + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            // Проверка сертификата остаётся включённой: через это соединение
            // приходят и токен, и личные данные.
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status >= 400) {
            $this->log("запрос к {$url} не удался: HTTP {$status} {$error} {$body}");

            return null;
        }

        $decoded = json_decode((string)$body, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function log(string $message): void
    {
        if (isset($GLOBALS['modx']) && $GLOBALS['modx'] instanceof \modX) {
            $GLOBALS['modx']->log(\modX::LOG_LEVEL_ERROR, '[pbAuth] ' . static::key() . ': ' . $message);
        }
    }
}
