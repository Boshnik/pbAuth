<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Support\Config;

class ChangePasswordController extends AuthController
{
    public function show()
    {
        return $this->page('profile', 'change_password', [
            'title' => lang('auth.change_password_title'),
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate(Config::rules('change_password'));

        $changed = $this->modx->user->changePassword($request->password, $request->old_password);
        if (!$changed) {
            return response()->error('', [
                'old_password' => lang('auth.old_password_error'),
            ]);
        }

        Dispatcher::fire(Dispatcher::AFTER_CHANGE_PASSWORD, ['user' => $this->modx->user]);

        return response()->success(lang('auth.change_password_success'));
    }
}
