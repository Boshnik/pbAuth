<?php

namespace Boshnik\PbAuth\Events;

use Boshnik\PbAuth\Support\Config;

/**
 * Подключает в менеджере кнопки на пользователя: «Авторизоваться на сайте» и
 * «Посмотреть на сайте».
 *
 * Скрипт сам проверяет, что нужные классы ExtJS на странице есть, поэтому
 * загрузить его лишний раз безопасно. Но когда действие страницы известно, мы
 * всё же сужаемся до раздела пользователей, чтобы не тянуть файл впустую.
 */
class OnManagerPageBeforeRender extends Event
{
    public function run(): void
    {
        $impersonate = (bool)Config::get('impersonate_enabled', true);
        $userPage = trim((string)$this->modx->getOption('pbauth_user_page', null, ''));

        // Обе возможности выключены — грузить нечего.
        if (!$impersonate && $userPage === '') {
            return;
        }

        $action = (string)($_GET['a'] ?? '');
        if ($action !== '' && strncmp($action, 'security/user', 13) !== 0) {
            return;
        }

        $assetsUrl = $this->modx->getOption('assets_url', null, '/assets/');
        // Метка по времени файла: после деплоя браузер сам возьмёт новую версию,
        // а помнить про ручное поднятие номера не нужно.
        $file = MODX_ASSETS_PATH . 'components/pbauth/js/mgr/user.js';
        $version = is_file($file) ? filemtime($file) : 1;

        $this->modx->lexicon->load('pbauth:default');

        $config = [
            'impersonate' => $impersonate,
            'userPage' => $userPage,
            // На странице пользователя шаблон может содержать {username}, а из
            // ExtJS его достать неоткуда — подставляем здесь.
            'username' => $this->currentUsername(),
            'lang' => [
                'impersonate' => $this->modx->lexicon('pbauth_impersonate'),
                'view' => $this->modx->lexicon('pbauth_user_page_view'),
            ],
        ];

        $this->modx->regClientStartupHTMLBlock(
            '<script>Ext.ns("pbAuth");pbAuth.mgr='
            . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . ';</script>'
        );

        $this->modx->regClientStartupScript(
            $assetsUrl . "components/pbauth/js/mgr/user.js?v={$version}"
        );
    }

    /**
     * Логин пользователя, страница которого открыта. Пусто — открыт список.
     */
    protected function currentUsername(): string
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            return '';
        }

        $user = $this->modx->getObject(\modUser::class, $id);

        return $user ? (string)$user->get('username') : '';
    }
}
