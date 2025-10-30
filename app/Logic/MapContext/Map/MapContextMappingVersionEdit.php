<?php

namespace App\Logic\MapContext\Map;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Logic\MapContext\Map\MapContextMappingVersion;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Models\Dungeon;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Class MapContextMappingVersionEdit
 *
 * @author  Wouter
 *
 * @since   28/08/2023
 */
class MapContextMappingVersionEdit extends MapContextMappingVersion
{
    use ListsEnemies;

    public function __construct(
        CacheServiceInterface       $cacheService,
        CoordinatesServiceInterface $coordinatesService,
        Dungeon                     $dungeon,
        MappingVersion              $mappingVersion,
    ) {
        parent::__construct($cacheService, $coordinatesService, $dungeon, $mappingVersion, User::MAP_FACADE_STYLE_SPLIT_FLOORS);
    }

    public function getType(): string
    {
        return 'mappingVersionEdit';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-mapping-version-edit.%s', config('app.type'), $this->dungeon->getRouteKey());
    }

    public function getEnemies(): ?array
    {
        try {
            return $this->listEnemies($this->cacheService, $this->coordinatesService, $this->mappingVersion, true) ?? [];
        } catch (InvalidMDTDungeonException) {
            return $this->listEnemies($this->cacheService, $this->coordinatesService, $this->mappingVersion) ?? [];
        }
    }

    public function getVisibleFloors(): array
    {
        return $this->dungeon->floors->toArray();
    }

    public function toArray(): array
    {
        // Get or set the NPCs
        $npcs = $this->cacheService->remember(sprintf('npcs_%s', $this->dungeon->id), function () {
            return Npc::with('dungeons')
                ->selectRaw('npcs.*, translations.translation as name')
                ->join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
                ->leftJoin('translations', static function (JoinClause $clause) {
                    $clause->on('translations.key', 'npcs.name')
                        ->on('translations.locale', DB::raw('"en_US"'));
                })
                ->where('npc_dungeons.dungeon_id', $this->dungeon->id)
                ->get()
                ->map(static fn(Npc $npc) => [
                    'id'          => $npc->id,
                    'name'        => $npc->name,
                    'dungeon_ids' => $npc->dungeons->pluck('id'),
                ])
                ->values();
        }, config('keystoneguru.cache.npcs.ttl'));

        return array_merge(parent::toArray(), [
            'dungeonNpcs'    => $npcs,
        ]);
    }
}
