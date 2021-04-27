<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\MapIcon;

class MapIconRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('map_icons.json', MapIcon::class);
    }
}