<?php

namespace App\Logic\MapContext;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\PublishedState;
use App\Models\RaidMarker;
use App\Models\Spell\Spell;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Psr\SimpleCache\InvalidArgumentException;

abstract class MapContext
{
    use ListsEnemies;

    protected MappingVersion $mappingVersion;

    public function __construct(
        protected CacheServiceInterface       $cacheService,
        protected CoordinatesServiceInterface $coordinatesService,
        protected Model                       $context,
        protected Floor                       $floor,
        MappingVersion                        $mappingVersion,
        protected ?string                     $mapFacadeStyle = null
    ) {
        $this->mappingVersion = $mappingVersion;
    }

    abstract public function getType(): string;

    abstract public function isTeeming(): bool;

    abstract public function getSeasonalIndex(): int;

    abstract public function getFloors(): Collection;

    abstract public function getEnemies(): array;

    abstract public function getEchoChannelName(): string;

    public function onlyLoadInUseNpcs(): bool
    {
        return true;
    }

    public function getMapFacadeStyle(): string
    {
        return $this->mapFacadeStyle ?? User::getCurrentUserMapFacadeStyle();
    }

    public function getContext(): Model
    {
        return $this->context;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getProperties(): array
    {
        $mapFacadeStyle = $this->getMapFacadeStyle();

        // Get the DungeonData
        $dungeonData = $this->cacheService->remember(
            sprintf('dungeon_%d_%d_%s', $this->floor->dungeon->id, $this->mappingVersion->id, $mapFacadeStyle),
            function () use ($mapFacadeStyle) {
                $useFacade = $mapFacadeStyle === 'facade';

                /** @var Dungeon $dungeon */
                $dungeon = $this->floor->dungeon()
                    ->with(['dungeonSpeedrunRequiredNpcs10Man', 'dungeonSpeedrunRequiredNpcs25Man'])
                    ->without(['floors', 'mapIcons', 'enemyPacks'])
                    ->first();
                // Filter out floors that we do not need
                $dungeon->setRelation('floors', $this->getFloors());

                // Temporarily disabled to improve performance - not using this anyway
                $auras = collect();
//                $auras = Spell::where('aura', true)
//                    ->get()
//                    ->each(fn(Spell $spell) => $spell->setVisible(['id', 'name', 'icon_url']));

                $enemies = $this->mappingVersion->mapContextEnemies($this->coordinatesService, $useFacade);

                return array_merge($dungeon->toArray(), $this->getEnemies(), [
                    'latestMappingVersion'      => $this->floor->dungeon->currentMappingVersion,
                    'npcs'                      => $this->floor->dungeon->npcs()
                        ->when($this->onlyLoadInUseNpcs(), function (Builder $query) use ($enemies) {
                            $query->whereIn('npcs.id', $enemies->pluck('npc_id')->unique());
                        })->with([
                            'spells',
                            // Restrain the enemy forces relationship so that it returns the enemy forces of the target mapping version only
                            'enemyForces' => fn(HasOne $query) => $query->where('mapping_version_id', $this->mappingVersion->id),
                        ])
                        // Disable cache for this query though! Since NpcEnemyForces is a cache model, it can otherwise return values from another mapping version
                        ->disableCache()
                        ->get()
                        // Only show what we need in the FE
                        ->each(function (Npc $npc) {
                            $npc->enemyForces?->setVisible(['enemy_forces', 'enemy_forces_teeming']);
                            $npc->setHidden(['pivot']);
                        }),
                    'auras'                     => $auras,
                    'enemies'                   => $enemies,
                    'enemyPacks'                => $this->mappingVersion->mapContextEnemyPacks($this->coordinatesService, $useFacade),
                    'enemyPatrols'              => $this->mappingVersion->mapContextEnemyPatrols($this->coordinatesService, $useFacade),
                    'mapIcons'                  => $this->mappingVersion->mapContextMapIcons($this->coordinatesService, $useFacade),
                    'dungeonFloorSwitchMarkers' => $this->mappingVersion->mapContextDungeonFloorSwitchMarkers($this->coordinatesService, $useFacade),
                    'mountableAreas'            => $this->mappingVersion->mapContextMountableAreas($this->coordinatesService, $useFacade),
                    'floorUnions'               => $this->mappingVersion->mapContextFloorUnions($this->coordinatesService, $useFacade),
                    'floorUnionAreas'           => $this->mappingVersion->mapContextFloorUnionAreas($this->coordinatesService, $useFacade),
                ]);
            }, config('keystoneguru.cache.dungeonData.ttl')
        );

        $selectableSpells = Spell::where('selectable', true)->get();
        $characterClasses = CharacterClass::all();
        $mapIconTypes     = MapIconType::all()->keyBy('id');
        $static           = $this->cacheService->remember('static_data', static fn() => [
            'selectableSpells'                  => $selectableSpells,
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

        [$npcMinHealth, $npcMaxHealth] = $this->floor->dungeon->getNpcsMinMaxHealth($this->mappingVersion);

        // Prevent the values being exactly the same, which causes issues in the front end
        if ($npcMaxHealth <= $npcMinHealth) {
            $npcMaxHealth = $npcMinHealth + 1;
        }

        return [
            'environment'         => config('app.env'),
            'type'                => $this->getType(),
            'mappingVersion'      => $this->mappingVersion,
            'floorId'             => $this->floor->id,
            'teeming'             => $this->isTeeming(),
            'seasonalIndex'       => $this->getSeasonalIndex(),
            'dungeon'             => $dungeonData,
            'static'              => $static,
            'minEnemySizeDefault' => config('keystoneguru.min_enemy_size_default'),
            'maxEnemySizeDefault' => config('keystoneguru.max_enemy_size_default'),
            'npcsMinHealth'       => $npcMinHealth,
            'npcsMaxHealth'       => $npcMaxHealth,

            'keystoneScalingFactor' => config('keystoneguru.keystone.scaling_factor'),

            'echoChannelName' => $this->getEchoChannelName(),
            // May be null
            'userPublicKey'   => Auth::user()?->public_key,
        ];
    }
}
