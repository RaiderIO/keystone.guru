<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\Enemy;

class EnemyRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('enemies.json', Enemy::class);
    }
}