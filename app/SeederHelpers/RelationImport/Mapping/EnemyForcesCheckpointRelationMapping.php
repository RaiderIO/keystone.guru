<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\EnemyForcesCheckpoint;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class EnemyForcesCheckpointRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('enemy_forces_checkpoints.json', EnemyForcesCheckpoint::class);

        $this->setConditionals(collect([
            new MappingVersionConditional(),
        ]));
    }
}
