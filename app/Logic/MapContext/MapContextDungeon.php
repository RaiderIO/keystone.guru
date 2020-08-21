<?php


namespace App\Logic\MapContext;

use App\Models\Dungeon;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\Npc;
use App\Service\Cache\CacheService;
use Illuminate\Support\Facades\App;

/**
 * Class MapContextDungeon
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 06/08/2020
 *
 * @property Dungeon $_context
 */
class MapContextDungeon extends MapContext
{

    /**
     * MapContextDungeon constructor.
     * @param Dungeon $dungeon
     * @param Floor $floor
     */
    public function __construct(Dungeon $dungeon, Floor $floor)
    {
        parent::__construct($dungeon, $floor);
    }

    public function getType(): string
    {
        return 'dungeon';
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
        return $this->listEnemies($this->_context->id, true);
    }


    public function toArray(): array
    {
        /** @var CacheService $cacheService */
        $cacheService = App::make(CacheService::class);

        // Get or set the NPCs
        $npcs = $cacheService->getOtherwiseSet(sprintf('npcs_%s', $this->_context->id), function ()
        {
            return Npc::whereIn('dungeon_id', [$this->_context->id, -1])->get()->map(function ($npc)
            {
                return ['id' => $npc->id, 'name' => $npc->name, 'dungeon_id' => $npc->dungeon_id];
            })->values();
        });

        return array_merge(parent::toArray(), [
            // First should be unspecified
            'faction' => strtolower(Faction::where('name', 'Unspecified')->first()->name),
            'npcs'    => $npcs,
        ]);
    }


}