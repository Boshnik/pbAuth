<?php

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__);
    while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}

return [
    'name' => 'pbAuth',
    'name_lower' => 'pbauth',
    'version' => '1.1.1',
    'release' => 'pl',
    // Install package to site right after build.
    // Stays false in the repository: the build finds its MODX by walking up from
    // _build, so a checkout inside a site tree would install into that site
    // without asking. Switch it on locally when that is what you want.
    'install' => false,
    // Which elements should be updated on package upgrade
    'update' => [
        'settings' => false,
        // Off on purpose, as in PageBlocks and pbShop. The symlinks_after
        // resolver replaces Extras/pbAuth/core|assets with links into the
        // installed component, and after that an edit in the build tree is an
        // edit of the live one. A staging site is there to show what a buyer
        // sees, and that only happens when the component arrives by package
        // install rather than by link. Need the links locally — switch it on in
        // your own copy; the resolver no longer deletes a real directory at any
        // value of this flag, and that is what actually protects the sources.
        'symlinks' => false,
    ],
    // Which elements should be static by default
    'static' => [],
    // Log settings
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => php_sapi_name() == 'cli' ? 'ECHO' : 'HTML',
    // Download transport.zip after build
    'download' => !empty($_REQUEST['download']),
];
