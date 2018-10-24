<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    //

    public function edit(Request $request)
    {
        return view('profile.edit');
    }

    /**
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        $user->email = $request->get('email');
        $user->game_server_region_id = $request->get('game_server_region_id');

        $exists = User::where('email', $user->email)->where('id', '<>', $user->id)->get()->count() > 0;
        if (!$exists) {
            if (!$user->save()) {
                abort(500, __('An unexpected error occurred trying to save your profile'));
            } else {
                \Session::flash('status', __('Profile updated'));
            }
        } else {
            \Session::flash('warning', __('That e-mail is already in use.'));
        }

        return redirect()->route('profile.edit');
    }

    public function view(Request $request, User $user)
    {
        return view('profile.view');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function changepassword(Request $request)
    {
        $currentPw = $request->get('current_password');
        $newPassword = $request->get('new_password');
        $newPasswordConfirm = $request->get('new_password-confirm');

        $user = Auth::getUser();

        $error = [];
        // Check if the entered PW was correct
        if (Auth::attempt(['name' => $user->name, 'password' => $currentPw])) {
            // New passwords must match
            if ($newPassword === $newPasswordConfirm) {
                // But not the same password as he/she had
                if ($currentPw !== $newPassword) {
                    $user->password = Hash::make($newPassword);
                    $user->save();
                    \Session::flash('status', __('Password changed'));

                    // @todo Send an e-mail letting the user know the password has been changed
                } else {
                    $error = ['passwords_match' => __('New password equals the old password')];
                }
            } else {
                $error = ['passwords_no_match' => __('New passwords do not match')];
            }
        } else {
            $error = ['passwords_incorrect' => __('Current password is incorrect')];
        }

        return view('profile.edit')->withErrors($error);
    }

    public function list(Request $request)
    {
        return view('profile.list');
    }
}
