<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\Enemy;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class EnemyRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('enemies.json', Enemy::class);

        $this->setConditionals(collect([
            new MappingVersionConditional()
        ]));
    }
}
