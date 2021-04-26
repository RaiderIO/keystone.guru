<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\EnemyPack;

class EnemyPackRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('enemy_packs.json', EnemyPack::class);
    }
}