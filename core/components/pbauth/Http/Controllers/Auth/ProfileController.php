<?php

namespace PageBlocks\App\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;

class ProfileController extends AuthController
{
    public function show()
    {
        return view('file:auth/templates/profile', [
            'title' => lang('auth.profile_title'),
            'form' => 'form.profile'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->modx->user;
        $profile = $user->getOne('Profile');

        $validated = $request->validate([
            'username' => "required|unique:modUser,username,$user->id",
            'email'    => "required|email|unique:modUserProfile,email,$profile->id",
            'fullname' => 'nullable|string',
            'phone'    => 'nullable|string',
            'photo'    => 'nullable|string',
            'newphoto' => 'nullable|file|image|mimes:image/jpg,image/jpeg,image/png|max:2048|exclude',
        ]);

        if ($request->hasFile('newphoto')) {
            $photo = $request->file('newphoto')->store('assets/images/avatars/' . $user->id);
            $validated['photo'] = $photo['url'];
        }

        $user->fromArray($validated);
        $user->save();

        $profile->fromArray($validated);
        $profile->save();

        return response()->success(lang('auth.update_profile_success'));
    }

}