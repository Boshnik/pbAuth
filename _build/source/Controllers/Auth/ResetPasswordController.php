<?php

namespace PageBlocks\App\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;

class ResetPasswordController extends AuthController
{
    public function show(string $token)
    {
        if (!$user = $this->modx->getObject($this->userClassKey, ['remote_key' => $token])) {
            return redirect(route('pageForgotPassword'));
        }

        $data = json_decode($user->remote_data ?: '{}', true);
        $expires = $data['expires'] ?? 0;

        if ($expires < time()) {
            return redirect(route('pageForgotPassword'));
        }

        return view('file:auth/templates/auth', [
            'title' => lang('auth.reset_password_title'),
            'form' => 'form.resetPassword',
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'honeypot' => 'empty',
            'token' => 'required|string',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $this->modx->getObject($this->userClassKey, ['remote_key' => $request->token]);
        if (!$user) {
            return response()->error(lang('auth.invalid_token'));
        }

        $user->set('password', $request->password);
        $user->save();

        $response = $this->modx->runProcessor($this->getProccesorPath('login'), [
            'username' => $user->username,
            'password' => $request->password,
            'rememberme' => true,
            'login_context' => $this->modx->context->key ?? 'web',
            'add_contexts' => implode(',', $this->getContexts()),
        ]);

        if ($response->isError()) {
            return $this->getProcessorError($response);
        }

        return response()->success('', '/');
    }

}