<?php


namespace App\Logic\MapContext;

use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Models\Dungeon;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Service\Cache\CacheService;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Support\Facades\App;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class MapContextDungeonExplore
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 28/08/2023
 *
 * @property Dungeon $context
 */
class MapContextDungeonExplore extends MapContextMappingVersion
{

    public function getType(): string
    {
        return 'dungeonExplore';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-dungeon-explore.%s', config('app.type'), $this->context->getRouteKey());
    }
}
