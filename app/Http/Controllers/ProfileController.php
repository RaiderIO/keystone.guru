<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    //

    public function edit(Request $request)
    {
        return view('profile.edit');
    }

    //

    public function update(Request $request, User $user)
    {
        $user->name = $request->get('name');
        $user->email = $request->get('email');

        if (!$user->save()) {
            abort(500, __('An unexpected error occurred trying to save your profile'));
        } else {
            \Session::flash('status', __('Profile updated'));
        }

        return redirect()->route('profile.edit');
    }

    public function view(Request $request, User $user)
    {
        return view('profile.view');
    }

    public function list(Request $request)
    {
        return view('profile.list');
    }

    public function changepassword(Request $request)
    {
        return view('profile.list');
    }
}
