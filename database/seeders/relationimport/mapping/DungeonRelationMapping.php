<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\Dungeon;
use Database\Seeders\RelationImport\Parsers\DungeonFloorsRelationParser;
use Database\Seeders\RelationImport\Parsers\DungeonSpeedrunRequiredNpcsRelationParser;
use Database\Seeders\RelationImport\Parsers\NestedModelRelationParser;

class DungeonRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('dungeons.json', Dungeon::class, true);

        $this->setPreSaveAttributeParsers(collect([
            new NestedModelRelationParser(),
            new DungeonFloorsRelationParser(),
            new DungeonSpeedrunRequiredNpcsRelationParser(),
        ]));
    }
}
