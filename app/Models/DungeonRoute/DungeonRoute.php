<?php

namespace App\Models\DungeonRoute;

use App\Http\Requests\DungeonRoute\DungeonRouteSubmitTemporaryFormRequest;
use App\Logic\Structs\LatLng;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Brushline;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\Dungeon;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemies\PridefulEnemy;
use App\Models\Enemy;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\Interfaces\ConvertsVerticesInterface;
use App\Models\Interfaces\TracksPageViewInterface;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\MapIcon;
use App\Models\Mapping\MappingVersion;
use App\Models\MDTImport;
use App\Models\PageView;
use App\Models\Path;
use App\Models\PublishedState;
use App\Models\RouteAttribute;
use App\Models\Season;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\Traits\HasMetrics;
use App\Models\Traits\HasTags;
use App\Models\Traits\Reportable;
use App\Models\Traits\SerializesDates;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\DungeonRoute\ThumbnailService;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @property int                                     $id
 * @property string                                  $public_key
 * @property int                                     $author_id
 * @property int                                     $dungeon_id
 * @property int                                     $mapping_version_id
 * @property int                                     $season_id
 * @property int                                     $faction_id
 * @property int|null                                $team_id
 * @property int                                     $published_state_id
 * @property string                                  $clone_of
 * @property string                                  $title
 * @property string                                  $description
 * @property int|null                                $level_min
 * @property int|null                                $level_max
 * @property string                                  $difficulty
 * @property int                                     $seasonal_index
 * @property int                                     $enemy_forces
 * @property bool                                    $teeming
 * @property bool                                    $demo
 * @property array                                   $setup Attribute
 * @property bool                                    $has_thumbnail Attribute
 * @property string                                  $pull_gradient
 * @property bool                                    $pull_gradient_apply_always
 * @property int                                     $dungeon_difficulty
 * @property int                                     $views
 * @property int                                     $views_embed
 * @property int                                     $popularity
 * @property float                                   $rating
 * @property int                                     $rating_count
 * @property Carbon                                  $thumbnail_refresh_queued_at
 * @property Carbon                                  $thumbnail_updated_at
 * @property Carbon                                  $updated_at
 * @property Carbon                                  $created_at
 * @property Carbon                                  $published_at
 * @property Carbon                                  $expires_at
 *
 * @property MappingVersion                          $mappingVersion
 * @property Dungeon                                 $dungeon
 * @property Path                                    $route
 * @property Season|null                             $season
 * @property Faction                                 $faction
 * @property User|null                               $author Can be null in case of temporary route
 * @property MDTImport                               $mdtImport
 * @property Team                                    $team
 * @property PublishedState                          $publishedState
 * @property ChallengeModeRun|null                   $challengeModeRun Is only set if route is created through API
 *
 * @property Collection                              $specializations
 * @property Collection                              $classes
 * @property Collection                              $races
 * @property Collection                              $playerspecializations
 * @property Collection                              $playerclasses
 * @property Collection                              $playerraces
 * @property Collection<AffixGroup>                  $affixes
 * @property Collection<DungeonRouteAffixGroup>      $affixGroups
 * @property Collection<DungeonRouteRating>          $ratings
 * @property Collection<DungeonRouteFavorite>        $favorites
 * @property Collection<LiveSession>                 $livesessions
 * @property Collection<Brushline>                   $brushlines
 * @property Collection<Path>                        $paths
 * @property Collection<KillZone>                    $killZones
 * @property Collection<PridefulEnemy>               $pridefulEnemies
 * @property Collection<OverpulledEnemy>             $overpulledenemies
 * @property Collection<DungeonRouteEnemyRaidMarker> $enemyRaidMarkers
 * @property Collection<MapIcon>                     $mapicons
 * @property Collection<PageView>                    $pageviews
 * @property Collection<Tag>                         $tags
 * @property Collection<RouteAttribute>              $routeattributes
 * @property Collection<DungeonRouteAttribute>       $routeattributesraw
 * @property Collection<DungeonRouteThumbnailJob>    $dungeonRouteThumbnailJobs
 *
 * @method static Builder visible()
 * @method static Builder visibleWithUnlisted()
 *
 * @mixin Eloquent
 */
class DungeonRoute extends Model implements TracksPageViewInterface
{
    use GeneratesPublicKey;
    use HasFactory;
    use HasMetrics;
    use HasTags;
    use Reportable;
    use SerializesDates;

    public const PAGE_VIEW_SOURCE_VIEW_ROUTE = 1;

    public const PAGE_VIEW_SOURCE_VIEW_EMBED = 2;

    public const PAGE_VIEW_SOURCE_PRESENT_ROUTE = 3;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['setup', 'has_thumbnail', 'png_thumbnails', 'has_team', 'published'];

    protected $hidden = [
        'id',
        'author_id',
        'dungeon_id',
        'faction_id',
        'team_id',
        'unlisted',
        'demo',
        'killZones',
        'faction',
        'pageviews',
        'specializations',
        'races',
        'classes',
        'ratings',
        'created_at',
        'updated_at',
        'expires_at',
        'thumbnail_refresh_queued_at',
        'thumbnail_updated_at',
        'published_at',
        'published_state_id',
        'published_state',
    ];

    protected $fillable = [
        'id',
        'public_key',
        'author_id',
        'dungeon_id',
        'mapping_version_id',
        'season_id',
        'faction_id',
        'published_state_id',
        'teeming',
        'title',
        'difficulty',
        'level_min',
        'level_max',
        'expires_at',
        'enemy_forces',
        'views',
        'views_embed',
        'popularity',
    ];

    protected $with = [
        'mappingVersion',
        'dungeon',
        'season',
        'faction',
        'specializations',
        'classes',
        'races',
        'affixes',
    ];

