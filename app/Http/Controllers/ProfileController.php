<?php

namespace App\Http\Controllers;

use App\Events\UserColorChangedEvent;
use App\Http\Requests\Tag\TagFormRequest;
use App\Models\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Service\EchoServerHttpApiService;
use App\User;
use Exception;
use Faker\Provider\Color;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Session;

class ProfileController extends Controller
{
    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request)
    {
        return view('profile.edit');
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function routes(Request $request)
    {
        return view('profile.routes');
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function tags(Request $request)
    {
        return view('profile.tags');
    }

    /**
     * @param Request $request
     * @param User $user
     * @param EchoServerHttpApiService $echoServerHttpApiService
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(Request $request, User $user, EchoServerHttpApiService $echoServerHttpApiService)
    {
        // Allow username change once!
        if ($user->isOAuth()) {
            // When the user may change the username
            if ($request->has('name') && !$user->changed_username) {
                // Only when the user's name has actually changed
                if ($user->name !== $request->get('name')) {
                    $user->name = $request->get('name');
                    $user->changed_username = true;
                }
            }
        } // May not change e-mail when OAuth
        else {
            $user->email = $request->get('email');
        }
        $user->theme = $request->get('theme');
        $user->echo_color = $request->get('echo_color', Color::hexColor());
        $user->echo_anonymous = $request->get('echo_anonymous', false);
        $user->game_server_region_id = $request->get('game_server_region_id');
        $user->timezone = $request->get('timezone');

        // Check if these things already exist or not, if so notify the user that they couldn't be saved
        $emailExists = User::where('email', $user->email)->where('id', '<>', $user->id)->get()->count() > 0;
        if ($emailExists) {
            Session::flash('warning', __('That e-mail is already in use.'));
        }

        $nameExists = User::where('name', $user->name)->where('id', '<>', $user->id)->get()->count() > 0;
        if ($nameExists) {
            Session::flash('warning', __('That username is already in use.'));
        }

        // Only when no duplicates are found!
        if (!$emailExists && !$nameExists) {
            if ($user->save()) {
                Session::flash('status', __('Profile updated'));

                try {
                    // Propagate changes to any channel the user may be in
                    foreach ($echoServerHttpApiService->getChannels() as $channel) {
                        $assoc = get_object_vars($channel);
                        $channelName = array_keys($assoc)[0];

                        $routeKey = str_replace(sprintf('presence-%s-route-edit.', env('APP_TYPE')), '', $channelName);

                        $userInChannel = false;
                        // Check if the user is in this channel..
                        foreach ($echoServerHttpApiService->getChannelUsers($channelName) as $users) {

                            foreach ($users as $channelUser) {
                                if ($channelUser->id === $user->id) {
                                    $userInChannel = true;
                                    break;
                                }
                            }
                        }

                        if ($userInChannel) {
                            /** @var DungeonRoute $dungeonRoute */
                            $dungeonRoute = DungeonRoute::where('public_key', $routeKey)->firstOrFail();
                            // Broadcast that channel that the user's color has changed
                            broadcast(new UserColorChangedEvent($dungeonRoute, $user));
                        }
                    }
                } catch (Exception $exception) {
                    Log::warning('Echo server is probably not running!');
                }
            } else {
                abort(500, __('An unexpected error occurred trying to save your profile'));
            }
        }

        return redirect()->route('profile.edit');
    }

    /**
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function updatePrivacy(Request $request, User $user)
    {
        $user->analytics_cookie_opt_out = $request->get('analytics_cookie_opt_out');

        if (!$user->save()) {
            abort(500, __('An unexpected error occurred trying to save your profile'));
        } else {
            Session::flash('status', __('Privacy settings updated'));
        }

        return redirect()->route('profile.edit');
    }

    public function view(Request $request, User $user)
    {
        return view('profile.view');
    }

    /**
     * @param Request $request
     * @return Factory|View
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
                    Session::flash('status', __('Password changed'));

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

    /**
     * Creates a tag from the tag manager
     *
     * @param TagFormRequest $request
     * @return Application|Factory|View
     */
    public function createtag(TagFormRequest $request)
    {
        $error = [];

        $tagCategoryId = TagCategory::fromName(TagCategory::DUNGEON_ROUTE_PERSONAL)->id;

        if (!Tag::where('name', $request->get('tag_name_new'))
            ->where('user_id', Auth::id())
            ->where('tag_category_id', $tagCategoryId)
            ->exists()) {

            Tag::saveFromRequest($request, $tagCategoryId);

            Session::flash('status', __('Tag created successfully'));
        } else {
            $error = ['tag_name_new' => __('This tag already exists')];
        }

        return view('profile.edit')->withErrors($error);
    }

    public function list(Request $request)
    {
        return view('profile.list');
    }

    public function delete(Request $request)
    {
        if (Auth::getUser()->hasRole('admin')) {
            throw new Exception('Admins cannot delete themselves!');
        }

        try {
            User::findOrFail(Auth::id())->delete();
            Auth::logout();
            Session::flash('status', __('Account deleted successfully.'));
        } catch (Exception $e) {
            Session::flash('warning', __('An error occurred. Please try again.'));
        }

        return redirect()->route('home');
    }
}
