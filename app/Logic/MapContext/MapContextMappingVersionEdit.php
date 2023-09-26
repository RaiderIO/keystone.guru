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
 * Class MapContextMappingVersionEdit
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 28/08/2023
 *
 * @property Dungeon $context
 */
class MapContextMappingVersionEdit extends MapContextMappingVersion
{

    public function getType(): string
    {
        return 'mappingVersionEdit';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-mapping-version-edit.%s', config('app.type'), $this->context->getRouteKey());
    }
}
