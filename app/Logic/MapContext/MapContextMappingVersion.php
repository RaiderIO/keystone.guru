<?php


namespace App\Logic\MapContext;

use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Models\Dungeon;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;

/**
 * Class MapContextMappingVersion
 * @package App\Logic\MapContext
 * @author  Wouter
 * @since   06/08/2020
 *
 * @property Dungeon $context
 */
abstract class MapContextMappingVersion extends MapContext
{
    /**
     * @param CacheServiceInterface       $cacheService
     * @param CoordinatesServiceInterface $coordinatesService
     * @param Dungeon                     $dungeon
     * @param Floor                       $floor
     * @param MappingVersion              $mappingVersion
     */
    public function __construct(
        CacheServiceInterface       $cacheService,
        CoordinatesServiceInterface $coordinatesService,
        Dungeon                     $dungeon,
        Floor                       $floor,
        MappingVersion              $mappingVersion)
    {
        parent::__construct($cacheService, $coordinatesService, $dungeon, $floor, $mappingVersion);
    }

    public function isTeeming(): bool
    {
        return true;
    }

    public function getSeasonalIndex(): int
    {
        return -1;
    }

    public function getEnemies(): array
    {
        try {
            return $this->listEnemies($this->mappingVersion, true) ?? [];
        } catch (InvalidMDTDungeonException $e) {
            return $this->listEnemies($this->mappingVersion) ?? [];
        }
    }

    public function getProperties(): array
    {
        // Get or set the NPCs
        $npcs = $this->cacheService->remember(sprintf('npcs_%s', $this->context->id), function () {
            return Npc::whereIn('dungeon_id', [$this->context->id, -1])->get()->map(function ($npc) {
                return ['id' => $npc->id, 'name' => $npc->name, 'dungeon_id' => $npc->dungeon_id];
            })->values();
        },                                    config('keystoneguru.cache.npcs.ttl'));

        return array_merge(parent::getProperties(), [
            // First should be unspecified
            'faction' => __(strtolower(Faction::where('key', Faction::FACTION_UNSPECIFIED)->first()->name)),
            'npcs'    => $npcs,
        ]);
    }
}
