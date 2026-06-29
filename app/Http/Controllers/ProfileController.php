<?php

namespace App\Http\Controllers;

use App\Events\UserColorChangedEvent;
use App\Http\Requests\ProfileFormRequest;
use App\Http\Requests\Tag\TagFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\Season;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Service\DungeonRoute\CoverageServiceInterface;
use App\Service\Reverb\ReverbHttpApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
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
     * @return View
     */
    public function edit(Request $request): View
    {
        return view('profile.edit');
    }

    /**
     * @return View
     */
    public function view(Request $request, User $user): View
    {
        return view('profile.view', ['user' => $user]);
    }

    public function routes(
        CoverageServiceInterface $coverageService,
        SeasonServiceInterface   $seasonService,
    ): View {
        $season = null;
        if (isset($_COOKIE['dungeonroute_coverage_season_id'])) {
            $season = Season::find($_COOKIE['dungeonroute_coverage_season_id']);
        }

        $season ??= $seasonService->getCurrentSeason();

        /** @var User $user */
        $user = Auth::user();

        return view('profile.overview', [
            'dungeonRoutes' => $coverageService->getForUser($user, $season),
        ]);
    }

    /**
     * @return View
     */
    public function favorites(Request $request): View
    {
        return view('profile.favorites');
    }

    /**
     * @return View
     */
    public function tags(Request $request): View
    {
        return view('profile.tags');
    }

    /**
     * @throws Exception
     */
    public function update(
        ProfileFormRequest            $request,
        User                          $user,
        ReverbHttpApiServiceInterface $reverbHttpApiService,
    ): RedirectResponse {
        $validated = $request->validated();

        // Allow username change once!
        if ($user->isOAuth()) {
            // When the user may change the username
            if (isset($validated['name']) && !$user->changed_username) {
                // Only when the user's name has actually changed
                if ($user->name !== $validated['name']) {
                    $user->name             = $validated['name'];
                    $user->changed_username = true;
                }
            }
        } // May not change e-mail when OAuth
        else {
            $user->email = $validated['email'] ?? $user->email;
        }

        $user->echo_color            = $validated['echo_color'] ?? randomHexColor();
        $user->echo_anonymous        = $validated['echo_anonymous'] ?? false;
        $user->game_server_region_id = $validated['game_server_region_id'];
        $user->timezone              = $validated['timezone'];

        // Only when no duplicates are found!
        if ($user->save()) {
            // Handle changing of avatar if the user did so
            if (isset($validated['avatar'])) {
                $user->saveUploadedFile($validated['avatar']);
            }

            Session::flash('status', __('controller.profile.flash.profile_updated'));

            // Drop the caches for all of their routes since their profile name/icon may have changed
            foreach ($user->dungeonRoutes as $dungeonroute) {
                $dungeonroute->dropCaches($dungeonroute->id);
            }

            // Send an event that the user's color has changed
            try {
                // Propagate changes to any channel the user may be in
                foreach ($reverbHttpApiService->getChannels() as $name => $channel) {
                    $context = null;

                    // If it's a route edit page
                    if (str_contains((string)$name, 'route-edit')) {
                        $routeKey = str_replace(sprintf('presence-%s-route-edit.', config('app.type')), '', (string)$name);
                        /** @var DungeonRoute $context */
                        $context = DungeonRoute::where('public_key', $routeKey)->first();
                    } elseif (str_contains((string)$name, 'live-session')) {
                        $routeKey = str_replace(sprintf('presence-%s-live-session.', config('app.type')), '', (string)$name);
                        /** @var LiveSession $context */
                        $context = LiveSession::where('public_key', $routeKey)->first();
                    }

                    // Only if we could find a route
                    if ($context instanceof Model) {
                        // Check if the user is in this channel..
                        try {
                            foreach ($reverbHttpApiService->getChannelUsers($name) as $reverbChannelUser) {
                                if ((int)$reverbChannelUser['id'] === $user->id) {
                                    // Broadcast that channel that our user's color has changed
                                    broadcast(new UserColorChangedEvent($context, $user));

                                    break;
                                }
                            }
                        } catch (Exception $exception) {
                            Log::warning(sprintf('Unable to find users for channel %s', $name));
                        }
                    }
                }
            } catch (Exception $exception) {
                report($exception);

                Log::warning('Reverb server is probably not running!');
            }
        } else {
            abort(500, __('controller.profile.flash.unexpected_error_when_saving'));
        }

        return redirect()->route('profile.edit');
    }

    public function updatePrivacy(Request $request, User $user): RedirectResponse
    {
        $user->analytics_cookie_opt_out = $request->get('analytics_cookie_opt_out');

        if (!$user->save()) {
            abort(500, __('controller.profile.flash.unexpected_error_when_saving'));
        } else {
            Session::flash('status', __('controller.profile.flash.privacy_settings_updated'));
        }

        return redirect()->route('profile.edit');
    }

    /**
     * @return View
     */
    public function changepassword(Request $request): View
    {
        $currentPw          = $request->get('current_password');
        $newPassword        = $request->get('new_password');
        $newPasswordConfirm = $request->get('new_password-confirm');

        /** @var User $user */
        $user = Auth::getUser();

        $error = [];
        // Check if the entered PW was correct
        if (!Auth::attempt([
            'name'     => $user->name,
            'password' => $currentPw,
        ])) {
            $error = ['passwords_incorrect' => __('controller.profile.flash.current_password_is_incorrect')];
        } // New passwords must match
        elseif ($newPassword !== $newPasswordConfirm) {
            $error = ['passwords_no_match' => __('controller.profile.flash.new_passwords_do_not_match')];
        } // But not the same password as they had
        elseif ($currentPw === $newPassword) {
            $error = ['passwords_match' => __('controller.profile.flash.new_password_equals_old_password')];
        } else {
            $user->update([
                'password' => Hash::make($newPassword),
            ]);
            Session::flash('status', __('controller.profile.flash.password_changed'));
        }

        // @todo Send an e-mail letting the user know the password has been changed

        return view('profile.edit')->withErrors($error);
    }

    /**
     * Creates a tag from the tag manager
     *
     * @return RedirectResponse
     */
    public function createTag(TagFormRequest $request): RedirectResponse
    {
        $error = [];

        $tagCategoryId = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL];

        if (!Tag::where('name', $request->get('tag_name_new'))
            ->where('context_id', Auth::id())
            ->where('context_class', User::class)
            ->where('tag_category_id', $tagCategoryId)
            ->exists()) {
            Tag::saveFromRequest($request, Auth::user(), $tagCategoryId);

            Session::flash('status', __('controller.profile.flash.tag_created_successfully'));
        } else {
            $error = ['tag_name_new' => __('controller.profile.flash.tag_already_exists')];
        }

        return redirect()->route('profile.tags')->withErrors($error);
    }

    /**
     * @throws Exception
     */
    public function delete(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::getUser();
        if ($user->hasRole(Role::ROLE_ADMIN)) {
            throw new Exception(__('controller.profile.flash.admins_cannot_delete_themselves'));
        }

        try {
            User::findOrFail($user->id)->delete();
            Auth::logout();
            Session::flash('status', __('controller.profile.flash.account_deleted_successfully'));
        } catch (Exception) {
            Session::flash('warning', __('controller.profile.flash.error_deleting_account'));
        }

        return redirect()->route('home');
    }
}
