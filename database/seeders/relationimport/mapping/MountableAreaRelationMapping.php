<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\MountableArea;

class MountableAreaRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('mountable_areas.json', MountableArea::class);
    }
}
