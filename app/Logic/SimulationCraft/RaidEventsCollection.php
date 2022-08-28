<?php

namespace App\Logic\SimulationCraft;

use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Support\Collection;

class RaidEventsCollection implements RaidEventsCollectionInterface, RaidEventOutputInterface
{
    /** @var SimulationCraftRaidEventsOptions */
    private SimulationCraftRaidEventsOptions $options;

    /** @var Collection|RaidEventPull[] */
    private Collection $raidEventPulls;

    /**
     * @param SimulationCraftRaidEventsOptions $options
     */
    public function __construct(SimulationCraftRaidEventsOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function calculateRaidEvents(): RaidEventsCollectionInterface
    {
        $this->raidEventPulls = collect();

        foreach ($this->options->dungeonroute->killzones as $killZone) {
            $this->raidEventPulls->push(
                (new RaidEventPull($this->options))
                    ->calculateRaidEventPullEnemies($killZone)
            );
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        $result = sprintf('
            fight_style=DungeonRoute
            override.bloodlust=%d
            override.arcane_intellect=%d
            override.power_word_fortitude=%d
            override.battle_shout=%d
            override.mystic_touch=%d
            override.chaos_brand=%d
            override.bleeding=0
            single_actor_batch=1
            max_time=%s
            enemy="%s"
            enemy_health=999999
            keystone_level=%d
            keystone_pct_hp=%d
            raid_events=/invulnerable,cooldown=5160,duration=5160,retarget=1
        ', $this->options->bloodlust,
            $this->options->arcane_intellect,
            $this->options->power_word_fortitude,
            $this->options->battle_shout,
            $this->options->mystic_touch,
            $this->options->chaos_brand,
            $this->options->dungeonroute->dungeon->timer_max_seconds,
            $this->options->dungeonroute->title,
            $this->options->key_level,
            $this->options->hp_percent
        );

        $pullStrings = [];
        foreach ($this->raidEventPulls as $raidEventPull) {
            $pullStrings[] = $raidEventPull->toString();
        }

        $result .= implode(PHP_EOL, $pullStrings);


        return $result;
    }
}
