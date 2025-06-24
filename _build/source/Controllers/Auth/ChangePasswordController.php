<?php

namespace PageBlocks\App\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;

class ChangePasswordController extends AuthController
{
    public function show()
    {
        return view('file:auth/templates/profile', [
            'title' => lang('auth.change_password_title'),
            'form' => 'form.changePassword'
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|min:8',
            'password' => 'required|min:8|confirmed',
        ]);

        $changed = $this->modx->user->changePassword($request->password, $request->old_password);
        if (!$changed) {
            return response()->error('', [
                'old_password' => lang('auth.old_password_error'),
            ]);
        }

        return response()->success(lang('auth.change_password_success'));
    }

}