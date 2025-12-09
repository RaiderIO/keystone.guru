<?php

namespace App\Logic\MapContext;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

class MapContextMappingVersionData implements Arrayable
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
        $mappingVersionDataKey = sprintf('dungeon_%d_%d_%s', $this->dungeon->id, $this->mappingVersion->id, $this->mapFacadeStyle);
        $mappingVersionData    = $this->rememberLocal($mappingVersionDataKey, 86400, fn() => $this->cacheService->remember(
            $mappingVersionDataKey,
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
                    ->without('npc')
                    ->with(/*'npc', 'npc.type', 'npc.class',*/ 'floor')
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
        ));

        // Npc data (for localizations)
        $dungeonNpcDataKey = sprintf('dungeon_npcs_%d_%d', $this->dungeon->id, $this->mappingVersion->id);
        $dungeonNpcData    = $this->rememberLocal($dungeonNpcDataKey, 86400, fn() => $this->cacheService->remember(
            $dungeonNpcDataKey,
            fn() => $this->dungeon->npcs()
                ->with([
                    'enemyForces' => fn(HasOne $q) => $q
                        ->where('mapping_version_id', $this->mappingVersion->id)
                        ->select([
                            'id',
                            'npc_id',
                            'mapping_version_id',
                            'enemy_forces',
                            'enemy_forces_teeming',
                        ]),
                ])
                ->disableCache()
                ->get()
                ->setVisible([
                    'id',
                    'enemyForces',
                ]),
            config('keystoneguru.cache.dungeonData.ttl'),
        ));

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
            'dungeon'             => $mappingVersionData,
            'npcEnemyForces'      => $dungeonNpcData,
            'minEnemySizeDefault' => config('keystoneguru.min_enemy_size_default'),
            'maxEnemySizeDefault' => config('keystoneguru.max_enemy_size_default'),
            'npcsMinHealth'       => $npcMinHealth,
            'npcsMaxHealth'       => $npcMaxHealth,
        ];
    }
}
