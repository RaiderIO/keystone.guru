<?php

namespace App\Logic\SimulationCraft;

use App\Models\Enemy;
use App\Models\Npc;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;

class RaidEventPullEnemy implements RaidEventOutputInterface
{
    /** @var SimulationCraftRaidEventsOptions */
    private SimulationCraftRaidEventsOptions $options;

    /** @var string */
    private string $name;

    /** @var int */
    private int $health;

    public function __construct(SimulationCraftRaidEventsOptions $options, Enemy $enemy, int $enemyIndexInPull)
    {
        $this->options = $options;
        $this->name    = sprintf('%s_%s', $enemy->npc->name, $enemyIndexInPull);
        $this->health  = $this->calculateHealth($options, $enemy->npc);
    }

    /**
     * @param SimulationCraftRaidEventsOptions $options
     * @param Npc $npc
     * @return int
     */
    public function calculateHealth(SimulationCraftRaidEventsOptions $options, Npc $npc): int
    {
        return $npc->calculateHealthForKey(
            $options->key_level,
            $options->affix === SimulationCraftRaidEventsOptions::AFFIX_FORTIFIED,
            $options->affix === SimulationCraftRaidEventsOptions::AFFIX_TYRANNICAL
        );
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getHealth(): int
    {
        return $this->health;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return sprintf('"%s"|%d', $this->name, $this->health);
    }
}
