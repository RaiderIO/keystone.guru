<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\Npc;
use App\SeederHelpers\RelationImport\Parsers\Relation\NpcNpcBolsteringWhitelistRelationParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\NpcNpcSpellsRelationParser;

class NpcRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('npcs.json', Npc::class);

        $this->setPreSaveRelationParsers(collect([
            new NpcNpcBolsteringWhitelistRelationParser(),
            new NpcNpcSpellsRelationParser(),
        ]));
    }
}
