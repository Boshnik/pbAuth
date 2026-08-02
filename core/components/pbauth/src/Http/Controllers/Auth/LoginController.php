<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Support\Config;

class LoginController extends AuthController
{
    public function show()
    {
        return $this->page('auth', 'login', [
            'title' => lang('auth.login_title'),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate(Config::rules('login'));

        $user = $this->modx->getObject($this->userClassKey, ['username' => $request->username]);
        if (!$user) {
            $profile = $this->modx->getObject($this->profileClassKey, ['email' => $request->username])
                ?: $this->modx->getObject($this->profileClassKey, ['phone' => $request->username]);

            if (!$profile || !$user = $profile->getOne('User')) {
                return response()->error('', [
                    'username' => $this->modx->lexicon('user_err_nf'),
                ]);
            }

            $request->username = $user->username;
        }

        $response = $this->modx->runProcessor($this->getProccesorPath('login'), [
            'username' => $request->username,
            'password' => $request->password,
            'rememberme' => $request->remember ?? false,
            'login_context' => $this->modx->context->key ?? 'web',
            'add_contexts' => implode(',', $this->getContexts()),
        ]);

        if ($response->isError()) {
            return $this->getProcessorError($response);
        }

        Dispatcher::fire(Dispatcher::AFTER_LOGIN, ['user' => $user]);

        return response()->success('', $this->loginRedirect($request));
    }

    public function logout()
    {
        $user = $this->modx->user;
        $this->modx->runProcessor($this->getProccesorPath('logout'));

        Dispatcher::fire(Dispatcher::AFTER_LOGOUT, ['user' => $user]);

        return redirect($this->redirectTo('logout'));
    }

    /**
     * Форма может попросить вернуть пользователя туда, откуда его завернули на
     * вход. Принимается только путь внутри сайта — со схемой, хостом или
     * протокол-относительным `//host` форма стала бы открытым редиректом.
     */
    protected function loginRedirect(Request $request): string
    {
        $default = $this->redirectTo('login');
        $param = Config::get('login_redirect_param');

        if (empty($param)) {
            return $default;
        }

        $target = trim((string)$request->get($param, ''));
        if ($target === '' || str_starts_with($target, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target)) {
            return $default;
        }

        return '/' . ltrim($target, '/');
    }
}
