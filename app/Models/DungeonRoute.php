<?php

namespace App\Models;

use App\Http\Requests\DungeonRoute\DungeonRouteTemporaryFormRequest;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemies\PridefulEnemy;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\Traits\HasTags;
use App\Models\Traits\Reportable;
use App\Models\Traits\SerializesDates;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use App\User;
use Carbon\Carbon;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @property $id int
 * @property $public_key string
 * @property $author_id int
 * @property $dungeon_id int
 * @property $faction_id int
 * @property $team_id int|null
 * @property $published_state_id int
 *
 * @property $clone_of string
 * @property $title string
 * @property $description string
 * @property $level_min int
 * @property $level_max int
 * @property $difficulty string
 * @property $seasonal_index int
 * @property $enemy_forces int
 * @property $teeming boolean
 * @property $demo boolean
 *
 * @property array $setup
 * @property double $avg_rating
 * @property int $rating_count
 * @property boolean $has_thumbnail
 *
 * @property string $pull_gradient
 * @property boolean $pull_gradient_apply_always
 *
 * @property int $views
 * @property int $popularity
 *
 * @property Carbon $thumbnail_refresh_queued_at
 * @property Carbon $thumbnail_updated_at
 * @property Carbon $updated_at
 * @property Carbon $created_at
 * @property Carbon $published_at
 * @property Carbon $expires_at
 *
 * @property Dungeon $dungeon
 * @property Path $route
 * @property Faction $faction
 * @property User|null $author Can be null in case of temporary route
 * @property MDTImport $mdtImport
 * @property Team $team
 * @property PublishedState $publishedState
 *
 * @property Collection $specializations
 * @property Collection $classes
 * @property Collection $races
 *
 * @property Collection $playerspecializations
 * @property Collection $playerclasses
 * @property Collection $playerraces
 *
 * @property Collection|AffixGroup[] $affixes
 * @property Collection|DungeonRouteAffixGroup[] $affixgroups
 * @property Collection|DungeonRouteRating[] $ratings
 * @property Collection|DungeonRouteFavorite[] $favorites
 * @property Collection|LiveSession[] $livesessions
 *
 * @property Collection|Brushline[] $brushlines
 * @property Collection|Path[] $paths
 * @property Collection|KillZone[] $killzones
 * @property Collection|PridefulEnemy[] $pridefulenemies
 * @property Collection|OverpulledEnemy[] $overpulledenemies
 *
 * @property Collection|DungeonRouteEnemyRaidMarker[] $enemyraidmarkers
 * @property Collection|MapIcon[] $mapicons
 * @property Collection|PageView[] $pageviews
 *
 * @property Collection|Tag[] $tags
 *
 * @property Collection $routeattributes
 * @property Collection $routeattributesraw
 *
 * @method static Builder visible()
 * @method static Builder visibleWithUnlisted()
 *
 * @mixin Eloquent
 */
class DungeonRoute extends Model
{
    use SerializesDates;
    use Reportable;
    use HasTags;
    use GeneratesPublicKey;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['setup', 'avg_rating', 'rating_count', 'has_thumbnail', 'views', 'has_team', 'published'];

    protected $hidden = ['id', 'author_id', 'dungeon_id', 'faction_id', 'team_id', 'unlisted', 'demo',
                         'killzones', 'faction', 'pageviews', 'specializations', 'races', 'classes', 'ratings',
                         'created_at', 'updated_at', 'expires_at', 'thumbnail_refresh_queued_at', 'thumbnail_updated_at',
                         'published_at', 'published_state_id', 'published_state'];

    protected $fillable = ['enemy_forces'];

