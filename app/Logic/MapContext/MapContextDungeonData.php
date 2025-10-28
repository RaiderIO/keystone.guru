<?php

namespace App\Logic\MapContext;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Faction;
use App\Models\GameVersion\GameVersion;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\PublishedState;
use App\Models\RaidMarker;
use App\Models\Spell\Spell;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\Coordinates\CoordinatesServiceInterface;
use DragonCode\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

class MapContextDungeonData implements Arrayable
{
    use RemembersToFile;

    public function __construct(
        protected CacheServiceInterface       $cacheService,
        protected CoordinatesServiceInterface $coordinatesService,
        protected Dungeon                     $dungeon,
        protected MappingVersion              $mappingVersion,
        protected string                      $mapFacadeStyle,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function toArray(): array
    {
        $this->mappingVersion->load([
            'floorUnions',
            'floorUnionAreas',
            'mountableAreas',
        ]);

        // Get the DungeonData
        $dungeonDataKey = sprintf('dungeon_%d_%d_%s', $this->dungeon->id, $this->mappingVersion->id, $this->mapFacadeStyle);
        $dungeonData    = $this->rememberLocal($dungeonDataKey, 86400, function () use (
            $dungeonDataKey
        ) {
            return $this->cacheService->remember(
                $dungeonDataKey,
                function () {
                    $useFacade = $this->mapFacadeStyle === 'facade';

                    $dungeon = $this->dungeon
                        ->load([
                            'dungeonSpeedrunRequiredNpcs10Man',
                            'dungeonSpeedrunRequiredNpcs25Man',
                        ])
                        ->unsetRelation('mapIcons')
                        ->unsetRelation('enemyPacks')
                        ->setHidden(['floors']);

                    $auras = collect();

                    /** @var Collection<Enemy> $enemies */
                    $enemies = $this->mappingVersion->enemies()
                        ->with('npc', 'npc.type', 'npc.class', 'floor')
                        ->get()
                        ->makeHidden(['enemy_active_auras']);

                    if ($this->mappingVersion->facade_enabled && $useFacade) {
                        foreach ($enemies as $enemy) {
                            $convertedLatLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                                $this->mappingVersion,
                                $enemy->getLatLng(),
                            );

                            $enemy->setLatLng($convertedLatLng);
                        }
                    }

                    return array_merge(
                        $dungeon->toArray(),
                        [
                            'latestMappingVersion'      => $this->dungeon->getCurrentMappingVersion($this->mappingVersion->gameVersion),
                            'auras'                     => $auras,
                            'enemies'                   => $enemies,
                            'enemyPacks'                => $this->mappingVersion->mapContextEnemyPacks($this->coordinatesService, $useFacade),
                            'enemyPatrols'              => $this->mappingVersion->mapContextEnemyPatrols($this->coordinatesService, $useFacade),
                            'mapIcons'                  => $this->mappingVersion->mapContextMapIcons($this->coordinatesService, $useFacade),
                            'dungeonFloorSwitchMarkers' => $this->mappingVersion->mapContextDungeonFloorSwitchMarkers($this->coordinatesService, $useFacade),
                            'mountableAreas'            => $this->mappingVersion->mapContextMountableAreas($this->coordinatesService, $useFacade),
                            'floorUnions'               => $this->mappingVersion->mapContextFloorUnions($this->coordinatesService, $useFacade),
                            'floorUnionAreas'           => $this->mappingVersion->mapContextFloorUnionAreas($this->coordinatesService, $useFacade),
                        ],
                    );
                },
                config('keystoneguru.cache.dungeonData.ttl'),
            );
        });

        // Npc data (for localizations)
        $locale            = Auth::user()?->locale ?? 'en_US';
        $dungeonNpcDataKey = sprintf('dungeon_npcs_%d_%d_%s', $this->dungeon->id, $this->mappingVersion->id, $locale);
        $dungeonNpcData    = $this->rememberLocal($dungeonNpcDataKey, 86400, function () use (
            $dungeonNpcDataKey,
            $locale,
            $dungeonData
        ) {
            return $this->cacheService->remember(
                $dungeonNpcDataKey,
                function () use ($dungeonData, $locale) {
                    return [
                        'npcs' => $this->dungeon->npcs()
                            ->selectRaw('npcs.*, translations.translation as name')
                            ->leftJoin('translations', static function (JoinClause $clause) use ($locale) {
                                $clause->on('translations.key', 'npcs.name')
                                    ->on('translations.locale', DB::raw(sprintf('"%s"', $locale)));
                            })
                            ->with([
                                // @TODO This should just return IDs, and Spells should be a separate list of all spells found in the dungeon (cast by enemies)
                                'spells' => fn(BelongsToMany $belongsToMany) => $belongsToMany
                                    ->selectRaw('spells.*, translations.translation as name')
                                    ->leftJoin('translations', static function (JoinClause $clause) use ($locale) {
                                        $clause->on('translations.key', 'spells.name')
                                            ->on('translations.locale', DB::raw(sprintf('"%s"', $locale)));
                                    }),
                                // Restrain the enemy forces relationship so that it returns the enemy forces of the target mapping version only
                                'enemyForces' => fn(
                                    HasOne $query,
                                ) => $query->where('mapping_version_id', $this->mappingVersion->id),
                            ])
                            // Disable cache for this query though! Since NpcEnemyForces is a cache model, it can otherwise return values from another mapping version
                            ->disableCache()
                            ->get()
                            // Only show what we need in the FE
                            ->each(function (Npc $npc) {
                                $npc->enemyForces?->setVisible([
                                    'enemy_forces',
                                    'enemy_forces_teeming',
                                ]);
                                $npc->setHidden(['pivot']);
                            }),
                    ];
                },
                config('keystoneguru.cache.dungeonData.ttl'),
            );
        });

        $selectableSpellsKey = sprintf('selectable_spells_%s', $locale);
        $selectableSpells    = $this->rememberLocal($selectableSpellsKey, 86400, function () use (
            $selectableSpellsKey,
            $locale
        ) {
            return $this->cacheService->remember(
                $selectableSpellsKey,
                function () use ($locale) {
                    return Spell::where('selectable', true)
                        ->selectRaw('spells.*, translations.translation as name')
                        ->leftJoin('translations', static function (JoinClause $clause) use ($locale) {
                            $clause->on('translations.key', 'spells.name')
                                ->on('translations.locale', DB::raw(sprintf('"%s"', $locale)));
                        })
                        ->get();
                },
                config('keystoneguru.cache.dungeonData.ttl'),
            );
        });

        $characterClasses = CharacterClass::all();
        $mapIconTypes     = MapIconType::all()->keyBy('id');
        $staticKey        = 'static_data';
        $static           = $this->rememberLocal($staticKey, 86400, function () use (
            $staticKey,
            $locale,
            $characterClasses,
            $mapIconTypes
        ) {
            return $this->cacheService->remember($staticKey, static fn() => [
                'mapIconTypes'                      => $mapIconTypes->values(),
                'unknownMapIconType'                => $mapIconTypes->get(MapIconType::ALL[MapIconType::MAP_ICON_TYPE_UNKNOWN]),
                'awakenedObeliskGatewayMapIconType' => $mapIconTypes->get(MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GATEWAY]),
                'classColors'                       => $characterClasses->pluck('color'),
                'characterClasses'                  => $characterClasses,
                'characterClassSpecializations'     => CharacterClassSpecialization::all(),
                'raidMarkers'                       => RaidMarker::all(),
                'factions'                          => Faction::where('name', '<>', 'Unspecified')->with('iconfile')->get(),
                'publishStates'                     => PublishedState::all(),
                'gameVersions'                      => GameVersion::all(),
            ], config('keystoneguru.cache.static_data.ttl'));
        });

        $static['selectableSpells'] = $selectableSpells;

        [
            $npcMinHealth,
            $npcMaxHealth,
        ] = $this->dungeon->getNpcsMinMaxHealth($this->mappingVersion);

        // Prevent the values being exactly the same, which causes issues in the front end
        if ($npcMaxHealth <= $npcMinHealth) {
            $npcMaxHealth = $npcMinHealth + 1;
        }

        return [
            'mappingVersion'      => $this->mappingVersion->makeVisible(['gameVersion']),
            'dungeon'             => array_merge($dungeonData, $dungeonNpcData),
            'static'              => $static,
            'minEnemySizeDefault' => config('keystoneguru.min_enemy_size_default'),
            'maxEnemySizeDefault' => config('keystoneguru.max_enemy_size_default'),
            'npcsMinHealth'       => $npcMinHealth,
            'npcsMaxHealth'       => $npcMaxHealth,
        ];
    }
}
