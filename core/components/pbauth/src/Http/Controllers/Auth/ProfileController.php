<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Support\Config;

class ProfileController extends AuthController
{
    public function show()
    {
        return $this->page('profile', 'profile', [
            'title' => lang('auth.profile_title'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->modx->user;
        $profile = $user->getOne('Profile');

        $validated = $request->validate(Config::rules('profile', [
            ':user_id' => $user->id,
            ':profile_id' => $profile->id,
        ]));

        if ($request->hasFile('newphoto')) {
            $path = strtr(Config::get('avatar_path'), [':user_id' => $user->id]);
            $photo = $request->file('newphoto')->store($path);
            $validated['photo'] = $photo['url'];
        }

        $user->fromArray($validated);
        $profile->fromArray($validated);

        // Поля, которых нет среди колонок modUserProfile, сайт раскладывает сам
        // — обычно в `extended`. Событие для того и стоит перед save().
        Dispatcher::fire(Dispatcher::USER_SAVING, [
            'user' => $user,
            'profile' => $profile,
            'validated' => $validated,
            'action' => 'profile',
        ]);

        $user->save();
        $profile->save();

        Dispatcher::fire(Dispatcher::AFTER_PROFILE_UPDATE, [
            'user' => $user,
            'profile' => $profile,
            'validated' => $validated,
        ]);

        return response()->success(lang('auth.update_profile_success'));
    }
}
