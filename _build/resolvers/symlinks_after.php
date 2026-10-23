<?php
/** @var xPDOTransport $transport */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    $base = MODX_BASE_PATH . 'Extras/pbAuth/';

    $config = ['update' => ['symlinks' => false]];
    if (file_exists($base . '_build/config.inc.php')) {
        $config = include($base . '_build/config.inc.php');
    }

    if (!$config['update']['symlinks'] || !file_exists($base)) {
        return true;
    }

    $files = [
        'assets' => [
            'link'   => $base . 'assets/components/pbauth',
            'target' => MODX_ASSETS_PATH . 'components/pbauth/',
        ],
        'core' => [
            'link'   => $base . 'core/components/pbauth',
            'target' => MODX_CORE_PATH . 'components/pbauth/',
        ],
    ];

    foreach ($files as $file) {
        // Ссылка на месте - восстанавливать нечего. Отметки о прежнем состоянии
        // между symlinks_before и symlinks_after не нужно: is_link() на самом
        // пути отвечает на тот же вопрос.
        //
        // Спрашивается раньше file_exists(), и это не вкусовщина: у БИТОЙ
        // ссылки (цели ещё нет) file_exists() отвечает «нет», а symlink() в
        // этом случае не падает - он идёт ПО ссылке и создаёт новую по её цели,
        // отвечая успехом. Проверено на PHP 8.3.6: путь остаётся битой ссылкой,
        // а ссылка появляется там, куда она указывала, - то есть внутри
        // установленного компонента, саму на себя. Прежний код в этой ситуации
        // ещё и писал в лог «Symlink restored».
        if (is_link($file['link'])) {
            continue;
        }

        // Настоящий каталог здесь - исходники, а не след файлового резолвера.
        // Удалять его нельзя ни при каком значении флага: ровно так и собирают
        // пакет - копией чекаута на сайте, - и установка на тот же сайт сносила
        // бы рабочее дерево. Для dev-чекаута, где по этому пути лежит ссылка,
        // ничего не меняется: та ветка выше.
        if (file_exists($file['link'])) {
            $modx->log(
                modX::LOG_LEVEL_WARN,
                '[pbAuth] По пути ' . $file['link'] . ' лежит настоящий каталог, '
                . 'а не симлинк: оставлен как есть, ссылка не создана. Так выглядит '
                . 'обычный чекаут, и удалять его резолвер не станет. Нужны симлинки '
                . 'на этом сайте - снимите каталог сами, потом переустановите пакет.'
            );
            continue;
        }

        // Создаём симлинк обратно
        symlink($file['target'], $file['link']);
        $modx->log(modX::LOG_LEVEL_INFO, 'Symlink restored: ' . $file['link'] . ' → ' . $file['target']);
    }
}
return true;