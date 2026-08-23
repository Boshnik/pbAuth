<?php

namespace Boshnik\PbAuth\Events;

use Boshnik\PbAuth\Support\SingleSession;

/**
 * Проверка «один вход на учётную запись» — на каждом запросе к сайту.
 *
 * Событие то же, на котором PageBlocks разбирает маршруты, поэтому плагину
 * pbAuth задан приоритет пораньше: сессию надо закрыть до того, как страница
 * начнёт рисоваться под вошедшим пользователем.
 */
class OnHandleRequest extends Event
{
    public function run(): void
    {
        SingleSession::check($this->modx);
    }
}
