<?php

namespace App\Logic\SimulationCraft;

use App\Models\Enemy;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Support\Str;

class RaidEventPullEnemy implements RaidEventOutputInterface, RaidEventPullEnemyInterface
{
    public function __construct(
        private readonly SimulationCraftRaidEventsOptions $options,
        private readonly Enemy                            $enemy,
        private readonly int                              $enemyIndexInPull,
    ) {
    }

    public function calculateHealth(SimulationCraftRaidEventsOptions $options, Npc $npc): int
    {
        return (int)($npc->calculateHealthForKey(
            $options->dungeonRoute->mappingVersion->gameVersion,
            $options->key_level,
            $options->getAffixes(),
        ) * ($this->options->hp_percent / 100));
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        $name = sprintf('%s_%s', self::getNpcSlug($this->enemy->npc), $this->enemyIndexInPull);

        if ($this->enemy->npc->classification_id === NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]) {
            $name = sprintf('BOSS_%s', $name);
        }

        $health = $this->calculateHealth($this->options, $this->enemy->npc);

        return sprintf('"%s":%d', $name, $health);
    }

    /**
     * The name an NPC goes by in a SimulationCraft pull - different NPC ids may share one.
     */
    public static function getNpcSlug(Npc $npc): string
    {
        return Str::slug(__($npc->name));
    }
}
