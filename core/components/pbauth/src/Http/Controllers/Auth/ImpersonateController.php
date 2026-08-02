<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Support\Config;

/**
 * «Авторизоваться на сайте» из менеджера MODX.
 *
 * Менеджер и фронт делят одну PHP-сессию, поэтому проверять что-то ещё не нужно:
 * авторизацией служит сама сессия. Убеждаемся, что в ней есть токен mgr-контекста
 * и принадлежит он пользователю с флагом sudo, — и логиним выбранного
 * пользователя в web. Ни токенов выпускать, ни хранить их не требуется.
 */
class ImpersonateController extends AuthController
{
    public function impersonate(int $id, Request $request)
    {
        if (!Config::get('impersonate_enabled', true)) {
            abort(404);
        }

        $mgrUserId = (int)($_SESSION['modx.user.contextTokens']['mgr'] ?? 0);
        if (!$mgrUserId) {
            abort(403, lang('auth.impersonate_no_manager'));
        }

        $manager = $this->modx->getObject($this->userClassKey, $mgrUserId);
        if (!$manager || !$manager->get('sudo')) {
            abort(403, lang('auth.impersonate_denied'));
        }

        $target = $this->modx->getObject($this->userClassKey, $id);
        if (!$target) {
            abort(404, lang('auth.impersonate_not_found'));
        }

        // Заблокированные и неактивированные аккаунты не открываем: под ними и
        // сам пользователь войти не может.
        $profile = $target->getOne('Profile');
        if (!$target->get('active') || ($profile && $profile->get('blocked'))) {
            abort(403, lang('auth.impersonate_blocked'));
        }

        $target->addSessionContext($this->modx->context->key ?? 'web');

        foreach ($this->getContexts() as $context) {
            $target->addSessionContext($context);
        }

        Dispatcher::fire(Dispatcher::AFTER_IMPERSONATE, [
            'user' => $target,
            'manager' => $manager,
        ]);

        return redirect($this->redirectTo('impersonate'));
    }
}
