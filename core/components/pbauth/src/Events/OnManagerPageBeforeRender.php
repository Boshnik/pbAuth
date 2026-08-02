<?php

namespace Boshnik\PbAuth\Events;

use Boshnik\PbAuth\Support\Config;

/**
 * Подключает в менеджере кнопку «Авторизоваться на сайте».
 *
 * Скрипт сам проверяет, что нужные классы ExtJS на странице есть, поэтому
 * загрузить его лишний раз безопасно. Но когда действие страницы известно, мы
 * всё же сужаемся до раздела пользователей, чтобы не тянуть файл впустую.
 */
class OnManagerPageBeforeRender extends Event
{
    public function run(): void
    {
        if (!Config::get('impersonate_enabled', true)) {
            return;
        }

        $action = (string)($_GET['a'] ?? '');
        if ($action !== '' && strncmp($action, 'security/user', 13) !== 0) {
            return;
        }

        $assetsUrl = $this->modx->getOption('assets_url', null, '/assets/');
        // Метка по времени файла: после деплоя браузер сам возьмёт новую версию,
        // а помнить про ручное поднятие номера не нужно.
        $file = MODX_ASSETS_PATH . 'components/pbauth/js/mgr/impersonate.js';
        $version = is_file($file) ? filemtime($file) : 1;

        $this->modx->lexicon->load('pbauth:default');

        $this->modx->regClientStartupHTMLBlock(
            '<script>Ext.ns("pbAuth");pbAuth.lang='
            . json_encode(
                ['impersonate' => $this->modx->lexicon('pbauth_impersonate')],
                JSON_UNESCAPED_UNICODE
            )
            . ';</script>'
        );

        $this->modx->regClientStartupScript(
            $assetsUrl . "components/pbauth/js/mgr/impersonate.js?v={$version}"
        );
    }
}
