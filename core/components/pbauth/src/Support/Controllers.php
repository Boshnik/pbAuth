<?php

namespace Boshnik\PbAuth\Support;

/**
 * Мост между роутами компонента и классом, который их обслуживает.
 *
 * Роутер PageBlocks клеит к строке вида `'Auth\LoginController@show'` префикс
 * `PageBlocks\App\Http\Controllers\`, поэтому контроллер из компонента таким
 * синтаксисом не адресуется — используется форма `[класс, метод]`. Заодно здесь
 * применяется подмена класса из `controllers` в конфиге: сайт наследует
 * поставочный контроллер, переопределяет пару методов и называет свой класс.
 */
class Controllers
{
    public static function resolve(string $key): string
    {
        $class = Config::get("controllers.$key");

        if (!is_string($class) || $class === '') {
            throw new \RuntimeException("[pbAuth] Не задан контроллер для '$key'.");
        }

        // Существование класса намеренно не проверяется: опечатку в конфиге
        // лучше увидеть ошибкой роутера, чем тихо получить чужой контроллер.
        return $class;
    }

    /**
     * @return array{0: string, 1: string} пара для Route::get()/post()
     */
    public static function action(string $key, string $method): array
    {
        return [static::resolve($key), $method];
    }
}
