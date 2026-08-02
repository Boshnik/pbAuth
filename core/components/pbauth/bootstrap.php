<?php
/** @var MODX\Revolution\modX $modx */

use Boshnik\PageBlocks\Routing\Route;

$pbAuthPath = MODX_CORE_PATH . 'components/pbauth/';

// PageBlocks несёт все общие зависимости. Подгружаем его сами, а не полагаемся
// на порядок, в котором MODX обходит неймспейсы.
$pageBlocksAutoload = MODX_CORE_PATH . 'components/pageblocks/vendor/autoload.php';
if (!class_exists(Route::class) && file_exists($pageBlocksAutoload)) {
    require_once $pageBlocksAutoload;
}

if (!class_exists(Route::class)) {
    $modx->log(\modX::LOG_LEVEL_ERROR, '[pbAuth] PageBlocks не найден, компонент отключён.');
    return;
}

$autoloadPath = $pbAuthPath . 'vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // На дев-сервере composer не запустить (только SFTP), поэтому компонент
    // должен грузиться и без сгенерированного автозагрузчика.
    spl_autoload_register(static function (string $class) use ($pbAuthPath): void {
        $prefix = 'Boshnik\\PbAuth\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $pbAuthPath . 'src/' . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// Роуты сайта главнее. Копия auth.php, оставшаяся в App/ от прежних версий (или
// правленая под сайт), продолжает работать вместе со своими контроллерами в
// App/Http/Controllers/Auth/ — компонент в этом случае не подаёт ничего, иначе
// те же URI зарегистрировались бы дважды.
if (file_exists(MODX_CORE_PATH . 'App/routes/auth.php')) {
    $modx->log(
        \modX::LOG_LEVEL_INFO,
        '[pbAuth] Используются роуты сайта из App/routes/auth.php. Удалите этот файл, чтобы перейти на роуты и контроллеры компонента.'
    );
} else {
    Route::addRoutesPath($pbAuthPath . 'routes', 'web');
}
