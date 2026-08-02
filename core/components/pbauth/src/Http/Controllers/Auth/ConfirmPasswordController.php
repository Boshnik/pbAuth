<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Support\Config;

class ConfirmPasswordController extends AuthController
{
    public function show()
    {
        return $this->page('auth', 'confirm_password', [
            'title' => lang('auth.confirm_password_title'),
        ]);
    }

    public function confirmPassword(Request $request)
    {
        $request->validate(Config::rules('confirm_password'));

        if (!$this->modx->user->passwordMatches($request->password)) {
            return response()->error('', [
                'password' => $this->modx->lexicon('user_err_password'),
            ]);
        }

        return response()->success(lang('auth.user_confirm_password_success'));
    }
}
