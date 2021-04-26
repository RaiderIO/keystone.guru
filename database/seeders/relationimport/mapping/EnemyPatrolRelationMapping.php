<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\EnemyPatrol;
use Database\Seeders\RelationImport\Parsers\EnemyPatrolPolylineRelationParser;

class EnemyPatrolRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('enemy_patrols.json', EnemyPatrol::class);

        $this->setPreSaveAttributeParsers(collect([
            new EnemyPatrolPolylineRelationParser(),
        ]));
    }
}