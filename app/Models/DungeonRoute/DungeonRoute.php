<?php

namespace App\Models\DungeonRoute;

use App\Logic\Structs\LatLng;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupCoupling;
use App\Models\Arrow;
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
use App\Models\File;
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
use App\Models\Traits\Reportable;
use App\Models\Traits\SerializesDates;
use App\Models\Traits\Taggable;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Override;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @property int                  $id
 * @property string               $public_key
 * @property int                  $author_id
 * @property int                  $dungeon_id
 * @property int                  $mapping_version_id
 * @property int                  $season_id
 * @property int                  $faction_id
 * @property int|null             $team_id
 * @property int                  $published_state_id
 * @property string|null          $clone_of
 * @property string               $title
 * @property string               $description
 * @property int|null             $level_min
 * @property int|null             $level_max
 * @property string               $difficulty
 * @property int                  $seasonal_index
 * @property int                  $enemy_forces
 * @property bool                 $teeming
 * @property bool                 $demo
 * @property array<string, mixed> $setup                       Attribute
 * @property bool                 $has_thumbnail               Attribute
 * @property int                  $has_enemy_forces            Computed column added by CoverageService::selectRaw()
 * @property string               $pull_gradient
 * @property bool                 $pull_gradient_apply_always
 * @property int                  $dungeon_difficulty
 * @property int                  $views
 * @property int                  $views_embed
 * @property int                  $popularity
 * @property float                $rating
 * @property int                  $rating_count
 * @property Carbon               $thumbnail_refresh_queued_at
 * @property Carbon               $thumbnail_updated_at
 * @property Carbon               $updated_at
 * @property Carbon               $created_at
 * @property Carbon               $published_at
 * @property Carbon|null          $expires_at
 *
 * @property MappingVersion                    $mappingVersion
 * @property Dungeon                           $dungeon
 * @property Path                              $route
 * @property Season|null                       $season
 * @property Faction                           $faction
 * @property User|null                         $author           Can be null in case of temporary route
 * @property MDTImport                         $mdtImport
 * @property Team|null                         $team
 * @property PublishedState                    $publishedState
 * @property DungeonRouteScheduledPublish|null $scheduledPublish
 * @property ChallengeModeRun|null             $challengeModeRun Is only set if route is created through API
 *
 * @property EloquentCollection<int, CharacterClassSpecialization>     $specializations
 * @property EloquentCollection<int, CharacterClass>                   $classes
 * @property EloquentCollection<int, CharacterRace>                    $races
 * @property EloquentCollection<int, DungeonRoutePlayerSpecialization> $playerspecializations
 * @property EloquentCollection<int, DungeonRoutePlayerClass>          $playerclasses
 * @property EloquentCollection<int, DungeonRoutePlayerRace>           $playerraces
 * @property EloquentCollection<int, AffixGroup>                       $affixes
 * @property EloquentCollection<int, DungeonRouteAffixGroup>           $affixGroups
 * @property EloquentCollection<int, DungeonRouteRating>               $ratings
 * @property EloquentCollection<int, DungeonRouteFavorite>             $favorites
 * @property EloquentCollection<int, LiveSession>                      $livesessions
 * @property EloquentCollection<int, Brushline>                        $brushlines
 * @property EloquentCollection<int, Path>                             $paths
 * @property EloquentCollection<int, Arrow>                            $arrows
 * @property EloquentCollection<int, KillZone>                         $killZones
 * @property EloquentCollection<int, PridefulEnemy>                    $pridefulEnemies
 * @property EloquentCollection<int, OverpulledEnemy>                  $overpulledenemies
 * @property EloquentCollection<int, DungeonRouteEnemyRaidMarker>      $enemyRaidMarkers
 * @property EloquentCollection<int, MapIcon>                          $mapicons
 * @property EloquentCollection<int, PageView>                         $pageviews
 * @property EloquentCollection<int, Tag>                              $tags
 * @property EloquentCollection<int, RouteAttribute>                   $routeattributes
 * @property EloquentCollection<int, DungeonRouteAttribute>            $routeattributesraw
 * @property EloquentCollection<int, DungeonRouteThumbnailJob>         $dungeonRouteThumbnailJobs
 * @property EloquentCollection<int, DungeonRouteThumbnail>            $dungeonRouteThumbnails
 * @property EloquentCollection<int, File>                             $thumbnails
 *
 * @method static Builder<self> visible()
 * @method static Builder<self> visibleWithUnlisted()
 *
 * @mixin Eloquent
 */
