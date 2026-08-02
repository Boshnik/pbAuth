<?php

namespace Boshnik\PbAuth\Events;

/**
 * Базовый класс для обработчиков системных событий MODX.
 *
 * Плагин `pbauth` ищет класс `Boshnik\PbAuth\Events\<ИмяСобытия>` и, если нашёл,
 * зовёт его `run()`. Чтобы добавить обработку нового события, достаточно завести
 * такой класс и прикрепить событие к плагину.
 */
abstract class Event
{
    public function __construct(
        protected \modX $modx,
        protected array $properties = []
    ) {
    }

    abstract public function run(): void;

    protected function property(string $key, $default = null)
    {
        return $this->properties[$key] ?? $default;
    }
}
