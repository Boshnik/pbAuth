<?php

namespace Boshnik\PbAuth\Support;

/**
 * Одноразовые коды по времени — RFC 6238 поверх HOTP (RFC 4226).
 *
 * Написано вручную, без библиотеки: на дев-серверах composer не запустить, а
 * алгоритм — это HMAC-SHA1 плюс base32, и то и другое есть в самом PHP.
 * Совместимо с Google Authenticator, 1Password, Authy и любым другим
 * приложением, понимающим ссылку `otpauth://`.
 */
class Totp
{
    public const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const PERIOD = 30;
    public const DIGITS = 6;

    /**
     * Секрет в base32. 20 байт — длина ключа HMAC-SHA1, её же рекомендует RFC 4226.
     */
    public static function generateSecret(int $bytes = 20): string
    {
        return static::base32Encode(random_bytes($bytes));
    }

    /**
     * Номер интервала, в котором находится момент времени.
     */
    public static function counter(?int $timestamp = null, int $period = self::PERIOD): int
    {
        return intdiv($timestamp ?? time(), $period);
    }

    public static function codeAt(string $secret, int $counter, int $digits = self::DIGITS): string
    {
        $key = static::base32Decode($secret);
        if ($key === '') {
            return '';
        }

        $hash = hash_hmac('sha1', pack('J', $counter), $key, true);

        // Динамическая усечка: младший полубайт последнего байта указывает, с
        // какого места брать четыре байта результата (RFC 4226, §5.3).
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string)($binary % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Проверяет код и возвращает номер интервала, которому он подошёл, либо null.
     *
     * Соседние интервалы принимаются из-за расхождения часов и того, что человек
     * набирает код не мгновенно. Номер возвращается, чтобы вызывающий мог
     * запретить повторное использование того же кода.
     */
    public static function verify(
        string $secret,
        string $code,
        int $window = 1,
        ?int $timestamp = null,
        int $digits = self::DIGITS,
        int $period = self::PERIOD
    ): ?int {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== $digits) {
            return null;
        }

        $current = static::counter($timestamp, $period);

        for ($shift = -$window; $shift <= $window; $shift++) {
            $counter = $current + $shift;
            // hash_equals, а не ==: сравнение с ранним выходом выдаёт по времени
            // ответа, сколько первых цифр угаданы.
            if (hash_equals(static::codeAt($secret, $counter, $digits), $code)) {
                return $counter;
            }
        }

        return null;
    }

    /**
     * Ссылка для приложения-аутентификатора. На телефоне открывает его напрямую,
     * на компьютере из неё рисуют QR-код.
     */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);

        return 'otpauth://totp/' . $label . '?' . http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => static::DIGITS,
            'period' => static::PERIOD,
        ]);
    }

    /**
     * Секрет группами по четыре знака — так его реально ввести руками, если
     * приложение не умеет сканировать.
     */
    public static function readable(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    public static function base32Encode(string $bytes): string
    {
        if ($bytes === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= static::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    public static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret));
        if ($secret === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin(strpos(static::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            // Хвост короче байта — это добивка последнего символа, не данные.
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }
}
