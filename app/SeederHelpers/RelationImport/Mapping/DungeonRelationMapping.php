<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\Dungeon;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonFloorsRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\NestedModelRelationParser;

class DungeonRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('dungeons.json', Dungeon::class, true);

        $this->setPreSaveRelationParsers(collect([
            new NestedModelRelationParser(),
            new DungeonFloorsRelationParser(),
        ]));
    }
}
