<?php

namespace PageBlocks\App\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PageBlocks\Support\Mail;

class ConfirmPasswordController extends AuthController
{
    public function show()
    {
        return view('file:auth/templates/auth', [
            'title' => lang('auth.confirm_password_title'),
            'form' => 'form.confirmPassword',
        ]);
    }

    public function confirmPassword(Request $request)
    {
        $request->validate([
            'honeypot' => 'empty',
            'password' => 'required|string|min:8',
        ]);

        if (!$this->modx->user->passwordMatches($request->password)) {
            return response()->error('', [
                'password' => $this->modx->lexicon('user_err_password'),
            ]);
        }

        return response()->success(lang('auth.user_confirm_password_success'));
    }

}