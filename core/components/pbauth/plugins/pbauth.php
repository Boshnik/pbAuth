<?php
/**
 * pbAuth
 *
 * Раздаёт системные события MODX классам компонента: событию `OnFoo` отвечает
 * `Boshnik\PbAuth\Events\OnFoo`. Нового кода в плагине для нового события не
 * нужно — заведите класс и прикрепите событие к плагину.
 *
 * Лежит вне `elements/`, чтобы установщик не скопировал его в site-owned App/:
 * это код компонента, он должен обновляться вместе с ним.
 *
 * @var \modX $modx
 * @var array $scriptProperties
 */

$className = 'Boshnik\\PbAuth\\Events\\' . $modx->event->name;
if (!class_exists($className)) {
    return;
}

try {
    (new $className($modx, $scriptProperties))->run();
} catch (\Throwable $e) {
    $modx->log(
        \modX::LOG_LEVEL_ERROR,
        '[pbAuth] Ошибка в событии ' . $modx->event->name . ': '
        . get_class($e) . ': ' . $e->getMessage()
        . ' в ' . $e->getFile() . ':' . $e->getLine()
    );
}
