<?php


namespace App\Logic\MapContext;

use App\Models\Dungeon;

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
    public function getMapFacadeStyle(): string
    {
        return 'both';
    }


    public function getType(): string
    {
        return 'mappingVersionEdit';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-mapping-version-edit.%s', config('app.type'), $this->context->getRouteKey());
    }
}
