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
        $secret_key = config('pbauth_recaptcha_secret_key');
        if (!empty($secret_key)) {
            $captcha = $request->input('g-recaptcha-response');
            if (empty($captcha) || !$this->verifyRecaptcha($captcha, $secret_key)) {
                return response()->error('Captcha verification failed. Please try again.');
            }
        }

        $ip = $request->ip();
        $recentCount = query('modUser')
            ->where([
                'ip' => $ip,
                'createdon:>' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ])
            ->count();

        if ($recentCount > 3) {
            return response()->error('Too many registrations from your IP. Try again later.');
        }

        $validated = $request->validate([
            'honeypot' => 'empty|exclude',
            'username' => 'required|alpha_dash:ascii|min:3|max:30|unique:modUser',
            'email' => 'required|email|unique:modUserProfile',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $token = bin2hex(random_bytes(32));
        $user = $this->modx->newObject($this->userClassKey);
        $user->fromArray(array_merge($validated, [
            'class_key' => $user->class_key,
            'active' => 0,
            'remote_key' => $token,
            'ip' => $ip,
            'values' => '[]'
        ]));

        $profile = $this->modx->newObject($this->profileClassKey);
        $profile->fromArray($validated);
        $user->addOne($profile, 'Profile');

        if (!$user->save()) {
            return response()->error(lang('auth.register_error'));
        }

        $this->setUserGroups($user);

        $this->sendNotificationEmail([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'token' => $token,
        ]);

        return response()->success(lang('auth.register_success'));
    }

    protected  function setUserGroups($user): void
    {
        foreach ($this->userGroups as $group) {
            $user->joinGroup($group);
        }
    }

    protected  function sendNotificationEmail(array $data): void
    {
        $username = htmlspecialchars($data['username'] ?? '', ENT_QUOTES, 'UTF-8');
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $token = preg_replace('/[^a-f0-9]/i', '', $data['token'] ?? '');

        if (!$email || !$token) {
            return;
        }

        $verifyUrl = MODX_SITE_URL . 'verify-email/' . $token;

        Mail::to($email)
            ->subject(lang('auth.register_subject'))
            ->view('file:auth/chunks/email.verifyEmail', [
                'username' => $username,
                'email' => $email,
                'verifyUrl' => $verifyUrl,
            ])
            ->send();
    }

    protected function verifyRecaptcha(string $token, string $secret): bool
    {
        if (empty($secret) || empty($token)) {
            return false;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return false;
        }

        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true && $result['score'] >= 0.5;
    }

}