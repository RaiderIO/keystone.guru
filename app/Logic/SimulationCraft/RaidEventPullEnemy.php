<?php

namespace App\Logic\SimulationCraft;

use App\Models\Enemy;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Support\Str;

class RaidEventPullEnemy implements RaidEventOutputInterface, RaidEventPullEnemyInterface
{
    /**
     * @param bool $affixExempt Scale the health by the key level only, without Fortified or Tyrannical
     */
    public function __construct(
        private readonly SimulationCraftRaidEventsOptions $options,
        private readonly Enemy                            $enemy,
        private readonly int                              $enemyIndexInPull,
        private readonly bool                             $affixExempt = false,
    ) {
    }

    public function getEnemy(): Enemy
    {
        return $this->enemy;
    }

    public function calculateHealth(SimulationCraftRaidEventsOptions $options, Npc $npc): int
    {
        return self::calculateNpcHealth($options, $npc, $this->affixExempt);
    }

    /**
     * The name the enemy goes by in its pull, e.g. BOSS_sentinel-of-winter_1. SimulationCraft prefixes it with the
     * pull's own name (Pull_3_...) once it spawns.
     */
    public function getName(): string
    {
        $name = sprintf('%s_%s', self::getNpcSlug($this->enemy->npc), $this->enemyIndexInPull);

        if ($this->enemy->npc->classification_id === NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]) {
            $name = sprintf('BOSS_%s', $name);
        }

        return $name;
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        $health = $this->calculateHealth($this->options, $this->enemy->npc);

        return sprintf('"%s":%d', $this->getName(), $health);
    }

    /**
     * The NPC's health at the options' key level and affixes, scaled by the options' health percentage.
     *
     * @param bool $affixExempt Scale by the key level only, without Fortified or Tyrannical
     */
    public static function calculateNpcHealth(SimulationCraftRaidEventsOptions $options, Npc $npc, bool $affixExempt = false): int
    {
        return (int)($npc->calculateHealthForKey(
            $options->dungeonRoute->mappingVersion->gameVersion,
            $options->key_level,
            $options->getAffixes(),
            $affixExempt,
        ) * ($options->hp_percent / 100));
    }

    /**
     * The name an NPC goes by in a SimulationCraft pull - different NPC ids may share one.
     */
    public static function getNpcSlug(Npc $npc): string
    {
        return Str::slug(__($npc->name));
    }
}