    protected $casts = [
        'thumbnail_refresh_queued_at' => 'date',
        'thumbnail_updated_at'        => 'date',
        'published_at'                => 'date',
        'expires_at'                  => 'date',
        'created_at'                  => 'date',
        'updated_at'                  => 'date',
        'enemy_forces'                => 'integer',
        'demo'                        => 'integer',
        'level_min'                   => 'integer',
        'level_max'                   => 'integer',
        'rating'                      => 'float',
    ];

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    public function getRouteKeyName(): string
    {
        return 'public_key';
    }

    /**
     * @return array The setup as used in the front-end.
     */
    public function getSetupAttribute(): array
    {
        // Telescope has an issue where somehow it doesn't have these relations loaded and causes crashes
        $this->load(['faction', 'specializations', 'classes', 'races']);

        return [
            'faction'         => $this->faction,
            'specializations' => $this->specializations,
            'classes'         => $this->classes,
            'races'           => $this->races,
        ];
    }

    public function getTitleSlug(): string
    {
        // IF the title is somehow not set, we return the dungeon title, slugged
        return Str::slug(
            empty($this->title) ? __($this->dungeon->name, [], 'en_US') : $this->title,
            '-',
            null
        );
    }

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function brushlines(): HasMany
    {
        return $this->hasMany(Brushline::class)->orderBy('id');
    }

    public function paths(): HasMany
    {
        return $this->hasMany(Path::class)->orderBy('id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }


    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(CharacterClassSpecialization::class, 'dungeon_route_player_specializations');
    }

    public function playerspecializations(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerSpecialization::class);
    }

    public function routeattributesraw(): HasMany
    {
        return $this->hasMany(DungeonRouteAttribute::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(CharacterClass::class, 'dungeon_route_player_classes');
    }

    public function playerclasses(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerClass::class);
    }

    public function races(): BelongsToMany
    {
        return $this->belongsToMany(CharacterRace::class, 'dungeon_route_player_races');
    }

