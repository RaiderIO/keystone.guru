<?php

namespace App\Http\Controllers;

use App\Events\UserColorChangedEvent;
use App\Http\Requests\Tag\TagFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Service\EchoServer\EchoServerHttpApiServiceInterface;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class ProfileController extends Controller
{
    /**
     * @return Application|Factory|View
     */
    public function edit(Request $request): \Illuminate\View\View
    {
        return view('profile.edit');
    }

    /**
     * @return Application|Factory|View
     */
    public function view(Request $request, User $user): \Illuminate\View\View
    {
        return view('profile.view', ['user' => $user]);
    }

    public function routes(Request $request): RedirectResponse
    {
        return redirect()->route('home');
    }

    /**
     * @return Application|Factory|View
     */
    public function favorites(Request $request): \Illuminate\View\View
    {
        return view('profile.favorites');
    }

    /**
     * @return Application|Factory|View
     */
    public function tags(Request $request): \Illuminate\View\View
    {
        return view('profile.tags');
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, User $user, EchoServerHttpApiServiceInterface $echoServerHttpApiService): RedirectResponse
    {
        // Allow username change once!
        if ($user->isOAuth()) {
            // When the user may change the username
            if ($request->has('name') && ! $user->changed_username) {
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
        $user->echo_color = $request->get('echo_color', randomHexColor());
        $user->echo_anonymous = $request->get('echo_anonymous', false);
        $user->game_server_region_id = $request->get('game_server_region_id');
        $user->timezone = $request->get('timezone');

        // Check if these things already exist or not, if so notify the user that they couldn't be saved
        $emailExists = User::where('email', $user->email)->where('id', '<>', $user->id)->count() > 0;
        if ($emailExists) {
            Session::flash('warning', __('controller.profile.flash.email_already_in_use'));
        }

        $nameExists = User::where('name', $user->name)->where('id', '<>', $user->id)->count() > 0;
        if ($nameExists) {
            Session::flash('warning', __('controller.profile.flash.username_already_in_use'));
        }

        // Only when no duplicates are found!
        if (! $emailExists && ! $nameExists) {
            if ($user->save()) {

                // Handle changing of avatar if the user did so
                $avatar = $request->file('avatar');
                if ($avatar !== null) {
                    $user->saveUploadedFile($avatar);
                }

                Session::flash('status', __('controller.profile.flash.profile_updated'));

                // Drop the caches for all of their routes since their profile name/icon may have changed
                foreach ($user->dungeonRoutes as $dungeonroute) {
                    $dungeonroute->dropCaches($dungeonroute->id);
                }

                // Send an event that the user's color has changed
                try {
                    // Propagate changes to any channel the user may be in
                    foreach ($echoServerHttpApiService->getChannels() as $name => $channel) {
                        $context = null;

                        // If it's a route edit page
                        if (str_contains($name, 'route-edit')) {
                            $routeKey = str_replace(sprintf('presence-%s-route-edit.', config('app.type')), '', $name);
                            /** @var DungeonRoute $context */
                            $context = DungeonRoute::where('public_key', $routeKey)->first();
                        } elseif (str_contains($name, 'live-session')) {
                            $routeKey = str_replace(sprintf('presence-%s-live-session.', config('app.type')), '', $name);
                            /** @var LiveSession $context */
                            $context = LiveSession::where('public_key', $routeKey)->first();
                        }

                        // Only if we could find a route
                        if ($context instanceof Model) {
                            // Check if the user is in this channel..
                            foreach ($echoServerHttpApiService->getChannelUsers($name) as $channelUser) {

                                if ($channelUser['id'] === $user->id) {
                                    // Broadcast that channel that our user's color has changed
                                    broadcast(new UserColorChangedEvent($context, $user));

                                    break;
                                }
                            }
                        }
                    }
                } catch (Exception $exception) {
                    report($exception);

                    Log::warning('Echo server is probably not running!');
                }
            } else {
                abort(500, __('controller.profile.flash.unexpected_error_when_saving'));
            }
        }

        return redirect()->route('profile.edit');
    }

    public function updatePrivacy(Request $request, User $user): RedirectResponse
    {
        $user->analytics_cookie_opt_out = $request->get('analytics_cookie_opt_out');

        if (! $user->save()) {
            abort(500, __('controller.profile.flash.unexpected_error_when_saving'));
        } else {
            Session::flash('status', __('controller.profile.flash.privacy_settings_updated'));
        }

        return redirect()->route('profile.edit');
    }

    /**
     * @return Factory|View
     */
    public function changepassword(Request $request): \Illuminate\View\View
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
                    Session::flash('status', __('controller.profile.flash.password_changed'));

                    // @todo Send an e-mail letting the user know the password has been changed
                } else {
                    $error = ['passwords_match' => __('controller.profile.flash.new_password_equals_old_password')];
                }
            } else {
                $error = ['passwords_no_match' => __('controller.profile.flash.new_passwords_do_not_match')];
            }
        } else {
            $error = ['passwords_incorrect' => __('controller.profile.flash.current_password_is_incorrect')];
        }

        return view('profile.edit')->withErrors($error);
    }

    /**
     * Creates a tag from the tag manager
     *
     * @return Application|Factory|View
     */
    public function createtag(TagFormRequest $request): \Illuminate\View\View
    {
        $error = [];

        $tagCategoryId = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL];

        if (! Tag::where('name', $request->get('tag_name_new'))
            ->where('user_id', Auth::id())
            ->where('tag_category_id', $tagCategoryId)
            ->exists()) {

            Tag::saveFromRequest($request, $tagCategoryId);

            Session::flash('status', __('controller.profile.flash.tag_created_successfully'));
        } else {
            $error = ['tag_name_new' => __('controller.profile.flash.tag_already_exists')];
        }

        return view('profile.edit')->withErrors($error);
    }

    /**
     * @return Application|Factory|View
     */
    public function list(Request $request): \Illuminate\View\View
    {
        return view('profile.list');
    }

    /**
     * @throws Exception
     */
    public function delete(Request $request): RedirectResponse
    {
        if (Auth::getUser()->hasRole('admin')) {
            throw new Exception(__('controller.profile.flash.admins_cannot_delete_themselves'));
        }

        try {
            User::findOrFail(Auth::id())->delete();
            Auth::logout();
            Session::flash('status', __('controller.profile.flash.account_deleted_successfully'));
        } catch (Exception) {
            Session::flash('warning', __('controller.profile.flash.error_deleting_account'));
        }

        return redirect()->route('home');
    }
}
