<?php

namespace PageBlocks\App\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PageBlocks\Support\Mail;

class RegisterController extends AuthController
{
    public array $userGroups = [];

    public function show()
    {
        return view('file:auth/templates/auth', [
            'title' => lang('auth.register_title'),
            'form' => 'form.register'
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'honeypot' => 'empty|exclude',
            'username' => 'required|unique:modUser',
            'email' => 'required|email|unique:modUserProfile',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $token = bin2hex(random_bytes(32));

        $user = $this->modx->newObject($this->userClassKey);
        $user->fromArray(array_merge($validated, [
            'class_key' => $user->class_key,
            'active' => 0,
            'remote_key' => $token,
            'values' => json_encode([])
        ]));

        $profile = $this->modx->newObject($this->profileClassKey);
        $profile->fromArray($validated);
        $user->addOne($profile, 'Profile');
        if (!$user->save()) {
            return response()->error(lang('auth.register_error'));
        }

        $this->setUserGroups($user);
        $this->sendNotificationEmail($validated, $token);

        return response()->success(lang('auth.register_success'));
    }

    private function setUserGroups($user): void
    {
        foreach ($this->userGroups as $group) {
            $user->joinGroup($group);
        }
    }

    private function sendNotificationEmail(array $validated, string $token): void
    {
        $homePage = $this->modx->getOption('site_url');
        $validated['link'] = "{$homePage}verify-email/$token";

        Mail::to($validated['email'])
            ->subject(lang('auth.register_subject'))
            ->view('file:auth/chunks/email.verifyEmail', $validated)
            ->send();
    }

}