    protected $with = ['faction', 'specializations', 'classes', 'races', 'affixes'];

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'public_key';
    }

    /**
     * @return BelongsTo
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo('App\Models\Dungeon');
    }

    /**
     * @return HasMany
     */
    public function brushlines(): HasMany
    {
        return $this->hasMany('App\Models\Brushline');
    }

    /**
     * @return HasMany
     */
    public function paths(): HasMany
    {
        return $this->hasMany('App\Models\Path');
    }

    /**
     * @return BelongsTo
     */
    public function faction(): BelongsTo
    {
        return $this->belongsTo('App\Models\Faction');
    }

    /**
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    /**
     * @return BelongsToMany
     */
    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\CharacterClassSpecialization', 'dungeon_route_player_specializations');
    }

    /**
     * @return HasMany
     */
    public function playerspecializations(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerSpecialization');
    }

    /**
     * @return HasMany
     */
    public function routeattributesraw(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRouteAttribute');
    }

    /**
     * @return BelongsToMany
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\CharacterClass', 'dungeon_route_player_classes');
    }

    /**
     * @return HasMany
     */
    public function playerclasses(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerClass');
    }

    /**
     * @return BelongsToMany
     */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\CharacterRace', 'dungeon_route_player_races');
    }

    /**
     * @return HasMany
     */
    public function playerraces(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRoutePlayerRace');
    }

    /**
     * @return HasMany
     */
    public function affixgroups(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRouteAffixGroup');
    }

    /**
     * @return BelongsToMany
     */
    public function affixes(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\AffixGroup\AffixGroup', 'dungeon_route_affix_groups');
    }

    /**
     * @return HasMany
     */
    public function killzones(): HasMany
    {
        return $this->hasMany('App\Models\KillZone')->orderBy('index');
    }

    /**
     * @return HasMany
     */
    public function pridefulenemies(): HasMany
    {
        return $this->hasMany('App\Models\Enemies\PridefulEnemy');
    }

    /**
     * @return BelongsTo
     */
    public function publishedstate(): BelongsTo
    {
        return $this->belongsTo('App\Models\PublishedState', 'published_state_id');
    }

    /**
     * @return HasMany
     */
    public function ratings(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRouteRating');
    }

    /**
     * @return HasMany
     */
    public function favorites(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRouteFavorite');
    }

    /**
     * @return HasMany
     */
    public function livesessions(): HasMany
    {
        return $this->hasMany('App\Models\LiveSession');
    }

    /**
     * @return HasMany
     */
    public function enemyraidmarkers(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRouteEnemyRaidMarker');
    }

    /**
     * @return HasMany
     */
    public function mapicons(): HasMany
    {
        return $this->hasMany('App\Models\MapIcon');
    }

    /**
     * @return BelongsToMany
     */
    public function routeattributes(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\RouteAttribute', 'dungeon_route_attributes');
    }

    /**
     * @return HasMany
     */
    public function pageviews(): HasMany
    {
        return $this->hasMany('App\Models\PageView', 'model_id')->where('model_class', get_class($this));
    }

    /**
     * @return HasMany
     */
    public function mdtImport(): HasMany
    {
        // Only set if the route was imported through an MDT string
        return $this->hasMany('App\Models\MDTImport');
    }

    /**
     * @return BelongsTo
     */
    function team(): BelongsTo
    {
        return $this->belongsTo('App\Models\Team');
    }

    /**
     * @return HasMany
     */
    public function tagsteam(): HasMany
    {
        return $this->tags(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM]);
    }

    /**
     * @return HasMany
     */
    public function tagspersonal(): HasMany
    {
        return $this->tags(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL]);
    }

    /**
     * Scope a query to only include dungeon routes that are set in sandbox mode.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsSandbox(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at');
    }

    /**
     * Scope a query to only include active dungeons and non-demo routes.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeVisible($query): Builder
    {
        return $query->where('demo', false)
            ->whereHas('dungeon', function ($dungeon) {
                /** @var $dungeon Dungeon This uses the ActiveScope from the Dungeon; dungeon must be active for the route to show up */
                $dungeon->active();
            });
    }

    /**
     * @return string
     */
    public function getPublishedAttribute(): string
    {
        return $this->publishedState->name;
    }

    /**
     * @return bool
     */
    public function getHasTeamAttribute(): bool
    {
        return $this->team_id !== null;
    }

    /**
     * @return float
     */
    public function getAvgRatingAttribute(): float
    {
        $avg = 1;
        if (!$this->ratings->isEmpty()) {
            /** @var Collection $ratings */
            $ratingsArr = $this->ratings->pluck(['rating'])->toArray();

            $avg = array_sum($ratingsArr) / count($ratingsArr);
        }

        return round($avg, 2);
    }

    /**
     * @return int
     */
    public function getViewsAttribute(): int
    {
        return $this->pageviews->count();
    }

    /**
     * @return integer
     */
    public function getRatingCountAttribute(): int
    {
        return $this->ratings->count();
    }

    /**
     * @return bool
     */
    public function getHasThumbnailAttribute(): bool
    {
        return Carbon::createFromTimeString($this->thumbnail_updated_at)->diffInYears(Carbon::now()) === 0;
    }

    /**
     * Gets the current amount of enemy forces that have been targeted for killing in this dungeon route.
     * @return int
     * @noinspection UnknownColumnInspection
     */
    public function getEnemyForces(): int
    {
        $result = 0;

        // May not exist in case of MDT import
        if ($this->exists) {
            $result = DB::select('
                select dungeon_routes.id,
                   CAST(IFNULL(
                           IF(dungeon_routes.teeming = 1,
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override_teeming >= 0,
                                              enemies.enemy_forces_override_teeming,
                                              IF(
                                                  npcs.enemy_forces_teeming >= 0,
                                                  npcs.enemy_forces_teeming,
                                                  IF(
                                                      enemies.seasonal_type = "shrouded",
                                                      6,
                                                      IF(
                                                          enemies.seasonal_type = "shrouded_zul_gamux",
                                                          18,
                                                          npcs.enemy_forces
                                                      )
                                                  )
                                              )
                                          )
                                  ),
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override >= 0,
                                              enemies.enemy_forces_override,
                                              IF(
                                                  enemies.seasonal_type = "shrouded",
                                                  6,
                                                  IF(
                                                      enemies.seasonal_type = "shrouded_zul_gamux",
                                                      18,
                                                      npcs.enemy_forces
                                                  )
                                              )
                                          )
                                  )
                               ), 0
                       ) AS SIGNED) as enemy_forces
            from `dungeon_routes`
                     left join `kill_zones` on `kill_zones`.`dungeon_route_id` = `dungeon_routes`.`id`
                     left join `kill_zone_enemies` on `kill_zone_enemies`.`kill_zone_id` = `kill_zones`.`id`
                     left join `enemies` on `enemies`.`id` = `kill_zone_enemies`.`enemy_id`
                     left join `npcs` on `npcs`.`id` = `enemies`.`npc_id`
                     left join `dungeons` on `dungeons`.`id` = `dungeon_routes`.`dungeon_id`
                where `dungeon_routes`.id = :id
            group by `dungeon_routes`.id
            ', ['id' => $this->id]);

            $result = $result[0]->enemy_forces;
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getEnemyForcesTooMuch(): int
    {
        return max(0, $this->enemy_forces - ($this->teeming ? $this->dungeon->enemy_forces_required_teeming : $this->dungeon->enemy_forces_required));
    }

    /**
     * @return array The setup as used in the front-end.
     */
    public function getSetupAttribute(): array
    {
        return [
            'faction'         => $this->faction,
            'specializations' => $this->specializations,
            'classes'         => $this->classes,
            'races'           => $this->races,
        ];
    }

    /**
     * @param User|null $user
     *
     * @return bool
     */
    public function mayUserView(?User $user): bool
    {
        $result = false;
        switch ($this->publishedState->name) {
            case PublishedState::UNPUBLISHED:
                $result = $this->mayUserEdit($user);
                break;
            case PublishedState::TEAM:
                $result = ($this->team !== null && $this->team->isUserMember($user)) || ($user !== null && $user->hasRole('admin'));
                break;
            case PublishedState::WORLD_WITH_LINK:
            case PublishedState::WORLD:
                $result = true;
                break;
        }

        return $result;
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public function mayUserEdit(?User $user): bool
    {
        if ($user === null) {
            return $this->isSandbox();
        } else {
            return $this->isOwnedByUser($user) || $this->isSandbox() || $user->hasRole('admin') ||
                // Route is part of a team, user is a collaborator, and route is not unpublished
                ($this->team !== null && $this->team->isUserCollaborator($user) && $this->publishedState->name !== PublishedState::UNPUBLISHED);
        }
    }

    /**
     * If this dungeon is in sandbox mode, have a specific user claim this route as theirs.
     *
     * @param int $userId
     * @return bool
     */
    public function claim(int $userId): bool
    {
        if ($result = $this->isSandbox()) {
            $this->author_id  = $userId;
            $this->expires_at = null;
            $this->save();
        }
        return $result;
    }

    /**
     * @return bool True if this route is in sandbox mode, false if it is not.
     */
    public function isSandbox(): bool
    {
        return $this->expires_at !== null;
    }

    /**
     * @param DungeonRouteTemporaryFormRequest $request
     * @param SeasonServiceInterface $seasonService
     * @param ExpansionServiceInterface $expansionService
     * @return bool
     * @throws Exception
     */
    public function saveTemporaryFromRequest(DungeonRouteTemporaryFormRequest $request, SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService): bool
    {
        $this->author_id  = Auth::id() ?? -1;
        $this->public_key = DungeonRoute::generateRandomPublicKey();

        $this->dungeon_id = (int)$request->get('dungeon_id', $this->dungeon_id);

        $this->faction_id     = 1;
        $this->difficulty     = 1;
        $this->seasonal_index = 0;
        $this->teeming        = 0;

        $this->pull_gradient              = '';
        $this->pull_gradient_apply_always = 0;

        $this->title      = __('models.dungeonroute.title_temporary_route', ['dungeonName' => __($this->dungeon->name)]);
        $this->expires_at = Carbon::now()->addHours(config('keystoneguru.sandbox_dungeon_route_expires_hours'))->toDateTimeString();

        $saveResult = $this->save();
        if ($saveResult) {
            $this->ensureAffixGroup($seasonService, $expansionService);
        }

        return $saveResult;
    }

    /**
     * Saves this DungeonRoute with information from the passed Request.
     *
     * @param Request $request
     * @param SeasonServiceInterface $seasonService
     * @param ExpansionServiceInterface $expansionService
     * @param ThumbnailServiceInterface $thumbnailService
     * @return bool
     * @throws Exception
     */
    public function saveFromRequest(Request $request, SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService, ThumbnailServiceInterface $thumbnailService): bool
    {
        $result = false;

        // Overwrite the author_id if it's not been set yet
        $new = !isset($this->id);
        if ($new) {
            $this->author_id  = Auth::id() ?? -1;
            $this->public_key = DungeonRoute::generateRandomPublicKey();
        }

        $this->dungeon_id  = (int)$request->get('dungeon_id', $this->dungeon_id);
        $teamIdFromRequest = (int)$request->get('team_id', $this->team_id);
        $this->team_id     = $teamIdFromRequest > 0 ? $teamIdFromRequest : null;

        $this->faction_id = (int)$request->get('faction_id', $this->faction_id);
        // If it was empty just set Unspecified instead
        $this->faction_id = empty($this->faction_id) ? 1 : $this->faction_id;
        //$this->difficulty = $request->get('difficulty', $this->difficulty);
        $this->difficulty     = 1;
        $this->seasonal_index = (int)$request->get('seasonal_index', [$this->seasonal_index])[0];
        $this->teeming        = 0; // (int)$request->get('teeming', $this->teeming) ?? 0;

        $this->pull_gradient              = $request->get('pull_gradient', '');
        $this->pull_gradient_apply_always = (int)$request->get('pull_gradient_apply_always', 0);

        // Sandbox routes have some fixed properties
        // Fetch the title if the user set anything
        $this->title       = $request->get('dungeon_route_title', $this->title);
        $this->description = $request->get('dungeon_route_description', $this->description) ?? '';
        if (empty($this->title)) {
            $this->title = __($this->dungeon->name);
        }

        $this->level_min = $request->get('level_min', config('keystoneguru.levels.min'));
        $this->level_max = $request->get('level_max', config('keystoneguru.levels.max'));

        if (User::findOrFail(Auth::id())->hasRole('admin')) {
            $this->demo = intval($request->get('demo', 0)) > 0;
        }

        // Remove all loaded relations - we have changed some IDs so the values should be re-fetched
        $this->unsetRelations();


        // Update or insert it
        if ($this->save()) {
            $newAttributes = $request->get('attributes', []);
            if (!empty($newAttributes)) {
                // Remove old attributes
                $this->routeattributesraw()->delete();
                foreach ($newAttributes as $value) {
                    // Only if they exist
                    if (RouteAttribute::where('id', $value)->exists()) {
                        DungeonRouteAttribute::create([
                            'dungeon_route_id'   => $this->id,
                            'route_attribute_id' => $value,
                        ]);
                    }
                }
            }

            $newSpecs = $request->get('specialization', []);
            if (!empty($newSpecs)) {
                // Remove old specializations
                $this->playerspecializations()->delete();
                foreach ($newSpecs as $value) {
                    // Only if they exist
                    if (CharacterClassSpecialization::where('id', $value)->exists()) {
                        DungeonRoutePlayerSpecialization::create([
                            'dungeon_route_id'                  => $this->id,
                            'character_class_specialization_id' => (int)$value,
                        ]);
                    }
                }
            }

            $newClasses = $request->get('class', []);
            if (!empty($newClasses)) {
                // Remove old classes
                $this->playerclasses()->delete();
                foreach ($newClasses as $value) {
                    if (CharacterClass::where('id', $value)->exists()) {
                        DungeonRoutePlayerClass::create([
                            'dungeon_route_id'   => $this->id,
                            'character_class_id' => (int)$value,
                        ]);
                    }
                }
            }

            $newRaces = $request->get('race', []);
            if (!empty($newRaces)) {
                // Remove old races
                $this->playerraces()->delete();

                // We don't _really_ care if this doesn't get saved properly, they can just set it again when editing.
                foreach ($newRaces as $value) {
                    DungeonRoutePlayerRace::create([
                        'dungeon_route_id'  => $this->id,
                        'character_race_id' => (int)$value,
                    ]);
                }
            }

            $newAffixes = $request->get('route_select_affixes', []);
            if (!empty($newAffixes)) {
                // Remove old affixgroups
                $this->affixgroups()->delete();

                foreach ($newAffixes as $value) {
                    // Check disabled to support dungeons not being tied to expansions but to seasons instead.
                    // Impact is that people could assign affixes to routes that don't make sense if they edit the request, meh w/e
                    // Skip any affixes that don't exist, and don't match our current expansion
                    // if (!AffixGroup::where('id', $value)->where('expansion_id', $this->dungeon->expansion_id)->exists()) {
                    //     continue;
                    // }

                    /** @var AffixGroup $affixGroup */
                    $affixGroup = AffixGroup::find($value);

                    // Do not add affixes that do not belong to our Teeming selection
                    if (($affixGroup->id > 0 && $this->teeming != $affixGroup->hasAffix(Affix::AFFIX_TEEMING))) {
                        continue;
                    }

                    DungeonRouteAffixGroup::create([
                        'dungeon_route_id' => $this->id,
                        'affix_group_id'   => $affixGroup->id,
                    ]);
                }

                // Reload the affixes relation
                $this->load('affixes');
            } else if ($new) {
                $this->ensureAffixGroup($seasonService, $expansionService);
            }

            // Instantly generate a placeholder thumbnail for new routes.
            if ($new) {
                $thumbnailService->queueThumbnailRefresh($this);

                // If the user requested a template route..
                if ($request->get('template', false)) {
                    // Check if there's a route that we can use as a template..
                    $templateRoute = DungeonRoute::where('demo', true)
                        ->where('dungeon_id', $this->dungeon_id)
                        ->where('teeming', $this->teeming)
                        ->first();

                    // Only if the route was found!
                    if ($templateRoute !== null) {
                        // Clone its innards to this route
                        $templateRoute->cloneRelationsInto($this, [
                            $templateRoute->paths,
                            $templateRoute->brushlines,
                            $templateRoute->killzones,
                            $templateRoute->enemyraidmarkers,
                            $templateRoute->mapicons,
                        ]);
                    }
                }
            }

            $result = true;
        }

        return $result;
    }

    /**
     *  Clones this route into another route, adding all of our killzones, drawables etc etc to it.
     *
     * @param ThumbnailServiceInterface $thumbnailService
     * @param bool $unpublished
     * @return DungeonRoute The newly cloned route.
     */
    public function cloneRoute(ThumbnailServiceInterface $thumbnailService, bool $unpublished = true): self
    {
        // Must save the new route first
        $dungeonroute                     = new DungeonRoute();
        $dungeonroute->public_key         = DungeonRoute::generateRandomPublicKey();
        $dungeonroute->clone_of           = $this->public_key;
        $dungeonroute->author_id          = Auth::id();
        $dungeonroute->dungeon_id         = $this->dungeon_id;
        $dungeonroute->faction_id         = $this->faction_id;
        $dungeonroute->published_state_id = $unpublished ? PublishedState::ALL[PublishedState::UNPUBLISHED] : $this->published_state_id;
        // Do not clone team_id; user assigns the team himself
        $dungeonroute->team_id        = null;
        $dungeonroute->title          = __('models.dungeonroute.title_clone', ['routeTitle' => $this->title]);
        $dungeonroute->seasonal_index = $this->seasonal_index;
        $dungeonroute->teeming        = $this->teeming;
        $dungeonroute->enemy_forces   = $this->enemy_forces;
        $dungeonroute->level_min      = $this->level_min;
        $dungeonroute->level_max      = $this->level_max;

        $dungeonroute->save();

        // Clone the relations of this route into the new route.
        $this->cloneRelationsInto($dungeonroute, [
            $this->playerraces,
            $this->playerclasses,
            $this->affixgroups,
            $this->paths,
            $this->brushlines,
            $this->killzones,
            $this->pridefulenemies,
            $this->enemyraidmarkers,
            $this->mapicons,
            $this->routeattributesraw,
        ]);

        // Copy the thumbnails to this newly cloned route
        if ($thumbnailService->copyThumbnails($this, $dungeonroute)) {
            $dungeonroute->update([
                'thumbnail_refresh_queued_at' => $this->thumbnail_refresh_queued_at,
                'thumbnail_updated_at'        => $this->thumbnail_updated_at,
            ]);
        }

        return $dungeonroute;
    }

    /**
     * Clone relations of this dungeonroute into another dungeon route.
     * @param $dungeonroute DungeonRoute The RECEIVER of the target $relations
     * @param $relations array The relations that you want to clone.
     */
    public function cloneRelationsInto(DungeonRoute $dungeonroute, array $relations)
    {
        // Link all relations to their new dungeon route
        foreach ($relations as $relation) {
            foreach ($relation as $model) {
                /** @var $model Model */
                $model->id               = 0;
                $model->exists           = false;
                $model->dungeon_route_id = $dungeonroute->id;
                $model->save();

                // KillZone, save the enemies that were attached to them
                if ($model instanceof KillZone) {
                    foreach ($model->killzoneenemies as $enemy) {
                        $enemy->id           = 0;
                        $enemy->exists       = false;
                        $enemy->kill_zone_id = $model->id;
                        $enemy->save();
                    }
                } // Make sure all polylines are copied over
                else if (isset($model->polyline_id)) {
                    // It's not technically a brushline, but all other polyline using structs have the same auto complete
                    // Save a new polyline
                    /** @var Brushline $model */
                    $model->polyline->id       = 0;
                    $model->polyline->exists   = false;
                    $model->polyline->model_id = $model->id;
                    $model->polyline->save();

                    // Write the polyline back to the model
                    $model->polyline_id = $model->polyline->id;
                    $model->save();
                }
            }
        }
    }

    /**
     * @param ExpansionServiceInterface $expansionService
     * @param string $seasonalType
     * @return bool
     */
    public function migrateToSeasonalType(ExpansionServiceInterface $expansionService, string $seasonalType): bool
    {
        // Remove all seasonal type enemies that were assigned to pulls before
        foreach ($this->killzones as $killZone) {
            foreach ($killZone->killzoneenemies as $kzEnemy) {
                if ($kzEnemy->enemy === null || in_array($kzEnemy->enemy->seasonal_type, [Enemy::SEASONAL_TYPE_PRIDEFUL, Enemy::SEASONAL_TYPE_TORMENTED, Enemy::SEASONAL_TYPE_ENCRYPTED])) {
                    $kzEnemy->delete();
                }
            }
        }

        // Remove all affixes of the route
        $this->affixgroups()->delete();

        $currentAffixGroup = $expansionService->getCurrentAffixGroup($this->dungeon->expansion, GameServerRegion::getUserOrDefaultRegion());

        if ($currentAffixGroup !== null) {
            // Add the current affix to the route (user will need to change this anyways)
            DungeonRouteAffixGroup::create([
                'dungeon_route_id' => $this->id,
                'affix_group_id'   => $currentAffixGroup->id,
            ]);
        }

        // If we kill a pack that contains enemies with the new seasonal type, we must assign these enemies to the pulls as well
        $checkedEnemyPacks = collect();
        foreach ($this->killzones as $killZone) {
            foreach ($killZone->enemies as $enemy) {
                // Just in case the mapping was changed since then
                if ($enemy === null) {
                    continue;
                }

                $enemy->load('enemypack');

                $enemyPackId = $enemy->enemy_pack_id;

                if ($enemyPackId > 0 && !$checkedEnemyPacks->contains($enemyPackId) && $enemy->enemypack !== null) {
                    foreach ($enemy->enemypack->getEnemiesWithSeasonalType($seasonalType) as $seasonalTypeEnemy) {
                        KillZoneEnemy::create([
                            'enemy_id'     => $seasonalTypeEnemy->id,
                            'kill_zone_id' => $killZone->id,
                        ]);
                    }
                    $checkedEnemyPacks->push($enemyPackId);
                }
            }
        }

        return true;
    }

    /**
     * @return float|bool Gets the rating the current user (whoever is logged in atm) has given this dungeon route.
     */
    public function getRatingByCurrentUser()
    {
        $result = false;
        $user   = Auth::user();
        if ($user !== null) {
            $rating = DB::table('dungeon_route_ratings')
                ->where('dungeon_route_id', '=', $this->id)
                ->where('user_id', '=', $user->id)
                ->get(['rating'])->first();

            if ($rating !== null) {
                $result = $rating->rating;
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isFavoritedByCurrentUser(): bool
    {
        // Use relationship caching instead of favorites() to save some queries
        return Auth::check() && $this->favorites()->where('user_id', Auth::id())->exists();
    }


    /**
     * @param null $user
     * @return bool
     */
    public function isOwnedByUser($user = null): bool
    {
        // Can't have a function as a default value
        if ($user === null) {
            $user = Auth::user();
        }

        return $user !== null && $this->author_id === $user->id;
    }

    /**
     * Checks if this dungeon route kills a specific enemy or not.
     *
     * @param int $enemyId
     * @return bool
     */
    public function isEnemyKilled(int $enemyId): bool
    {
        $result = false;

        foreach ($this->killzones as $killZone) {
            if ($killZone->enemies->filter(function ($enemy) use ($enemyId) {
                return $enemy->id === $enemyId;
            })->isNotEmpty()) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Checks if this route has killed all required enemies.
     *
     * @return bool
     */
    public function hasKilledAllRequiredEnemies(): bool
    {
        $result = true;

        foreach ($this->dungeon->enemies as $enemy) {
            if ($enemy->required &&
                ($enemy->teeming === null || ($enemy->teeming === 'visible' && $this->teeming) || ($enemy->teeming === 'invisible' && $this->teeming))) {

                if (!$this->isEnemyKilled($enemy->id)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $affix
     * @return bool
     */
    public function hasUniqueAffix(string $affix): bool
    {
        return $this->affixes->filter(function (AffixGroup $affixGroup) use ($affix) {
            return $affixGroup->hasAffix($affix);
        })->isNotEmpty();
    }

    /**
     * Returns a single affix group from the list of affix groups attached to this dungeon route and returns the most relevant
     * one based on what the current affix is. By default will return the first affix group.
     * @return AffixGroup|null
     */
    public function getMostRelevantAffixGroup(): ?AffixGroup
    {
        $seasonService = App::make(SeasonService::class);
        return $seasonService->getCurrentSeason()->getCurrentAffixGroup();

//        $result = null;
//
//        if ($this->affixgroups->isNotEmpty()) {
//            $result = $this->affixgroups->first;
//
//            /** @var SeasonService $seasonService */
//            $seasonService     = App::make(SeasonService::class);
//            $currentAffixGroup = $seasonService->getCurrentSeason()->getCurrentAffixGroup()->id;
//
//            foreach ($this->affixgroups as $affixgroup) {
//                if ($affixgroup->id === $currentAffixGroup->id) {
//                    $result = $affixgroup;
//                    break;
//                }
//            }
//        }
//
//        return $result;
    }

    /**
     * Bit of an ugly way of making a generic function for the subtext, I don't have time to figure out a better solution now
     * @return string
     */
    public function getSubHeaderHtml(): string
    {
        // Only add the 'clone of' when the user cloned it from someone else as a form of credit
        if (isset($model->clone_of) && DungeonRoute::where('public_key', $this->clone_of)->where('author_id', $this->author_id)->count() === 0) {
            $subTitle = __('models.dungeonroute.subtitle_clone_of', [
                'routeLink' => sprintf(
                    ' <a href="%s">%s</a>',
                    route('dungeonroute.view', ['dungeonroute' => $this->clone_of, 'dungeon' => $this->dungeon, 'title' => $this->title]),
                    $this->clone_of
                ),
            ]);
        } else if ($this->demo) {
            if ($this->dungeon->expansion->shortname === Expansion::EXPANSION_BFA) {
                $subTitle = __('models.dungeonroute.permission_dratnos');
            } else if ($this->dungeon->expansion->shortname === Expansion::EXPANSION_SHADOWLANDS) {
                $subTitle = __('models.dungeonroute.permission_petko');
            } else {
                // You made this? I made this.jpg
                $subTitle = '';
            }
        } else if ($this->isSandbox()) {
            $subTitle = __('models.dungeonroute.subtitle_temporary_route');
        } else {
            $subTitle = sprintf(__('models.dungeonroute.subtitle_author'), $this->author->name);
        }

        return $subTitle;
    }

    /**
     * @inheritDoc
     */
    public function touch()
    {
        DungeonRoute::dropCaches($this->id);

        parent::touch();
    }

    /**
     * Creates a missing
     * @param SeasonServiceInterface $seasonService
     * @param ExpansionServiceInterface $expansionService
     * @return void
     * @throws Exception
     */
    private function ensureAffixGroup(SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService)
    {
        if ($this->affixgroups()->count() === 0) {
            // Fallback to the current expansion's
            $activeSeason = $this->dungeon->getActiveSeason($seasonService);

            if ($activeSeason === null) {
                logger()->warning('No active season found for dungeon; fallback on current season', [
                    'dungeonroute' => $this->public_key,
                    'dungeon'      => $this->dungeon->name,
                ]);

                $activeSeason = $seasonService->getCurrentSeason($expansionService->getCurrentExpansion());
            }

            // Make sure this route is at least assigned to an affix so that in the case of claiming we already have an affix which is required
            DungeonRouteAffixGroup::create([
                'affix_group_id'   => optional($activeSeason->getCurrentAffixGroup())->id ?? $activeSeason->affixgroups->first()->id,
                'dungeon_route_id' => $this->id,
            ]);

            // Make sure the relation should be reloaded
            $this->unsetRelation('affixgroups');
        }
    }

    /**
     * Drops any caches associated with this dungeon route
     * @param int $dungeonRouteId
     */
    public static function dropCaches(int $dungeonRouteId)
    {
        try {
            Cache::delete(sprintf('view:dungeonroute_card_0_0_%d', $dungeonRouteId));
            Cache::delete(sprintf('view:dungeonroute_card_0_1_%d', $dungeonRouteId));
            Cache::delete(sprintf('view:dungeonroute_card_1_0_%d', $dungeonRouteId));
            Cache::delete(sprintf('view:dungeonroute_card_1_1_%d', $dungeonRouteId));
        } catch (InvalidArgumentException $e) {
        }
    }


    public static function boot()
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(function ($item) {
            /** @var $item DungeonRoute */

            // Delete thumbnails
            $publicPath = public_path('images/route_thumbnails/');
            foreach ($item->dungeon->floors as $floor) {
                // @ because we don't care if it fails
                @unlink(sprintf('%s/%s_%s.png', $publicPath, $item->public_key, $floor->index));
            }

            // Dungeonroute settings
            $item->affixgroups()->delete();
            $item->routeattributesraw()->delete();
            $item->playerclasses()->delete();
            $item->playerraces()->delete();
            $item->playerspecializations()->delete();
            $item->tags()->delete();

            // Mapping related items
            $item->enemyraidmarkers()->delete();
            $item->brushlines()->delete();
            $item->paths()->delete();
            $item->killzones()->delete();
            $item->mapicons()->delete();
            $item->pridefulenemies()->delete();

            // External
            $item->ratings()->delete();
            $item->favorites()->delete();
            $item->livesessions()->delete();

            $item->mdtImport()->delete();
        });
    }
}
