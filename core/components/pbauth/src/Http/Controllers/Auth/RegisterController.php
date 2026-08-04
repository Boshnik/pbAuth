<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Support\Config;

class RegisterController extends AuthController
{
    public function show()
    {
        return $this->page('auth', 'register', [
            'title' => lang('auth.register_title'),
        ]);
    }

    public function register(Request $request)
    {
        $secret_key = config('pbauth_recaptcha_secret_key');
        if (!empty($secret_key)) {
            $captcha = $request->input('g-recaptcha-response');
            if (empty($captcha) || !$this->verifyRecaptcha($captcha, $secret_key)) {
                return response()->error(lang('auth.recaptcha_failed'));
            }
        }

        $ip = $request->ip();
        $limit = (int)Config::get('register_ip_limit', 0);
        $attemptsKey = "pbauth_register_attempts:$ip";
        $attempts = (int)cache($attemptsKey);

        if ($limit > 0 && $attempts >= $limit) {
            return response()->error(lang('auth.register_ip_error'));
        }

        $validated = $request->validate(Config::rules('register'));

        $token = bin2hex(random_bytes(32));

        $user = $this->modx->newObject($this->userClassKey);
        $user->fromArray(array_merge($validated, [
            'class_key' => $user->class_key,
            'active' => 0,
            'remote_key' => $token,
        ]));

        $profile = $this->modx->newObject($this->profileClassKey);
        $profile->fromArray($validated);
        $user->addOne($profile, 'Profile');

        Dispatcher::fire(Dispatcher::USER_SAVING, [
            'user' => $user,
            'profile' => $profile,
            'validated' => $validated,
            'action' => 'register',
        ]);

        if (!$user->save()) {
            return response()->error(lang('auth.register_error'));
        }

        if ($limit > 0) {
            cache($attemptsKey, $attempts + 1, 3600);
        }

        $this->setUserGroups($user);

        Dispatcher::fire(Dispatcher::AFTER_REGISTER, [
            'user' => $user,
            'profile' => $profile,
            'validated' => $validated,
        ]);

        $this->sendNotificationEmail([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'token' => $token,
        ]);

        return response()->success(lang('auth.register_success'));
    }

    protected function setUserGroups($user): void
    {
        foreach (Config::get('user_groups', []) as $group) {
            $user->joinGroup($group);
        }
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