class DungeonRoute extends Model implements TracksPageViewInterface
{
    use GeneratesPublicKey;
    /** @use HasFactory<\Database\Factories\DungeonRoute\DungeonRouteFactory> */
    use HasFactory;
    use HasMetrics;
    use Taggable;
    use Reportable;
    use SerializesDates;

    public const int PAGE_VIEW_SOURCE_VIEW_ROUTE    = 1;
    public const int PAGE_VIEW_SOURCE_VIEW_EMBED    = 2;
    public const int PAGE_VIEW_SOURCE_PRESENT_ROUTE = 3;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'setup',
        'has_thumbnail',
        'has_team',
        'published',
    ];

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
        'clone_of',
        'author_id',
        'dungeon_id',
        'mapping_version_id',
        'season_id',
        'faction_id',
        'team_id',
        'published_state_id',
        'teeming',
        'title',
        'description',
        'difficulty',
        'seasonal_index',
        'level_min',
        'level_max',
        'expires_at',
        'enemy_forces',
        'views',
        'views_embed',
        'popularity',
        'rating',
        'rating_count',
        'thumbnail_refresh_queued_at',
        'thumbnail_updated_at',
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
        'thumbnails',
    ];

    protected function casts(): array
    {
        return [
            'thumbnail_refresh_queued_at' => 'datetime',
            'thumbnail_updated_at'        => 'datetime',
            'published_at'                => 'datetime',
            'expires_at'                  => 'datetime',
            'created_at'                  => 'datetime',
            'updated_at'                  => 'datetime',
            'enemy_forces'                => 'integer',
            'demo'                        => 'integer',
            'level_min'                   => 'integer',
            'level_max'                   => 'integer',
            'rating'                      => 'float',
        ];
    }

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    #[Override]
    public function getRouteKeyName(): string
    {
        return 'public_key';
    }

    /**
     * @return array<string, mixed> The setup as used in the front-end.
     */
    public function getSetupAttribute(): array
    {
        // Telescope has an issue where somehow it doesn't have these relations loaded and causes crashes
        $this->load([
            'faction',
            'specializations',
            'classes',
            'races',
        ]);

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
            null,
        );
    }

    /** @return BelongsTo<MappingVersion, $this> */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    /** @return BelongsTo<Dungeon, $this> */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /** @return HasMany<Brushline, $this> */
    public function brushlines(): HasMany
    {
        return $this->hasMany(Brushline::class)->orderBy('id');
    }

    /** @return HasMany<Path, $this> */
    public function paths(): HasMany
    {
        return $this->hasMany(Path::class)->orderBy('id');
    }

    /** @return HasMany<Arrow, $this> */
    public function arrows(): HasMany
    {
        return $this->hasMany(Arrow::class)->orderBy('id');
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<Faction, $this> */
    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<CharacterClassSpecialization, $this> */
    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(CharacterClassSpecialization::class, 'dungeon_route_player_specializations');
    }

    /** @return HasMany<DungeonRoutePlayerSpecialization, $this> */
    public function playerspecializations(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerSpecialization::class);
    }

    /** @return HasMany<DungeonRouteAttribute, $this> */
    public function routeattributesraw(): HasMany
    {
        return $this->hasMany(DungeonRouteAttribute::class);
    }

    /** @return BelongsToMany<CharacterClass, $this> */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(CharacterClass::class, 'dungeon_route_player_classes');
    }

    /** @return HasMany<DungeonRoutePlayerClass, $this> */
    public function playerclasses(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerClass::class);
    }

    /** @return BelongsToMany<CharacterRace, $this> */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(CharacterRace::class, 'dungeon_route_player_races');
    }

    /** @return HasMany<DungeonRoutePlayerRace, $this> */
    public function playerraces(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerRace::class);
    }

    /** @return HasMany<DungeonRouteAffixGroup, $this> */
    public function affixGroups(): HasMany
    {
        return $this->hasMany(DungeonRouteAffixGroup::class);
    }

    /** @return BelongsToMany<AffixGroup, $this> */
    public function affixes(): BelongsToMany
    {
        return $this->belongsToMany(AffixGroup::class, 'dungeon_route_affix_groups');
    }

    /** @return HasMany<KillZone, $this> */
    public function killZones(): HasMany
    {
        return $this->hasMany(KillZone::class)->orderBy('index');
    }

    /** @return HasMany<PridefulEnemy, $this> */
    public function pridefulEnemies(): HasMany
    {
        return $this->hasMany(PridefulEnemy::class);
    }

    /** @return BelongsTo<PublishedState, $this> */
    public function publishedState(): BelongsTo
    {
        return $this->belongsTo(PublishedState::class);
    }

    /**
     * WARNING: requires you to set ->setConnection('combatlog') on the model before calling this method!
     * You then also need to call ->setConnection(null) to reset the connection to the default one.
     *
     * @return HasOne<ChallengeModeRun, $this>
     */
    public function challengeModeRun(): HasOne
    {
        return $this->hasOne(ChallengeModeRun::class);
    }

    /** @return HasMany<DungeonRouteRating, $this> */
    public function ratings(): HasMany
    {
        return $this->hasMany(DungeonRouteRating::class);
    }

    /** @return HasMany<DungeonRouteFavorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(DungeonRouteFavorite::class);
    }

    /** @return HasMany<LiveSession, $this> */
    public function livesessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }

    /** @return HasMany<DungeonRouteEnemyRaidMarker, $this> */
    public function enemyRaidMarkers(): HasMany
    {
        return $this->hasMany(DungeonRouteEnemyRaidMarker::class);
    }

    /** @return HasMany<DungeonRouteThumbnailJob, $this> */
    public function dungeonRouteThumbnailJobs(): HasMany
    {
        return $this->hasMany(DungeonRouteThumbnailJob::class);
    }

    /** @return HasMany<DungeonRouteThumbnail, $this> */
    public function dungeonRouteThumbnails(): HasMany
    {
        return $this->hasMany(DungeonRouteThumbnail::class);
    }

    /** @return BelongsToMany<File, $this> */
    public function thumbnails(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'dungeon_route_thumbnails')
            ->where('dungeon_route_thumbnails.custom', false);
    }

    /** @return HasMany<MapIcon, $this> */
    public function mapicons(): HasMany
    {
        /** @var HasMany<MapIcon, $this> $query */
        $query = $this->hasMany(MapIcon::class)
            ->when($this->team_id !== null, function ($query) {
                $query->orWhere(function ($query) {
                    $query->where('map_icons.team_id', $this->team_id)
                        ->whereIn('map_icons.floor_id', $this->dungeon->floors->pluck('id'));
                });
            });

        return $query;
    }

    /** @return BelongsToMany<RouteAttribute, $this> */
    public function routeattributes(): BelongsToMany
    {
        return $this->belongsToMany(RouteAttribute::class, 'dungeon_route_attributes');
    }

    /** @return HasMany<PageView, $this> */
    public function pageviews(): HasMany
    {
        return $this->hasMany(PageView::class, 'model_id')->where('model_class', static::class);
    }

    /** @return HasMany<MDTImport, $this> */
    public function mdtImport(): HasMany
    {
        // Only set if the route was imported through an MDT string
        return $this->hasMany(MDTImport::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return HasOne<DungeonRouteScheduledPublish, $this> */
    public function scheduledPublish(): HasOne
    {
        return $this->hasOne(DungeonRouteScheduledPublish::class);
    }

    /** @return HasMany<Tag, $this> */
    public function tagsteam(): HasMany
    {
        return $this->tags(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM]);
    }

    /** @return HasMany<Tag, $this> */
    public function tagspersonal(): HasMany
    {
        return $this->tags(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL]);
    }

    private function convertVerticesForFacade(
        CoordinatesServiceInterface     $coordinatesService,
        ConvertsVerticesInterface&Model $hasVertices,
        Floor                           $floor,
    ): Floor {
        $convertedLatLngs = collect();

        foreach ($hasVertices->getDecodedLatLngs($floor) as $latLng) {
            $convertedLatLngs->push($coordinatesService->convertMapLocationToFacadeMapLocation(
                $this->mappingVersion,
                $latLng,
            ));
        }

        $newFloor = isset($convertedLatLngs[0]) ? $convertedLatLngs[0]->getFloor() : $floor;

        $hasVertices->setAttribute('vertices_json', json_encode($convertedLatLngs->map(static fn(
            LatLng $latLng,
        ) => $latLng->toArray())));

        return $newFloor;
    }

    /** @return Collection<int, KillZone> */
    public function mapContextKillZones(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<int, KillZone> $killZones */
        $killZones = $this->killZones()
            ->with(['enemies', 'floor'])
            ->get();

        if ($useFacade) {
            foreach ($killZones as $killZone) {
                // If no kill zone was set, skip the conversion step
                if (!$killZone->hasValidLatLng()) {
                    continue;
                }

                $convertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this->mappingVersion,
                    $killZone->getLatLng(),
                );

                $killZone->setLatLng($convertedLatLng);
            }
        }

        return $killZones;
    }

    /** @return Collection<int, MapIcon> */
    public function mapContextMapIcons(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<int, MapIcon> $mapIcons */
        $mapIcons = $this->mapicons()
            ->with(['floor'])
            ->get();

        if ($useFacade) {
            foreach ($mapIcons as $mapIcon) {
                $convertedLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $this->mappingVersion,
                    $mapIcon->getLatLng(),
                );

                $mapIcon->setLatLng($convertedLatLng);
            }
        }

        return $mapIcons;
    }

    /** @return Collection<int, Brushline> */
    public function mapContextBrushlines(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<int, Brushline> $brushlines */
        $brushlines = $this->brushlines()->with(['floor'])->get();

        if ($useFacade) {
            $brushlines = $brushlines
                // #2177 Sometimes brushlines don't have a polyline
                ->filter(static fn(Brushline $brushline) => $brushline->polyline !== null)->map(function (
                    Brushline $brushline,
                ) use ($coordinatesService) {
                    $newFloor = $this->convertVerticesForFacade($coordinatesService, $brushline->polyline, $brushline->floor);
                    $brushline->setRelation('floor', $newFloor);
                    $brushline->floor_id = $newFloor->id;

                    return $brushline;
                });
        }

        return $brushlines;
    }

    /** @return Collection<int, Path> */
    public function mapContextPaths(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<int, Path> $paths */
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

    /** @return Collection<int, Arrow> */
    public function mapContextArrows(CoordinatesServiceInterface $coordinatesService, bool $useFacade): Collection
    {
        /** @var Collection<int, Arrow> $arrows */
        $arrows = $this->arrows()->with(['floor'])->get();

        if ($useFacade) {
            $arrows = $arrows
                ->filter(static fn(Arrow $arrow) => $arrow->polyline !== null)->map(function (
                    Arrow $arrow,
                ) use ($coordinatesService) {
                    $newFloor = $this->convertVerticesForFacade($coordinatesService, $arrow->polyline, $arrow->floor);
                    $arrow->setRelation('floor', $newFloor);
                    $arrow->floor_id = $newFloor->id;

                    return $arrow;
                });
        }

        return $arrows;
    }

    public function getChallengeModeRun(): ?ChallengeModeRun
    {
        return ChallengeModeRun::where('dungeon_route_id', $this->id)->first();
    }

    /**
     * Scope a query to only include dungeon routes that are set in sandbox mode.
     *
     * @param  Builder<self> $query
     * @return Builder<self>
     */
    public function scopeIsSandbox(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at');
    }

    /**
     * Scope a query to only include active dungeons and non-demo routes.
     *
     * @param  Builder<self> $query
     * @return Builder<self>
     */
    #[Scope]
    protected function visible(Builder $query): Builder
    {
        return $query->where('demo', false)
            ->whereHas('dungeon', static function ($dungeon) {
                $dungeon->where('dungeons.active', 1);
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
        return $this->thumbnails->isNotEmpty();
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
            $isShrouded = $this->getSeasonalAffix()?->key === Affix::AFFIX_SHROUDED;

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
        return max(
            0,
            $this->enemy_forces - ($this->teeming ? $this->mappingVersion->enemy_forces_required_teeming : $this->mappingVersion->enemy_forces_required),
        );
    }

    public function mayUserView(?User $user): bool
    {
        $result = false;
        $result = match ($this->published_state_id) {
            PublishedState::ALL[PublishedState::UNPUBLISHED] => $this->mayUserEdit($user),
            PublishedState::ALL[PublishedState::TEAM]        => ($this->team !== null && $this->team->isUserMember($user)) || ($user !== null && $user->hasRole(Role::ROLE_ADMIN)),
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
     * Clone relations of this dungeon route into another dungeon route.
     *
     * @param DungeonRoute $dungeonRoute The RECEIVER of the target $relations
     * @param array        $relations    The relations that you want to clone.
     */
    /**
     * @param array<int, mixed> $relations Collection of relations
     */
    public function cloneRelationsInto(DungeonRoute $dungeonRoute, array $relations): void
    {
        // Link all relations to their new dungeon route
        foreach ($relations as $relation) {
            foreach ($relation as $model) {
                // We have to load the enemies before we re-assign the ID - this is no longer done lazily for us
                if ($model instanceof KillZone) {
                    $model->load([
                        'killZoneEnemies',
                        'killZoneSpells',
                    ]);
                }

                $model->setAttribute($model->getKeyName(), 0);
                $model->exists = false;
                $model->setAttribute('dungeon_route_id', $dungeonRoute->id);
                $model->save();

                // KillZone, save the enemies that were attached to them
                if ($model instanceof KillZone) {
                    foreach ($model->killZoneEnemies as $killZoneEnemy) {
                        $killZoneEnemy->id           = 0;
                        $killZoneEnemy->exists       = false;
                        $killZoneEnemy->kill_zone_id = $model->id;
                        $killZoneEnemy->save();
                    }

                    foreach ($model->killZoneSpells as $killZoneSpell) {
                        $killZoneSpell->id           = 0;
                        $killZoneSpell->exists       = false;
                        $killZoneSpell->kill_zone_id = $model->id;
                        $killZoneSpell->save();
                    }
                } // MapIcon, save the map icons WITHOUT A TEAM, otherwise you duplicate icons in other people's teams
                elseif ($model instanceof MapIcon) {
                    $model->update(['team_id' => null]);
                } // Make sure all polylines are copied over
                elseif (isset($model->polyline_id)) {
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
            /** @var Season|null $seasonOfSeasonalType */
            $seasonOfSeasonalType = Season::where('seasonal_affix_id', Affix::ALL[$seasonalTypeAffix])->first();

            if ($seasonOfSeasonalType !== null) {
                try {
                    $currentAffixGroup = resolve(SeasonAffixGroupServiceInterface::class)->getCurrentAffixGroupInRegion($seasonOfSeasonalType, $gameServerRegion);
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
                if ($enemy === null) { // @phpstan-ignore identical.alwaysFalse
                    continue;
                }

                $enemy->load('enemypack');

                $enemyPackId = $enemy->enemy_pack_id;

                if ($enemyPackId !== null && !$checkedEnemyPacks->contains($enemyPackId) && $enemy->enemyPack !== null) {
                    // Get any new enemies in this pack that have the seasonal type we're migrating to
                    foreach ($enemy->enemyPack->getEnemiesWithSeasonalType($seasonalType) as $seasonalTypeEnemy) {
                        // But only create new enemies if these enemies are new to the pack
                        if ($killZone->getEnemies()->filter(static fn(
                            Enemy $enemy,
                        ) => $enemy->id === $seasonalTypeEnemy->id)->isEmpty()) {
                            KillZoneEnemy::create([
                                'npc_id'       => $seasonalTypeEnemy->mdt_npc_id ?? $seasonalTypeEnemy->npc_id,
                                'mdt_id'       => $seasonalTypeEnemy->mdt_id,
                                'kill_zone_id' => $killZone->id,
                                'enemy_id'     => $seasonalTypeEnemy->id,
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
        /** @var User|null $user */
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
     * @param  User|null $user
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
        if (in_array($this->season_id, [
            Season::SEASON_TWW_S1,
            Season::SEASON_TWW_S2,
            Season::SEASON_TWW_S3,
        ])) {
            foreach ($this->affixes as $affixGroup) {
                // Look at the 2nd affix - this is what people are going to be focused on mostly!
                // These affix groups have both fortified and tyrannical, so just look at the one that comes first
                /** @var Affix $affix */
                $affix = $affixGroup->affixes->get(1);
                if ($affix->key === Affix::AFFIX_FORTIFIED) {
                    $fortifiedCount++;
                } elseif ($affix->key === Affix::AFFIX_TYRANNICAL) {
                    $tyrannicalCount++;
                }
            }
        } else {
            // These seasons either contain fortified or tyrannical, not both
            foreach ($this->affixes as $affixGroup) {
                if ($affixGroup->hasAffix(Affix::AFFIX_FORTIFIED)) {
                    $fortifiedCount++;
                } elseif ($affixGroup->hasAffix(Affix::AFFIX_TYRANNICAL)) {
                    $tyrannicalCount++;
                }
            }
        }

        if ($fortifiedCount > $tyrannicalCount) {
            return Affix::AFFIX_FORTIFIED;
        } elseif ($tyrannicalCount > $fortifiedCount) {
            return Affix::AFFIX_TYRANNICAL;
        } else {
            // No real dominant affix found!
            return null;
        }
    }

    public function getSeasonalAffix(): ?Affix
    {
        /** @var Affix|null $foundSeasonalAffix */
        $foundSeasonalAffix = null;

        // Say that we found the seasonal affix, we're attached to a season and that season is TWW
        // Then we want to display Xal'Atath's Guile when the min level is PAST the min for that affix
        if ($this->affixes->isNotEmpty() && $this->season !== null && $this->season->expansion->shortname === Expansion::EXPANSION_TWW) {
            /** @var AffixGroup $affixGroup */
            $affixGroup = $this->affixes->first();

            /** @var Affix|null $xalAtathsGuile */
            $xalAtathsGuile = $affixGroup->affixes->first(fn(
                Affix $affix,
            ) => $affix->key === Affix::AFFIX_XALATATHS_GUILE);

            if ($xalAtathsGuile !== null) {
                $affixGroup->load(['affixGroupCouplings']);
                $affixCoupling = $affixGroup->affixGroupCouplings->first(fn(
                    AffixGroupCoupling $affix,
                ) => $affix->affix_id === $xalAtathsGuile->id);

                if ($affixCoupling !== null && $this->level_min >= $affixCoupling->key_level) {
                    $foundSeasonalAffix = $xalAtathsGuile;
                }
            }
        } // If the above didn't find anything, we don't care for the result. The seasonal affixes of TWW dungeons are
        // not relevant to the routes anymore - we only want to display Guile really.
        else {
            $this->affixes->each(static function (AffixGroup $affixGroup) use (&$foundSeasonalAffix) {
                foreach (Affix::SEASONAL_AFFIXES as $seasonalAffix) {
                    $foundSeasonalAffix ??= $affixGroup->affixes->first(fn(Affix $affix) => $affix->key === $seasonalAffix);
                }

                return true;
            });
        }

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

        if ($this->dungeon->hasMappingVersionWithSeasons() && $this->affixes->isNotEmpty()) {
            $result = $this->affixes->first();

            /** @var SeasonService $seasonService */
            $seasonService     = App::make(SeasonServiceInterface::class);
            $currentSeason     = $seasonService->getCurrentSeason();
            $currentAffixGroup = $currentSeason !== null ? resolve(SeasonAffixGroupServiceInterface::class)->getCurrentAffixGroup($currentSeason) : null;

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
        if ($this->clone_of !== null && DungeonRoute::where('public_key', $this->clone_of)->where('author_id', $this->author_id)->count() === 0) {
            $subTitle = __('models.dungeonroute.subtitle_clone_of', [
                // Can't use %s for the href since PhpStorm then complains >.>
                'routeLink' => sprintf(
                    ' <a href="' .
                    route('dungeonroute.view', [
                        'dungeonroute' => $this->clone_of,
                        'dungeon'      => $this->dungeon,
                        'title'        => $this->title,
                    ]) .
                    '">%s</a>',
                    $this->clone_of,
                ),
            ]);
        } elseif ($this->demo) {
            if ($this->dungeon->expansion->shortname === Expansion::EXPANSION_BFA) {
                $subTitle = __('models.dungeonroute.permission_dratnos');
            } elseif ($this->dungeon->expansion->shortname === Expansion::EXPANSION_SHADOWLANDS) {
                $subTitle = __('models.dungeonroute.permission_petko');
            } else {
                // You made this? I made this.jpg
                $subTitle = '';
            }
        } elseif ($this->isSandbox()) {
            $subTitle = __('models.dungeonroute.subtitle_temporary_route');
        } else {
            $subTitle = sprintf(__('models.dungeonroute.subtitle_author'), $this->author->name);
        }

        return $subTitle;
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

            $this->save();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    /** @param array<int, string>|string|null $attribute */
    public function touch($attribute = null): bool
    {
        DungeonRoute::dropCaches($this->id);

        return parent::touch($attribute);
    }

    /**
     * Ensure we have an affix group at all times
     *
     *
     *
     * @throws Exception
     */
    public function ensureAffixGroup(
        Season $activeSeason,
    ): void {
        if ($this->affixgroups()->count() === 0) {
            // Make sure this route is at least assigned to an affix so that in the case of claiming we already have an affix which is required
            DungeonRouteAffixGroup::create([
                'affix_group_id' => resolve(SeasonAffixGroupServiceInterface::class)->getCurrentAffixGroup($activeSeason)?->id ?? // @phpstan-ignore nullsafe.neverNull
                    $activeSeason->affixGroups->first()->id,
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
            $orientations = [
                'vertical',
                'horizontal',
                'horizontal_row',
            ];
            $locales     = language()->allowed();
            $showAffixes = [
                0,
                1,
            ];
            $showDungeon = [
                0,
                1,
            ];
            $isAdmin = [
                0,
                1,
            ];

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

    public static function getCardCacheKey(
        int    $dungeonRouteId,
        string $orientation,
        string $locale,
        int    $showAffixes,
        int    $showDungeonImage,
        int    $isAdmin,
    ): string {
        return sprintf(
            'view:dungeonroute_card:%s:%s_%d_%d_%d_%d',
            $orientation,
            $locale,
            $showAffixes,
            $showDungeonImage,
            $isAdmin,
            $dungeonRouteId,
        );
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Delete route properly if it gets deleted
        static::deleting(static function (DungeonRoute $dungeonRoute) {
            $dungeonRoute->load([
                'dungeonRouteThumbnails',
                'dungeonRouteThumbnailJobs',
                'brushlines',
                'paths',
                'arrows',
                'killZones',
                'livesessions',
            ]);

            $dungeonRoute->setConnection('combatlog')->challengeModeRun()->delete();
            $dungeonRoute->setConnection(null);

            // Delete thumbnails
            foreach ($dungeonRoute->dungeonRouteThumbnails as $dungeonRouteThumbnail) {
                // This deletes the file from the database, and then from S3 as well
                $dungeonRouteThumbnail->delete();
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

            foreach ($dungeonRoute->arrows as $arrow) {
                $arrow->delete();
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
