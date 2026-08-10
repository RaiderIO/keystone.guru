<?php

namespace App\Http\Controllers;

use App\Events\UserColorChangedEvent;
use App\Features\CreatorProfiles;
use App\Http\Requests\CreatorProfileFormRequest;
use App\Http\Requests\ProfileFormRequest;
use App\Http\Requests\Tag\TagFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Models\UserPinnedDungeonRoute;
use App\Models\UserSocialLink;
use App\Repositories\Interfaces\UserPinnedDungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\UserSocialLinkRepositoryInterface;
use App\Service\DungeonRoute\CoverageServiceInterface;
use App\Service\Reverb\ReverbHttpApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Session;

class ProfileController extends Controller
{
    /**
     * @return View
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', $this->creatorProfileEditViewData());
    }

    /**
     * @return View
     */
    public function view(Request $request, User $user): View
    {
        $creatorProfileActive = Feature::active(CreatorProfiles::class);

        /** @var Collection<int, UserSocialLink> $socialLinks */
        $socialLinks = collect();
        /** @var Collection<int, DungeonRoute> $pinnedDungeonRoutes */
        $pinnedDungeonRoutes = collect();
        $publishedRouteCount = 0;

        if ($creatorProfileActive) {
            $publishedRouteCount = DungeonRoute::query()
                ->where('author_id', $user->id)
                ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD])
                ->count();

            // The pinned routes render through the shared route card, which needs the same relation
            // set DiscoverService eager loads - lazy loading is disabled, so a miss here is a 500
            $user->load([
                'socialLinks',
                'pinnedDungeonRoutes.dungeonRoute.author.iconfile',
                'pinnedDungeonRoutes.dungeonRoute.affixes',
                'pinnedDungeonRoutes.dungeonRoute.ratings',
                'pinnedDungeonRoutes.dungeonRoute.mappingVersion',
                'pinnedDungeonRoutes.dungeonRoute.thumbnails',
                'pinnedDungeonRoutes.dungeonRoute.dungeon',
                'pinnedDungeonRoutes.dungeonRoute.season.expansion',
                // Needed by mayUserView() for team-published routes
                'pinnedDungeonRoutes.dungeonRoute.team',
            ]);

            $socialLinks = $user->socialLinks;

            $viewer = Auth::user();

            // A pin only records intent - the viewer's own visibility rules still decide what is
            // actually shown, so pinning an unpublished route does not expose it
            $pinnedDungeonRoutes = $user->pinnedDungeonRoutes
                ->map(static fn(UserPinnedDungeonRoute $pin): ?DungeonRoute => $pin->dungeonRoute)
                ->filter(static fn(?DungeonRoute $dungeonRoute): bool => $dungeonRoute?->mayUserView($viewer) ?? false)
                ->values();
        }

        return view('profile.view', [
            'user'                 => $user,
            'creatorProfileActive' => $creatorProfileActive,
            'socialLinks'          => $socialLinks,
            'pinnedDungeonRoutes'  => $pinnedDungeonRoutes,
            'publishedRouteCount'  => $publishedRouteCount,
        ]);
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
        Gate::authorize('update', $user);

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
                                    try {
                                        broadcast(new UserColorChangedEvent($context, $user));
                                    } catch (BroadcastException) {
                                        // Ignore broadcast failures
                                    }

                                    break;
                                }
                            }
                        } catch (Exception) {
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
        Gate::authorize('update', $user);

        $user->analytics_cookie_opt_out = $request->get('analytics_cookie_opt_out');

        if (!$user->save()) {
            abort(500, __('controller.profile.flash.unexpected_error_when_saving'));
        } else {
            Session::flash('status', __('controller.profile.flash.privacy_settings_updated'));
        }

        return redirect()->route('profile.edit');
    }

    /**
     * Persist the creator podium settings: bio, social links, pinned routes and the directory
     * opt-out.
     */
    public function updateCreatorProfile(
        CreatorProfileFormRequest                 $request,
        UserSocialLinkRepositoryInterface         $userSocialLinkRepository,
        UserPinnedDungeonRouteRepositoryInterface $userPinnedDungeonRouteRepository,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validated();

        $user->bio                         = $validated['bio'] ?? null;
        $user->hide_from_creator_directory = (bool)($validated['hide_from_creator_directory'] ?? false);

        // The user row and its links are saved together: a failure partway through would otherwise
        // leave the bio updated while the links and pins still describe the previous state.
        // Replace rather than diff - both sets are capped at a handful of rows, and replacing keeps
        // the pin ordering trivially correct without reconciling against what was already stored
        DB::transaction(function () use ($user, $request, $userSocialLinkRepository, $userPinnedDungeonRouteRepository): void {
            if (!$user->save()) {
                abort(500, __('controller.profile.flash.unexpected_error_when_saving'));
            }

            $user->socialLinks()->delete();

            foreach ($request->socialLinks() as $platform => $url) {
                $userSocialLinkRepository->create([
                    'user_id'  => $user->id,
                    'platform' => $platform,
                    'url'      => $url,
                ]);
            }

            $user->pinnedDungeonRoutes()->delete();

            foreach ($request->pinnedDungeonRoutes() as $order => $dungeonRoute) {
                $userPinnedDungeonRouteRepository->create([
                    'user_id'          => $user->id,
                    'dungeon_route_id' => $dungeonRoute->id,
                    'order'            => $order,
                ]);
            }
        });

        Session::flash('status', __('controller.profile.flash.creator_profile_updated'));

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

        return view('profile.edit', $this->creatorProfileEditViewData())->withErrors($error);
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

        Gate::authorize('delete', $user);

        try {
            User::findOrFail($user->id)->delete();
            Auth::logout();
            Session::flash('status', __('controller.profile.flash.account_deleted_successfully'));
        } catch (Exception) {
            Session::flash('warning', __('controller.profile.flash.error_deleting_account'));
        }

        return redirect()->route('home');
    }

    /**
     * @return array{creatorProfileActive: bool, ownDungeonRoutes: Collection<int, DungeonRoute>, pinnedDungeonRouteIds: array<int, int>}
     */
    private function creatorProfileEditViewData(): array
    {
        $creatorProfileActive = Feature::active(CreatorProfiles::class);

        /** @var Collection<int, DungeonRoute> $ownDungeonRoutes */
        $ownDungeonRoutes = collect();
        /** @var array<int, int> $pinnedDungeonRouteIds */
        $pinnedDungeonRouteIds = [];

        if ($creatorProfileActive) {
            /** @var User $user */
            $user = Auth::user();

            // Sandbox routes expire, so they are deliberately not offered as pinnable
            $ownDungeonRoutes = $user->dungeonRoutes()
                ->whereNull('expires_at')
                ->with(['dungeon'])
                ->orderBy('title')
                ->get();

            $pinnedDungeonRouteIds = $user->pinnedDungeonRoutes
                ->pluck('dungeon_route_id')
                ->all();
        }

        return [
            'creatorProfileActive'  => $creatorProfileActive,
            'ownDungeonRoutes'      => $ownDungeonRoutes,
            'pinnedDungeonRouteIds' => $pinnedDungeonRouteIds,
        ];
    }
}
