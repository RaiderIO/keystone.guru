<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\EnemyPatrol;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;
use App\SeederHelpers\RelationImport\Parsers\Relation\EnemyPatrolPolylineRelationParser;

class EnemyPatrolRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('enemy_patrols.json', EnemyPatrol::class);

        $this->setConditionals(collect([
            new MappingVersionConditional()
        ]));
        $this->setPreSaveRelationParsers(collect([
            new EnemyPatrolPolylineRelationParser(),
        ]));
    }
}
