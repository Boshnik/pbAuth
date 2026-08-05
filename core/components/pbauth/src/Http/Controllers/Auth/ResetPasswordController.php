<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Social\SocialAccounts;
use Boshnik\PbAuth\Support\Config;

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

        return $this->page('auth', 'reset_password', [
            'title' => lang('auth.reset_password_title'),
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate(Config::rules('reset_password'));

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

        // Пароль задан осознанно — значит пользователь больше не заперт в
        // соцсети и может отвязать последнюю.
        SocialAccounts::markPasswordless($user, false);

        Dispatcher::fire(Dispatcher::AFTER_RESET_PASSWORD, ['user' => $user]);

        return response()->success('', $this->redirectTo('reset_password'));
    }
}
