<?php

namespace App\Logic\SimulationCraft;

use App\Models\Enemy;
use App\Models\Npc;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Support\Str;

class RaidEventPullEnemy implements RaidEventPullEnemyInterface, RaidEventOutputInterface
{
    /** @var SimulationCraftRaidEventsOptions */
    private SimulationCraftRaidEventsOptions $options;

    /** @var Enemy */
    private Enemy $enemy;

    /** @var int */
    private int $enemyIndexInPull;

    public function __construct(SimulationCraftRaidEventsOptions $options, Enemy $enemy, int $enemyIndexInPull)
    {
        $this->options          = $options;
        $this->enemy            = $enemy;
        $this->enemyIndexInPull = $enemyIndexInPull;
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
     * @inheritDoc
     */
    public function toString(): string
    {
        $name = sprintf('%s_%s', Str::slug($this->enemy->npc->name), $this->enemyIndexInPull);

        if ($this->enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED) {
            $name = sprintf('BOUNTY1_%s', $name);
        } else if ($this->enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX) {
            $name = sprintf('BOUNTY3_%s', $name);
        }

        $health = $this->calculateHealth($this->options, $this->enemy->npc);

        return sprintf('"%s":%d', $name, $health);
    }
}