    public function playerraces(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerRace::class);
    }

    public function affixGroups(): HasMany
    {
        return $this->hasMany(DungeonRouteAffixGroup::class);
    }

    public function affixes(): BelongsToMany
    {
        return $this->belongsToMany(AffixGroup::class, 'dungeon_route_affix_groups');
    }

    public function killZones(): HasMany
    {
        return $this->hasMany(KillZone::class)->orderBy('index');
    }

    public function pridefulEnemies(): HasMany
    {
        return $this->hasMany(PridefulEnemy::class);
    }

    public function publishedState(): BelongsTo
    {
        return $this->belongsTo(PublishedState::class);
    }

    /**
     * @throws Exception
     */
    public function challengeModeRun(): BelongsTo
    {
        throw new Exception('Not implemented');
        // This doesn't work because it's on a different connection - strange stuff
        //        return $this->setConnection('combatlog')->belongsTo(ChallengeModeRun::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(DungeonRouteRating::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(DungeonRouteFavorite::class);
    }

    public function livesessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }

    public function enemyRaidMarkers(): HasMany
    {
        return $this->hasMany(DungeonRouteEnemyRaidMarker::class);
    }

    public function dungeonRouteThumbnailJobs(): HasMany
    {
        return $this->hasMany(DungeonRouteThumbnailJob::class);
    }

    public function mapicons(): HasMany
    {
        return $this->hasMany(MapIcon::class)
            ->when($this->team_id !== null, function ($query) {
                $query->orWhere(function ($query) {
                    $query->where('map_icons.team_id', $this->team_id)
                        ->whereIn('map_icons.floor_id', $this->dungeon->floors->pluck('id'));
                });
            });
    }

    public function routeattributes(): BelongsToMany
    {
        return $this->belongsToMany(RouteAttribute::class, 'dungeon_route_attributes');
    }

    public function pageviews(): HasMany
    {
        return $this->hasMany(PageView::class, 'model_id')->where('model_class', static::class);
    }

    public function mdtImport(): HasMany
    {
        // Only set if the route was imported through an MDT string
        return $this->hasMany(MDTImport::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function tagsteam(): HasMany
    {
        return $this->tags(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM]);
    }

    public function tagspersonal(): HasMany
    {
        return $this->tags(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL]);
    }

    private function convertVerticesForFacade(
        CoordinatesServiceInterface $coordinatesService,
        ConvertsVerticesInterface   $hasVertices,
        Floor                       $floor
    ): Floor {
        $convertedLatLngs = collect();

        foreach ($hasVertices->getDecodedLatLngs($floor) as $latLng) {
            $convertedLatLngs->push($coordinatesService->convertMapLocationToFacadeMapLocation(
                $this->mappingVersion,
                $latLng
            ));
        }

        $newFloor = isset($convertedLatLngs[0]) ? $convertedLatLngs[0]->getFloor() : $floor;

        $hasVertices->vertices_json = json_encode($convertedLatLngs->map(static fn(LatLng $latLng) => $latLng->toArray()));

        return $newFloor;
    }

    public function mapContextKillZones(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<KillZone> $killZones */
        $killZones = $this->killZones()
            ->with(['floor'])
            ->get();

        if ($useFacade) {
            foreach ($killZones as $killZone) {
                // If no kill zone was set, skip the conversion step
                if (!$killZone->hasValidLatLng()) {
                    continue;
                }

                $convertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this->mappingVersion,
                    $killZone->getLatLng()
                );

                $killZone->setLatLng($convertedLatLng);
            }
        }

        return $killZones;
    }

    public function mapContextMapIcons(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<MapIcon> $mapIcons */
        $mapIcons = $this->mapicons()
            ->with(['floor'])
            ->get();

        if ($useFacade) {
            foreach ($mapIcons as $mapIcon) {
                $convertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this->mappingVersion,
                    $mapIcon->getLatLng()
                );

                $mapIcon->setLatLng($convertedLatLng);
            }
        }

        return $mapIcons;
    }

    public function mapContextBrushlines(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<Brushline> $brushlines */
        $brushlines = $this->brushlines()->with(['floor'])->get();

        if ($useFacade) {
            $brushlines = $brushlines
                // #2177 Sometimes brushlines don't have a polyline
                ->filter(static fn(Brushline $brushline) => $brushline->polyline !== null)->map(function (Brushline $brushline) use ($coordinatesService) {
                    $newFloor = $this->convertVerticesForFacade($coordinatesService, $brushline->polyline, $brushline->floor);
                    $brushline->setRelation('floor', $newFloor);
                    $brushline->floor_id = $newFloor->id;

                    return $brushline;
                });
        }

        return $brushlines;
    }

    public function mapContextPaths(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<Brushline> $paths */
        $paths = $this->paths()->with(['floor'])->get();

        if ($useFacade) {
            $paths = $paths
                // #2177 Sometimes paths don't have a polyline
                ->filter(static fn(Path $path) => $path->polyline !== null)
                ->map(function (Path $path) use ($coordinatesService) {
                    $newFloor = $this->convertVerticesForFacade($coordinatesService, $path->polyline, $path->floor);
                    $path->setRelation('floor', $newFloor);
                    $path->floor_id = $newFloor->id;

                    return $path;
                });
        }

        return $paths;
    }

    public function getChallengeModeRun(): ?ChallengeModeRun
    {
        return ChallengeModeRun::where('dungeon_route_id', $this->id)->first();
    }

    /**
     * Scope a query to only include dungeon routes that are set in sandbox mode.
     */
    public function scopeIsSandbox(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at');
    }

    /**
     * Scope a query to only include active dungeons and non-demo routes.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('demo', false)
            ->whereHas('dungeon', static function ($dungeon) {
                /** @var $dungeon Dungeon This uses the ActiveScope from the Dungeon; dungeon must be active for the route to show up */
                $dungeon->active();
            });
    }

    public function getPublishedAttribute(): string
    {
        return array_search($this->published_state_id, PublishedState::ALL, true);
    }

    public function getHasTeamAttribute(): bool
    {
        return $this->team_id !== null;
    }

    public function updateRating(): float
    {
        $avg   = round($this->ratings()->avg('rating') ?? 0, 2);
        $count = $this->ratings()->count();

        $this->update([
            'rating'       => $avg,
            'rating_count' => $count,
        ]);

        return $avg;
    }

    public function getHasThumbnailAttribute(): bool
    {
        return Carbon::createFromTimeString($this->thumbnail_updated_at)->diffInYears(Carbon::now()) === 0;
    }

    public function getPngThumbnailsAttribute(): bool
    {
        // A bit of a hack but it works, it's complicated otherwise
        return Carbon::createFromTimeString($this->thumbnail_updated_at)->isBefore(
            Carbon::createFromDate(2024, 02, 05)->setTime(21, 13)
        );
    }

    /**
     * Gets the current amount of enemy forces that have been targeted for killing in this dungeon route.
     *
     * @noinspection UnknownColumnInspection
     */
    public function getEnemyForces(): int
    {
        $result = 0;

        // May not exist in case of MDT import
        if ($this->exists) {
            $isShrouded = $this->getSeasonalAffix() === Affix::AFFIX_SHROUDED;

            // Ignore the shrouded query if we're not shrouded (make it fail)
            $ifIsShroudedEnemyForcesQuery = $isShrouded ? '
                IF(
                    enemies.seasonal_type = "shrouded",
                    mapping_versions.enemy_forces_shrouded,
                    IF(
                        enemies.seasonal_type = "shrouded_zul_gamux",
                        mapping_versions.enemy_forces_shrouded_zul_gamux,
                        npc_enemy_forces.enemy_forces
                    )
                )
            ' : 'npc_enemy_forces.enemy_forces';

            $ifIsShroudedJoins = $isShrouded ? '
                left join `mapping_versions` on `mapping_versions`.`id` = `dungeon_routes`.`mapping_version_id`
            ' : '';

            // This produces a list of enemies with their enemy forces. This also does not count duplicate enemies across
            // the same or multiple pulls twice. This may have been introduced with migration to mapping versions but idk
            $queryResult = DB::select("
                select dungeon_routes.id,
               CAST(
                   CAST(IFNULL(
                           IF(dungeon_routes.teeming = 1,
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override_teeming IS NOT NULL,
                                              enemies.enemy_forces_override_teeming,
                                              IF(
                                                  npc_enemy_forces.enemy_forces_teeming IS NOT NULL,
                                                  npc_enemy_forces.enemy_forces_teeming,
                                                  {$ifIsShroudedEnemyForcesQuery}
                                              )
                                          )
                                  ),
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override IS NOT NULL,
                                              enemies.enemy_forces_override,
                                              {$ifIsShroudedEnemyForcesQuery}
                                          )
                                  )
                               ), 0
                       ) AS SIGNED)  / COUNT(concat(`kill_zone_enemies`.`npc_id`, `kill_zone_enemies`.`mdt_id`)) AS SIGNED) as enemy_forces
            from `dungeon_routes`
                     left join `dungeons` on `dungeons`.`id` = `dungeon_routes`.`dungeon_id`
                     left join `kill_zones` on `kill_zones`.`dungeon_route_id` = `dungeon_routes`.`id`
                     left join `kill_zone_enemies` on `kill_zone_enemies`.`kill_zone_id` = `kill_zones`.`id`
                     left join `enemies` on coalesce(`enemies`.`mdt_npc_id`, `enemies`.`npc_id`) = `kill_zone_enemies`.`npc_id`
                        AND `enemies`.`mdt_id` = `kill_zone_enemies`.`mdt_id`
                        AND `enemies`.`mapping_version_id` = `dungeon_routes`.`mapping_version_id`
                     left join `npcs` on `npcs`.`id` = `kill_zone_enemies`.`npc_id`
                     left join `npc_enemy_forces` on `npcs`.`id` = `npc_enemy_forces`.`npc_id` AND `dungeon_routes`.`mapping_version_id` = `npc_enemy_forces`.`mapping_version_id`
                     {$ifIsShroudedJoins}
                where `dungeon_routes`.id = :id
                    AND `enemies`.`mapping_version_id` = `dungeon_routes`.`mapping_version_id`
            group by `dungeon_routes`.id, concat(`kill_zone_enemies`.`npc_id`, `kill_zone_enemies`.`mdt_id`)
            ", ['id' => $this->id]);

            // Could be if no enemies were assigned yet
            if (!empty($queryResult)) {
                foreach ($queryResult as $row) {
                    $result += $row->enemy_forces;
                }
            }
        }

        return $result;
    }

    public function getEnemyForcesPercentage(): int
    {
        if ($this->mappingVersion->enemy_forces_required > 0) {
            return (int)(($this->enemy_forces / $this->mappingVersion->enemy_forces_required) * 100);
        } else {
            return 0;
        }
    }

    public function getEnemyForcesTooMuch(): int
    {
        return max(0,
            $this->enemy_forces - ($this->teeming ? $this->mappingVersion->enemy_forces_required_teeming : $this->mappingVersion->enemy_forces_required));
    }

    public function mayUserView(?User $user): bool
    {
        $result = false;
        $result = match ($this->published_state_id) {
            PublishedState::ALL[PublishedState::UNPUBLISHED] => $this->mayUserEdit($user),
            PublishedState::ALL[PublishedState::TEAM] => ($this->team !== null && $this->team->isUserMember($user)) || ($user !== null && $user->hasRole(Role::ROLE_ADMIN)),
            PublishedState::ALL[PublishedState::WORLD_WITH_LINK], PublishedState::ALL[PublishedState::WORLD] => true,
            default => $result,
        };

        return $result;
    }

    public function mayUserEdit(?User $user): bool
    {
        if ($user === null) {
            return $this->isSandbox();
        } else {
            return $this->isOwnedByUser($user) || $this->isSandbox() || $user->hasRole(Role::ROLE_ADMIN) ||
                // Route is part of a team, user is a collaborator, and route is not unpublished
                (
                    $this->team !== null &&
                    $this->team->isUserCollaborator($user) &&
                    $this->published_state_id !== PublishedState::ALL[PublishedState::UNPUBLISHED]
                );
        }
    }

    /**
     * If this dungeon is in sandbox mode, have a specific user claim this route as theirs.
     */
    public function claim(int $userId): bool
    {
        if ($result = $this->isSandbox()) {
            $this->update([
                'author_id'  => $userId,
                'expires_at' => null,
            ]);
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
     * @throws Exception
     */
    public function saveTemporaryFromRequest(
        DungeonRouteSubmitTemporaryFormRequest $request,
        SeasonServiceInterface                 $seasonService,
        ExpansionServiceInterface              $expansionService
    ): bool {
        $this->author_id  = Auth::id() ?? -1;
        $this->public_key = DungeonRoute::generateRandomPublicKey();

        $this->dungeon_id         = (int)$request->get('dungeon_id', $this->dungeon_id);
        $dungeon                  = Dungeon::findOrFail($this->dungeon_id);
        $this->mapping_version_id = $dungeon->currentMappingVersion->id;

        $activeSeason = $seasonService->getCurrentSeason(
            $expansionService->getCurrentExpansion(GameServerRegion::getUserOrDefaultRegion())
        ) ?? $seasonService->getMostRecentSeasonForDungeon($dungeon);
        // Can still be null if there are no seasons for this dungeon, like in Classic
        $this->season_id = $activeSeason->id ?? null;

        $this->faction_id = 1;
//        $this->difficulty     = 1;
        $this->seasonal_index = 0;
        $this->teeming        = 0;

        $this->pull_gradient              = '';
        $this->pull_gradient_apply_always = 0;

        $this->dungeon_difficulty = $request->get('dungeon_difficulty');

        $this->title = __('models.dungeonroute.title_temporary_route', ['dungeonName' => __($this->dungeon->name)]);

        $dungeonRouteLevel      = $request->get('dungeon_route_level');
        $dungeonRouteLevelParts = explode(';', (string)$dungeonRouteLevel);
        $this->level_min        = $dungeonRouteLevelParts[0] ?? null;
        $this->level_max        = $dungeonRouteLevelParts[1] ?? null;

        if ($this->level_min === null || $this->level_max === null) {
            $this->level_min = $this->level_min ?? $activeSeason->key_level_min;
            $this->level_max = $this->level_max ?? $activeSeason->key_level_max;
        }
        if ($this->level_min !== null) {
            $this->level_min = (int)$this->level_min;
        }
        if ($this->level_max !== null) {
            $this->level_max = (int)$this->level_max;
        }

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
     *
     * @throws Exception
     */
    public function saveFromRequest(
        Request                   $request,
        SeasonServiceInterface    $seasonService,
        ExpansionServiceInterface $expansionService,
        ThumbnailServiceInterface $thumbnailService
    ): bool {
        $result = false;

        // Overwrite the author_id if it's not been set yet
        $new = !isset($this->id);
        /** @var User|null $user */
        $user = Auth::user();

        $this->dungeon_id = (int)$request->get('dungeon_id', $this->dungeon_id);
        if ($new) {
            $this->author_id  = $user?->id ?? -1;
            $this->public_key = DungeonRoute::generateRandomPublicKey();
            $this->setRelation('dungeon', Dungeon::findOrFail($this->dungeon_id));
            $this->mapping_version_id = $this->dungeon->currentMappingVersion->id;
        }

        $teamIdFromRequest = (int)$request->get('team_id', $this->team_id);
        $this->team_id     = $teamIdFromRequest > 0 ? $teamIdFromRequest : null;

        $this->faction_id = (int)$request->get('faction_id', $this->faction_id);
        // If it was empty just set Unspecified instead
        $this->faction_id = empty($this->faction_id) ? 1 : $this->faction_id;

        $activeSeason = $seasonService->getUpcomingSeasonForDungeon($this->dungeon) ??
            $seasonService->getMostRecentSeasonForDungeon($this->dungeon);
        // Can still be null if there are no seasons for this dungeon, like in Classic
        $this->season_id = $activeSeason->id ?? null;

        //$this->difficulty = $request->get('difficulty', $this->difficulty);
//        $this->difficulty     = 1;
        $this->seasonal_index = (int)$request->get('seasonal_index', [$this->seasonal_index])[0];
        $this->teeming        = 0; // (int)$request->get('teeming', $this->teeming) ?? 0;

        $this->pull_gradient              = $request->get('pull_gradient', '');
        $this->pull_gradient_apply_always = (int)$request->get('pull_gradient_apply_always', 0);

        // Sandbox routes have some fixed properties
        // Fetch the title if the user set anything
        $this->title       = $request->get('dungeon_route_title', $this->title);
        $this->description = $request->get('dungeon_route_description', $this->description) ?? '';
        // Title slug CAN resolve to empty if they're just using special characters only
        if (empty($this->title) || empty($this->getTitleSlug())) {
            $this->title = __($this->dungeon->name);
        }

        $dungeonRouteLevel      = $request->get('dungeon_route_level');
        $dungeonRouteLevelParts = explode(';', (string)$dungeonRouteLevel);
        $this->level_min        = $dungeonRouteLevelParts[0] ?? null;
        $this->level_max        = $dungeonRouteLevelParts[1] ?? null;

        if ($this->level_min === null || $this->level_max === null) {
            $this->level_min = $this->level_min ?? $activeSeason->key_level_min;
            $this->level_max = $this->level_max ?? $activeSeason->key_level_max;
        }
        if ($this->level_min !== null) {
            $this->level_min = (int)$this->level_min;
        }
        if ($this->level_max !== null) {
            $this->level_max = (int)$this->level_max;
        }

        if ($user?->hasRole(Role::ROLE_ADMIN)) {
            $this->demo = intval($request->get('demo', 0)) > 0;
        }

        $this->dungeon_difficulty = $request->get('dungeon_difficulty', null);

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

                $dungeonActiveSeason = $this->dungeon->getActiveSeason($seasonService);

                if ($dungeonActiveSeason === null) {
                    $this->ensureAffixGroup($seasonService, $expansionService);
                } else {
                    foreach ($newAffixes as $value) {
                        $value = (int)$value;

                        if ($dungeonActiveSeason->affixGroups->filter(static fn(AffixGroup $affixGroup) => $affixGroup->id === $value)->isEmpty()) {
                            // Attempted to assign an affix that the dungeon cannot have - abort it
                            continue;
                        }

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
                    $templateRoute?->cloneRelationsInto($this, [
                        $templateRoute->paths,
                        $templateRoute->brushlines,
                        $templateRoute->killZones,
                        $templateRoute->enemyRaidMarkers,
                        $templateRoute->mapicons,
                    ]);
                }
            }

            $result = true;
        }

        return $result;
    }

    /**
     *  Clones this route into another route, adding all of our killzones, drawables etc etc to it.
     *
     *
     * @return DungeonRoute The newly cloned route.
     */
    public function cloneRoute(ThumbnailServiceInterface $thumbnailService, bool $unpublished = true): self
    {
        // Must save the new route first
        $dungeonroute = DungeonRoute::create([
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'clone_of'           => $this->public_key,
            'author_id'          => Auth::id(),
            'dungeon_id'         => $this->dungeon_id,
            'mapping_version_id' => $this->mapping_version_id,
            'season_id'          => $this->season_id,
            'faction_id'         => $this->faction_id,
            'published_state_id' => $unpublished ? PublishedState::ALL[PublishedState::UNPUBLISHED] : $this->published_state_id,

            // Do not clone team_id, user assigns the team himself
            'team_id'            => null,
            'title'              => __('models.dungeonroute.title_clone', ['routeTitle' => $this->title]),
            'seasonal_index'     => $this->seasonal_index,
            'teeming'            => $this->teeming,
            'enemy_forces'       => $this->enemy_forces,
            'level_min'          => $this->level_min,
            'level_max'          => $this->level_max,
        ]);

        // Clone the relations of this route into the new route.
        $this->cloneRelationsInto($dungeonroute, [
            $this->playerraces,
            $this->playerclasses,
            $this->affixGroups,
            $this->paths,
            $this->brushlines,
            $this->killZones,
            $this->pridefulEnemies,
            $this->enemyRaidMarkers,
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
     *
     * @param DungeonRoute $dungeonroute The RECEIVER of the target $relations
     * @param array        $relations The relations that you want to clone.
     */
    public function cloneRelationsInto(DungeonRoute $dungeonroute, array $relations): void
    {
        // Link all relations to their new dungeon route
        foreach ($relations as $relation) {
            foreach ($relation as $model) {
                // We have to load the enemies before we re-assign the ID - this is no longer done lazily for us
                if ($model instanceof KillZone) {
                    $model->load(['killZoneEnemies']);
                }

                /** @var $model Model */
                $model->id               = 0;
                $model->exists           = false;
                $model->dungeon_route_id = $dungeonroute->id;
                $model->save();

                // KillZone, save the enemies that were attached to them
                if ($model instanceof KillZone) {
                    foreach ($model->killZoneEnemies as $killZoneEnemy) {
                        $killZoneEnemy->id           = 0;
                        $killZoneEnemy->exists       = false;
                        $killZoneEnemy->kill_zone_id = $model->id;
                        $killZoneEnemy->save();
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

    public function migrateToSeasonalType(ExpansionServiceInterface $expansionService, string $seasonalType): bool
    {
        // Remove all seasonal type enemies that were assigned to pulls before
        foreach ($this->killZones as $killZone) {
            // We have to load the enemies before we re-assign the ID - this is no longer done lazily for us
            $killZone->load(['killZoneEnemies']);

            foreach ($killZone->killZoneEnemies as $kzEnemy) {
                if ($kzEnemy->enemy === null || in_array($kzEnemy->enemy->seasonal_type, [
                        Enemy::SEASONAL_TYPE_PRIDEFUL,
                        Enemy::SEASONAL_TYPE_TORMENTED,
                        Enemy::SEASONAL_TYPE_ENCRYPTED,
                        // Do not include these as they are not new enemies but are a seasonal type on existing enemies
                        // Enemy::SEASONAL_TYPE_SHROUDED,
                        // Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX,
                    ])) {
                    $kzEnemy->delete();
                }
            }
        }

        $gameServerRegion = GameServerRegion::getUserOrDefaultRegion();

        // Remove all affixes of the route
        $this->affixgroups()->delete();

        $seasonOfSeasonalType = null;
        $currentAffixGroup    = null;
        // Only if the seasonal type was valid and we could find the corresponding affix
        $seasonalTypeAffix = Affix::getAffixBySeasonalType($seasonalType);
        if ($seasonalTypeAffix !== null) {
            /** @var Season $seasonOfSeasonalType */
            $seasonOfSeasonalType = Season::where('seasonal_affix_id', Affix::ALL[$seasonalTypeAffix])->first();

            if ($seasonOfSeasonalType !== null) {
                try {
                    $currentAffixGroup = $seasonOfSeasonalType->getCurrentAffixGroupInRegion($gameServerRegion);
                } catch (Exception) {
                    // It's okay - we can recover in the next IF
                    logger()->error('Unable to find current affixgroup for seasonal type', [
                        'season'       => $seasonOfSeasonalType->id,
                        'seasonalType' => $seasonalType,
                    ]);
                }
            }
        }

        if ($currentAffixGroup === null) {
            // Backup - grab the current affix group of the expansion
            $currentAffixGroup = $expansionService->getCurrentAffixGroup($this->dungeon->expansion, $gameServerRegion)
                // Last ditch attempt
                ?? $expansionService->getCurrentAffixGroup($expansionService->getCurrentExpansion($gameServerRegion), $gameServerRegion);
        }

        if ($currentAffixGroup !== null) {
            // Add the current affix to the route (user will need to change this anyways)
            DungeonRouteAffixGroup::create([
                'dungeon_route_id' => $this->id,
                'affix_group_id'   => $currentAffixGroup->id,
            ]);
        }

        // If we kill a pack that contains enemies with the new seasonal type, we must assign these enemies to the pulls as well
        $checkedEnemyPacks = collect();
        foreach ($this->killZones as $killZone) {
            foreach ($killZone->getEnemies() as $enemy) {
                // Just in case the mapping was changed since then
                if ($enemy === null) {
                    continue;
                }

                $enemy->load('enemypack');

                $enemyPackId = $enemy->enemy_pack_id;

                if ($enemyPackId !== null && !$checkedEnemyPacks->contains($enemyPackId) && $enemy->enemyPack !== null) {
                    // Get any new enemies in this pack that have the seasonal type we're migrating to
                    foreach ($enemy->enemyPack->getEnemiesWithSeasonalType($seasonalType) as $seasonalTypeEnemy) {
                        // But only create new enemies if these enemies are new to the pack
                        if ($killZone->getEnemies()->filter(static fn(Enemy $enemy) => $enemy->id === $seasonalTypeEnemy->id)->isEmpty()) {
                            KillZoneEnemy::create([
                                'enemy_id'     => $seasonalTypeEnemy->id,
                                'kill_zone_id' => $killZone->id,
                            ]);
                        }
                    }

                    $checkedEnemyPacks->push($enemyPackId);
                }
            }
        }

        // Reset the affixes so that the enemy forces calculation goes right
        $this->load('affixes');

        $this->update(['enemy_forces' => $this->getEnemyForces()]);

        return true;
    }

    /**
     * @return float|null Gets the rating the current user (whoever is logged in atm) has given this dungeon route.
     */
    public function getRatingByCurrentUser(): ?float
    {
        $result = null;
        /** @var User $user */
        $user = Auth::user();
        if ($user !== null) {
            $result = DungeonRouteRating::where('dungeon_route_id', $this->id)
                ->where('user_id', $user->id)
                ->first()?->rating;
        }

        return $result;
    }

    public function isFavoritedByCurrentUser(): bool
    {
        // Use relationship caching instead of favorites() to save some queries
        return Auth::check() && $this->favorites()->where('user_id', Auth::id())->exists();
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public function isOwnedByUser(?User $user = null): bool
    {
        // Can't have a function as a default value
        if ($user === null) {
            $user = Auth::user();
        }

        return $user !== null && $this->author_id === $user->id;
    }

    /**
     * Checks if this dungeon route kills a specific enemy or not.
     */
    public function isEnemyKilled(int $enemyId): bool
    {
        $result = false;

        foreach ($this->killZones as $killZone) {
            if ($killZone->getEnemies()->filter(static fn($enemy) => $enemy->id === $enemyId)->isNotEmpty()) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Checks if this route has killed all required enemies.
     */
    public function hasKilledAllRequiredEnemies(): bool
    {
        $result = true;

        //        foreach ($this->dungeon->enemies as $enemy) {
        //            if ($enemy->required &&
        //                ($enemy->teeming === null || ($enemy->teeming === 'visible' && $this->teeming) || ($enemy->teeming === 'invisible' && $this->teeming))) {
        //
        //                if (!$this->isEnemyKilled($enemy->id)) {
        //                    $result = false;
        //                    break;
        //                }
        //            }
        //        }

        return $result;
    }

    public function hasUniqueAffix(string $affix): bool
    {
        return $this->affixes->filter(static fn(AffixGroup $affixGroup) => $affixGroup->hasAffix($affix))->isNotEmpty();
    }

    /**
     * Based on the assigned affixes, determine the season that this route was most likely created in
     *
     * @return Season|null
     */
    public function getSeasonFromAffixes(): ?Season
    {
        /** @var AffixGroup|null $affixGroup */
        $affixGroup = $this->affixes->first();

        return $affixGroup?->load('season')?->season;
    }

    public function getDominantAffix(): ?string
    {
        $fortifiedCount = $tyrannicalCount = 0;
        if (in_array($this->season_id, [Season::SEASON_TWW_S1, Season::SEASON_TWW_S2, Season::SEASON_TWW_S3, Season::SEASON_TWW_S4])) {
            foreach ($this->affixes as $affixGroup) {
                // Look at the 2nd affix - this is what people are going to be focussed on mostly!
                // These affix groups have both fortified and tyrannical so just look at the one that comes first
                /** @var Affix $affix */
                $affix = $affixGroup->affixes->get(1);
                if ($affix->key === Affix::AFFIX_FORTIFIED) {
                    $fortifiedCount++;
                } else if ($affix->key === Affix::AFFIX_TYRANNICAL) {
                    $tyrannicalCount++;
                }
            }
        } else {
            // These seasons either contain fortified or tyrannical, not both
            foreach ($this->affixes as $affixGroup) {
                if ($affixGroup->hasAffix(Affix::AFFIX_FORTIFIED)) {
                    $fortifiedCount++;
                } else if ($affixGroup->hasAffix(Affix::AFFIX_TYRANNICAL)) {
                    $tyrannicalCount++;
                }
            }
        }

        if ($fortifiedCount > $tyrannicalCount) {
            return Affix::AFFIX_FORTIFIED;
        } else if ($tyrannicalCount > $fortifiedCount) {
            return Affix::AFFIX_TYRANNICAL;
        } else {
            // No real dominant affix found!
            return null;
        }
    }

    public function getSeasonalAffix(): ?string
    {
        $foundSeasonalAffix = null;

        $this->affixes->each(static function (AffixGroup $affixGroup) use (&$foundSeasonalAffix) {
            foreach (Affix::SEASONAL_AFFIXES as $seasonalAffix) {
                if ($affixGroup->hasAffix($seasonalAffix)) {
                    $foundSeasonalAffix = $seasonalAffix;

                    return false; // break
                }
            }

            return true;
        });

        return $foundSeasonalAffix;
    }

    /**
     * Returns a single affix group from the list of affix groups attached to this dungeon route and returns the most relevant
     * one based on what the current affix is. By default, will return the first affix group.
     *
     * @throws Exception
     */
    public function getMostRelevantAffixGroup(): ?AffixGroup
    {
        /** @var AffixGroup|null $result */
        $result = null;

        if ($this->dungeon->gameVersion->has_seasons && $this->affixes->isNotEmpty()) {
            $result = $this->affixes->first();

            /** @var SeasonService $seasonService */
            $seasonService     = App::make(SeasonServiceInterface::class);
            $currentAffixGroup = $seasonService->getCurrentSeason()->getCurrentAffixGroup();

            if ($currentAffixGroup !== null) {
                foreach ($this->affixes as $affixGroup) {
                    if ($affixGroup->id === $currentAffixGroup->id) {
                        $result = $affixGroup;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Bit of an ugly way of making a generic function for the subtext, I don't have time to figure out a better solution now
     */
    public function getSubHeaderHtml(): string
    {
        // Only add the 'clone of' when the user cloned it from someone else as a form of credit
        if (isset($model->clone_of) && DungeonRoute::where('public_key', $this->clone_of)->where('author_id', $this->author_id)->count() === 0) {
            $subTitle = __('models.dungeonroute.subtitle_clone_of', [
                // Can't use %s for the href since PhpStorm then complains >.>
                'routeLink' => sprintf(
                    ' <a href="' .
                    route('dungeonroute.view', ['dungeonroute' => $this->clone_of, 'dungeon' => $this->dungeon, 'title' => $this->title]) .
                    '">%s</a>',
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

    public function getThumbnailUrl(int $floorIndex): string
    {
        return url($this->getRelativeThumbnailPath($floorIndex));
    }

    public function getRelativeThumbnailPath(int $floorIndex): string
    {
        $relativePath    = sprintf('%s/%s_%s.jpg', ThumbnailService::THUMBNAIL_FOLDER_PATH, $this->public_key, $floorIndex);
        $relativePathPng = str_replace('.jpg', '.png', $relativePath);

        $publicPath = public_path($relativePath);
        // If we don't have a .jpg file, check if we should use .png instead

        if (!file_exists($publicPath)) {
            $publicPathPng = str_replace('.jpg', '.png', $publicPath);
            if (file_exists($publicPathPng)) {
                $relativePath = $relativePathPng;
            }
        }

        return $relativePath;
    }

    public function getAbsoluteThumbnailPath(int $floorIndex): string
    {
        return public_path($this->getRelativeThumbnailPath($floorIndex));
    }

    public function trackPageView(int $source): bool
    {
        // Handle route views counting
        if ($result = PageView::trackPageView($this->id, DungeonRoute::class, $source)) {
            // Do not update the updated_at time - triggering a refresh of the thumbnails
            $this->timestamps = false;
            if ($source === self::PAGE_VIEW_SOURCE_VIEW_ROUTE) {
                $this->views++;
            }

            if ($source === self::PAGE_VIEW_SOURCE_VIEW_EMBED) {
                $this->views_embed++;
            }

            $this->update(['views', 'views_embed']);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function touch($attribute = null): void
    {
        DungeonRoute::dropCaches($this->id);

        parent::touch($attribute);
    }

    /**
     * Ensure we have an affix group at all times
     *
     *
     *
     * @throws Exception
     */
    private function ensureAffixGroup(SeasonServiceInterface $seasonService, ExpansionServiceInterface $expansionService): void
    {
        if ($this->affixgroups()->count() === 0) {
            // Fallback to the current expansion's
            $activeSeason = $this->dungeon->getActiveSeason($seasonService);

            if ($activeSeason === null) {
                //                logger()->warning('No active season found for dungeon; fallback on current season', [
                //                    'dungeonroute' => $this->public_key,
                //                    'dungeon'      => $this->dungeon->name,
                //                ]);

                $activeSeason = $seasonService->getCurrentSeason(
                    $expansionService->getCurrentExpansion(GameServerRegion::getUserOrDefaultRegion())
                );
            }

            // Make sure this route is at least assigned to an affix so that in the case of claiming we already have an affix which is required
            DungeonRouteAffixGroup::create([
                'affix_group_id'   => $activeSeason->getCurrentAffixGroup()?->id ?? $activeSeason->affixGroups->first()->id,
                'dungeon_route_id' => $this->id,
            ]);

            // Make sure the relation should be reloaded
            $this->unsetRelation('affixGroups');
        }
    }

    /**
     * Drops any caches associated with this dungeon route.
     */
    public static function dropCaches(int $dungeonRouteId): void
    {
        try {
            // This can be better - but it's fine if you're using it to drop caches for 1 route.
            $orientations = ['vertical', 'horizontal'];
            $locales      = language()->allowed();
            $showAffixes  = [0, 1];
            $showDungeon  = [0, 1];
            $isAdmin      = [0, 1];

            foreach ($orientations as $orientation) {
                foreach ($locales as $code => $name) {
                    foreach ($showAffixes as $showAffix) {
                        foreach ($showDungeon as $showDungeonImage) {
                            foreach ($isAdmin as $admin) {
                                Cache::delete(self::getCardCacheKey($dungeonRouteId, $orientation, $code, $showAffix, $showDungeonImage, $admin));
                            }
                        }
                    }
                }
            }
        } catch (InvalidArgumentException) {
        }
    }

    public static function getCardCacheKey(int $dungeonRouteId, string $orientation, string $locale, int $showAffixes, int $showDungeonImage, int $isAdmin): string
    {
        return sprintf(
            'view:dungeonroute_card:%s:%s_%d_%d_%d_%d',
            $orientation,
            $locale,
            $showAffixes,
            $showDungeonImage,
            $isAdmin,
            $dungeonRouteId
        );
    }

    protected static function boot(): void
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(static function (DungeonRoute $dungeonRoute) {
            $dungeonRoute->load(['dungeonRouteThumbnailJobs', 'brushlines', 'paths', 'killZones', 'livesessions']);

            // Delete thumbnails
            foreach ($dungeonRoute->dungeon->floors as $floor) {
                // @ because we don't care if it fails
                @unlink($dungeonRoute->getAbsoluteThumbnailPath($floor->index));
            }

            // Delete all API thumbnail jobs/thumbnails generated for it
            foreach ($dungeonRoute->dungeonRouteThumbnailJobs as $dungeonRouteThumbnailJob) {
                $dungeonRouteThumbnailJob->expire();
            }

            // Dungeonroute settings
            $dungeonRoute->affixgroups()->delete();
            $dungeonRoute->routeattributesraw()->delete();
            $dungeonRoute->playerclasses()->delete();
            $dungeonRoute->playerraces()->delete();
            $dungeonRoute->playerspecializations()->delete();
            $dungeonRoute->tags()->delete();

            // Mapping related items
            $dungeonRoute->enemyRaidMarkers()->delete();
            foreach ($dungeonRoute->brushlines as $brushline) {
                $brushline->delete();
            }

            foreach ($dungeonRoute->paths as $path) {
                $path->delete();
            }

            foreach ($dungeonRoute->killZones as $killZone) {
                $killZone->delete();
            }

            $dungeonRoute->mapicons()->delete();
            $dungeonRoute->pridefulEnemies()->delete();
            // External
            $dungeonRoute->ratings()->delete();
            $dungeonRoute->favorites()->delete();
            foreach ($dungeonRoute->livesessions as $liveSession) {
                $liveSession->delete();
            }
            $dungeonRoute->mdtImport()->delete();
            $dungeonRoute->metrics()->delete();
            $dungeonRoute->metricAggregations()->delete();
        });
    }
}
