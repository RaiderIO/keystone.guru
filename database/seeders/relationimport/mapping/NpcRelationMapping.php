<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\Npc;
use Database\Seeders\RelationImport\Parsers\NestedModelRelationParser;
use Database\Seeders\RelationImport\Parsers\NpcNpcBolsteringWhitelistRelationParser;
use Database\Seeders\RelationImport\Parsers\NpcNpcSpellsRelationParser;

class NpcRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('npcs.json', Npc::class);

        $this->setPreSaveAttributeParsers(collect([
            new NpcNpcBolsteringWhitelistRelationParser(),
            new NpcNpcSpellsRelationParser(),
        ]));
    }
}