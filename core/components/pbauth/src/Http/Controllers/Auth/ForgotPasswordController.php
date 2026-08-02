<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PageBlocks\Support\Mail;
use Boshnik\PbAuth\Support\Config;

class ForgotPasswordController extends AuthController
{
    public function show()
    {
        return $this->page('auth', 'forgot_password', [
            'title' => lang('auth.forgot_password_title'),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate(Config::rules('forgot_password'));

        $token = bin2hex(random_bytes(32));
        $expires = strtotime('+1 hour');

        $profile = $this->modx->getObject($this->profileClassKey, [
            'email' => $request->email
        ]);
        $user = $profile->getOne('User');
        $user->set('remote_key', $token);
        $user->set('remote_data', json_encode(compact('expires')));
        $user->save();

        $homePage = $this->modx->getOption('site_url');
        $validated['username'] = $user->username;
        $validated['link'] = "{$homePage}reset-password/$token";

        Mail::to($request->email)
            ->subject(lang('auth.reset_password_subject'))
            ->view('file:auth/chunks/email.resetPassword', $validated)
            ->send();

        return response()->success(lang('auth.forgot_password_success'));
    }
}
