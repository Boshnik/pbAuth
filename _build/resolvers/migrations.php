<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

if (!$transport->xpdo) {
    return false;
}

$modx =& $transport->xpdo;
$action = $options[xPDOTransport::PACKAGE_ACTION];

// Phinx itself comes from PageBlocks: pbAuth declares no vendor of its own.
$phinxBin    = MODX_CORE_PATH . 'components/pageblocks/vendor/bin/phinx';
$phinxConfig = MODX_CORE_PATH . 'components/pbauth/src/phinx.php';

if (!file_exists($phinxBin)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[pbAuth] Phinx binary not found: ' . $phinxBin . '. Is PageBlocks installed?');
    return false;
}

// `php` в PATH есть не на каждом хостинге, а под php-fpm PHP_BINARY указывает на
// сам fpm, которым CLI-скрипт не запустить. Рядом с ним почти всегда лежит
// обычный php, поэтому сначала ищем там и только потом надеемся на PATH.
$phpBin = 'php';
if (defined('PHP_BINDIR') && is_executable(PHP_BINDIR . '/php')) {
    $phpBin = PHP_BINDIR . '/php';
}

/**
 * Прогон phinx с проверкой кода выхода.
 *
 * `exec`, а не `shell_exec`, и это не стилистика: второй кода выхода не
 * возвращает вовсе, и упавшая миграция, ненайденный `php` и запрещённая хостером
 * функция приходили в одну точку с успешным прогоном. Текст ошибки при этом
 * писался в журнал на уровне INFO, где его никто не искал, а резолвер отдавал
 * `true`. Приём взят из PageBlocks и pbFavorites; pbAuth закрывает эту дыру
 * последним из пакетов.
 *
 * Цена молчания здесь - вход соцсетью: без `pba_social_accounts` привязка
 * падает на первом же запросе, и выглядит это как «сайт не пускает через
 * Google», а не как непроставленная миграция.
 *
 * `return false` ниже - честный ответ, но на исход установки он не влияет:
 * `xPDOVehicle::resolve()` перезаписывает результат каждым следующим резолвером
 * (`$resolved = include(...)`, не `&&`), а `xPDOObjectVehicle::install()`
 * возвращает `$saved || $exists` независимо от резолверов вообще. Поэтому
 * единственный доходящий до человека сигнал - ERROR в журнале: консоль установки
 * печатает его красным. Своих страниц в менеджере у пакета нет, так что баннера,
 * как у PageBlocks и pbShop, здесь негде показать.
 */
$runPhinx = static function (string $title, string $command) use ($modx): bool {
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (!function_exists('exec') || in_array('exec', $disabled, true)) {
        $modx->log(
            modX::LOG_LEVEL_ERROR,
            '[pbAuth] ' . $title . ': функция exec() отключена на этом хостинге, '
            . 'таблица привязок соцсетей не создана. Примените миграции вручную: php '
            . 'core/components/pageblocks/vendor/bin/phinx migrate '
            . '--configuration=core/components/pbauth/src/phinx.php --environment=production'
        );
        return false;
    }

    $lines = [];
    $code  = 0;
    exec($command . ' 2>&1', $lines, $code);
    $output = trim(implode("\n", $lines));

    if ($code !== 0) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[pbAuth] ' . $title . ' завершился с кодом ' . $code . ': ' . $output);
        return false;
    }

    $modx->log(modX::LOG_LEVEL_INFO, '[pbAuth] ' . $title . ': ' . $output);

    return true;
};

switch ($action) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $command = sprintf(
            '%s %s migrate --configuration=%s --environment=production',
            escapeshellarg($phpBin),
            escapeshellarg($phinxBin),
            escapeshellarg($phinxConfig)
        );
        if (!$runPhinx('Phinx migrate', $command)) {
            return false;
        }
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        $command = sprintf(
            '%s %s rollback --configuration=%s --environment=production --target=0',
            escapeshellarg($phpBin),
            escapeshellarg($phinxBin),
            escapeshellarg($phinxConfig)
        );
        if (!$runPhinx('Phinx rollback', $command)) {
            return false;
        }
        break;
}

return true;
