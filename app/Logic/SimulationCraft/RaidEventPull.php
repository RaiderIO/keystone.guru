<?php

namespace App\Logic\SimulationCraft;

use App\Models\Enemy;
use App\Models\KillZone;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Support\Collection;

class RaidEventPull implements RaidEventPullInterface, RaidEventOutputInterface
{
    /** @var SimulationCraftRaidEventsOptions */
    private SimulationCraftRaidEventsOptions $options;

    /** @var int */
    private int $pullIndex;

    /** @var bool */
    private bool $bloodLust;

    /** @var int */
    private int $delay;

    /** @var Collection|RaidEventPullEnemy[] */
    private Collection $raidEventPullEnemies;

    /**
     * @param SimulationCraftRaidEventsOptions $options
     */
    public function __construct(SimulationCraftRaidEventsOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @return int
     */
    public function getPullIndex(): int
    {
        return $this->pullIndex;
    }

    /**
     * @return bool
     */
    public function isBloodLust(): bool
    {
        return $this->bloodLust;
    }

    /**
     * @param bool $bloodLust
     * @return RaidEventPull
     */
    public function setBloodLust(bool $bloodLust): RaidEventPull
    {
        $this->bloodLust = $bloodLust;
        return $this;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     * @return RaidEventPull
     */
    public function setDelay(int $delay): RaidEventPull
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getRaidEventPullEnemies(): Collection
    {
        return $this->raidEventPullEnemies;
    }

    /**
     * @inheritDoc
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone): RaidEventPullInterface
    {
        $this->pullIndex            = $killZone->index;
        $this->raidEventPullEnemies = collect();
        foreach ($killZone->enemies->groupBy('npc_id') as $npcId => $enemies) {

            $enemyIndex = 1;
            foreach ($enemies as $enemy) {
                $this->addEnemy($enemy, $enemyIndex);
                $enemyIndex++;
            }

        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self
    {
        $this->raidEventPullEnemies->push(
            (new RaidEventPullEnemy($this->options, $enemy, $enemyIndexInPull))
        );

        return $this;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $result = sprintf(
            'raid_events+=/pull,pull=%02d,bloodlust=%d,delay=%03d,enemies=',
            $this->pullIndex,
            (int)$this->bloodLust,
            $this->delay
        );

        $enemyStrings = [];
        foreach ($this->raidEventPullEnemies as $raidEventPullEnemy) {
            $enemyStrings[] = $raidEventPullEnemy->toString();
        }

        $result .= implode('|', $enemyStrings);
        return $result;
    }
}
