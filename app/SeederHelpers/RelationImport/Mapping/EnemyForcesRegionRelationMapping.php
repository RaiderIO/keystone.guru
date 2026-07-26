<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\EnemyForcesRegion;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class EnemyForcesRegionRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('enemy_forces_regions.json', EnemyForcesRegion::class);

        $this->setConditionals(collect([
            new MappingVersionConditional(),
        ]));
    }
}
