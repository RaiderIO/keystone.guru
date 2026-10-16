<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcBolsteringWhitelist;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcHealth;
use App\SeederHelpers\RelationImport\Parsers\Relation\PivotRelationParser;

class NpcRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('npcs.json', Npc::class);

        $this->setPreSaveRelationParsers(collect([
            new PivotRelationParser(Npc::class, 'npcbolsteringwhitelists', NpcBolsteringWhitelist::class),
            new PivotRelationParser(Npc::class, 'npc_enemy_forces', NpcEnemyForces::class),
            new PivotRelationParser(Npc::class, 'npc_dungeons', NpcDungeon::class),
            new PivotRelationParser(Npc::class, 'npc_healths', NpcHealth::class),
        ]));
    }
}
