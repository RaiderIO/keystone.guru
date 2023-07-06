<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\EnemyPack;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class EnemyPackRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('enemy_packs.json', EnemyPack::class);

        $this->setConditionals(collect([
            new MappingVersionConditional()
        ]));
    }
}
