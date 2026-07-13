<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\Npc\Npc;
use App\SeederHelpers\RelationImport\Parsers\Relation\NpcNpcBolsteringWhitelistRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\NpcNpcDungeonsRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\NpcNpcEnemyForcesRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\NpcNpcHealthsRelationParser;

class NpcRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('npcs.json', Npc::class);

        $this->setPreSaveRelationParsers(collect([
            new NpcNpcBolsteringWhitelistRelationParser(),
            new NpcNpcEnemyForcesRelationParser(),
            new NpcNpcDungeonsRelationParser(),
            new NpcNpcHealthsRelationParser(),
        ]));
    }
}
