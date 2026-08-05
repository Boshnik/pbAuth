<?php

namespace Boshnik\PbAuth\Social\Drivers;

use Boshnik\PbAuth\Social\AbstractDriver;
use Boshnik\PbAuth\Social\SocialUser;

/**
 * Telegram Login Widget — единственный здесь провайдер не по OAuth2.
 *
 * Никуда не уводит: кнопку рисует скрипт Telegram прямо на странице, а после
 * подтверждения возвращает пользователя на наш адрес с полями и подписью.
 * Подпись — HMAC-SHA256 по токену бота; она и есть доказательство, что данные
 * пришли от Telegram, а не набраны в адресной строке.
 *
 * Почту Telegram не отдаёт никогда.
 */
class TelegramDriver extends AbstractDriver
{
    /** Данные считаются свежими сутки — столько же, сколько у самого Telegram. */
    protected const MAX_AGE = 86400;

    public static function key(): string
    {
        return 'telegram';
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['bot_token']) && !empty($this->config['bot_name']);
    }

    public function isRedirectBased(): bool
    {
        return false;
    }

    public function redirectUrl(string $redirectUri, string $state): string
    {
        // Уводить некуда: вход начинается с виджета на странице.
        return '';
    }

    public function user(array $request, string $redirectUri): ?SocialUser
    {
        $hash = (string)($request['hash'] ?? '');
        if ($hash === '' || empty($request['id'])) {
            return null;
        }

        if (!$this->checkSignature($request, $hash)) {
            $this->log('подпись не сошлась, данные отброшены');

            return null;
        }

        $authDate = (int)($request['auth_date'] ?? 0);
        if ($authDate <= 0 || $authDate < time() - static::MAX_AGE) {
            // Иначе однажды подсмотренную ссылку можно было бы переиграть когда
            // угодно: подпись у неё остаётся верной навсегда.
            $this->log('данные устарели, вход отклонён');

            return null;
        }

        $name = trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? ''));

        return new SocialUser(
            id: (string)$request['id'],
            nickname: $name ?: (string)($request['username'] ?? ''),
            avatar: (string)($request['photo_url'] ?? ''),
            raw: $request
        );
    }

    /**
     * Строка проверки — все поля, кроме подписи, отсортированные по имени и
     * склеенные переводом строки. Ключ — SHA-256 от токена бота.
     */
    protected function checkSignature(array $request, string $hash): bool
    {
        $fields = $request;
        unset($fields['hash']);
        // Служебное, что мог добавить роутер или сам сайт: в подпись Telegram
        // это не входило.
        unset($fields['provider'], $fields['state'], $fields['q']);
        ksort($fields);

        $pairs = [];
        foreach ($fields as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        $secret = hash('sha256', (string)$this->config['bot_token'], true);
        $expected = hash_hmac('sha256', implode("\n", $pairs), $secret);

        return hash_equals($expected, $hash);
    }

    protected function authUrl(): string
    {
        return '';
    }

    protected function tokenUrl(): string
    {
        return '';
    }

    protected function userUrl(): string
    {
        return '';
    }

    protected function mapUser(array $response, array $token): SocialUser
    {
        return new SocialUser(id: (string)($response['id'] ?? ''));
    }
}
