<?php

namespace Boshnik\PbAuth\Social;

use Boshnik\PbAuth\Support\Config;

/**
 * Какие провайдеры есть и как до них добраться.
 *
 * Свой драйвер сайт добавляет ключом в `social.drivers` — писать его можно на
 * любой службе, лишь бы класс отвечал интерфейсу SocialDriver.
 */
class DriverRegistry
{
    protected static array $shipped = [
        'google' => Drivers\GoogleDriver::class,
        'yandex' => Drivers\YandexDriver::class,
        'mailru' => Drivers\MailRuDriver::class,
        'github' => Drivers\GithubDriver::class,
        'facebook' => Drivers\FacebookDriver::class,
        'telegram' => Drivers\TelegramDriver::class,
    ];

    public static function make(string $provider): ?SocialDriver
    {
        $classes = array_merge(static::$shipped, Config::get('social.drivers', []));
        $class = $classes[$provider] ?? null;

        if (!is_string($class) || !class_exists($class) || !is_a($class, SocialDriver::class, true)) {
            return null;
        }

        $driver = new $class(Config::get("social.providers.$provider", []));

        return $driver->isConfigured() ? $driver : null;
    }

    /**
     * Настроенные провайдеры — те, у кого заданы доступы. Ими и рисуются кнопки:
     * показывать вход через службу, которая не заработает, незачем.
     *
     * @return array<string, SocialDriver>
     */
    public static function available(): array
    {
        if (!Config::get('social.enabled', true)) {
            return [];
        }

        $drivers = [];
        foreach (array_keys(array_merge(static::$shipped, Config::get('social.drivers', []))) as $provider) {
            if ($driver = static::make($provider)) {
                $drivers[$provider] = $driver;
            }
        }

        return $drivers;
    }
}
