<?php

namespace PageBlocks\App\Http\Controllers\Auth;

use Boshnik\PageBlocks\Facades\Request;

class LoginController extends AuthController
{
    public function show()
    {
        return view('file:auth/templates/auth', [
            'title' => lang('auth.login_title'),
            'form' => 'form.login'
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'honeypot' => 'empty',
            'username' => 'required',
            'password' => 'required|min:8'
        ]);

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

        return response()->success('', '/');
    }

    public function logout()
    {
        $this->modx->runProcessor($this->getProccesorPath('logout'));
        return redirect('/');
    }
}