<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileApiTokenFormRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Passport\Passport;

/**
 * The profile's API tokens tab: personal access tokens a user creates for their own scripts, and every token they
 * granted an OAuth client - both revocable from the same list.
 */
class ProfileApiTokenController extends Controller
{
    public function store(ProfileApiTokenFormRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validated();

        $result = $user->createToken($validated['name'], $validated['scopes']);

        // Only the hash of a token is stored, so this flash is the one and only time the user gets to see it
        Session::flash('api_token_plain_text', $result->accessToken);
        Session::flash('status', __('controller.profile.flash.api_token_created'));

        return redirect()->to(sprintf('%s#api-tokens', route('profile.edit')));
    }

    public function destroy(string $tokenId): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $tokenExists = Passport::token()->newQuery()
            ->where('user_id', $user->id)
            ->whereKey($tokenId)
            ->exists();

        abort_unless($tokenExists, 404);

        Passport::token()->newQuery()->whereKey($tokenId)->update(['revoked' => true]);
        Passport::refreshToken()->newQuery()->where('access_token_id', $tokenId)->update(['revoked' => true]);

        Session::flash('status', __('controller.profile.flash.api_token_revoked'));

        return redirect()->to(sprintf('%s#api-tokens', route('profile.edit')));
    }
}
