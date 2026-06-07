<?php

namespace App\Logic\SimulationCraft;

use App\Models\MountableArea;
use App\Models\SimulationCraft\SimulationCraftRaidBuffs;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Illuminate\Support\Collection;

class RaidEventsCollection implements RaidEventOutputInterface, RaidEventsCollectionInterface
{
    /** @var Collection<RaidEventPull> */
    private Collection $raidEventPulls;

    public function __construct(
        private readonly CoordinatesServiceInterface      $coordinatesService,
        private readonly KillZonePathServiceInterface     $killZonePathService,
        private readonly SimulationCraftRaidEventsOptions $options,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function calculateRaidEvents(): RaidEventsCollectionInterface
    {
        $this->raidEventPulls = collect();

        $pathsToKillZones = $this->killZonePathService->findPathsToKillZones($this->options->dungeonRoute);

        if ($this->options->use_mounts) {
            // Load mountable areas for each floor, required later
            /** @var Collection<int, Collection<int, MountableArea>> $mountableAreasCache */
            $mountableAreasCache = collect();
            foreach ($pathsToKillZones as $paths) {
                foreach ($paths as $path) {
                    $path->getFloor()->setRelation(
                        'mountableAreas',
                        $mountableAreasCache->get(
                            $path->getFloor()->id,
                            fn() => $path->getFloor()->mountableAreas($this->options->dungeonRoute->mappingVersion)->get(),
                        ),
                    );
                }
            }
        }

        foreach ($this->options->dungeonRoute->killZones as $killZone) {
            // Skip empty pulls
            if ($killZone->getEnemies()->count() === 0) {
                continue;
            }

            $this->raidEventPulls->push(
                new RaidEventPull($this->coordinatesService, $this->options)
                    ->calculateRaidEventPullEnemies($killZone, $pathsToKillZones[$killZone->id] ?? []),
            );
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        $result = sprintf(
            '
            fight_style=DungeonRoute
            override.bloodlust=%d
            override.arcane_intellect=%d
            override.power_word_fortitude=%d
            override.mark_of_the_wild=%d
            override.battle_shout=%d
            override.mystic_touch=%d
            override.chaos_brand=%d
            override.skyfury=%d
            override.hunters_mark=%d
            override.bleeding=0
            single_actor_batch=1
            max_time=%s
            enemy="%s"
            enemy_health=999999
            %s
            keystone_level=%d
            raid_events=/invulnerable,cooldown=5160,duration=5160,retarget=1
        ',
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::Bloodlust) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::ArcaneIntellect) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::PowerWordFortitude) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::MarkOfTheWild) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::BattleShout) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::MysticTouch) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::ChaosBrand) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::Skyfury) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::HuntersMark) ? 1 : 0,
            $this->options->dungeonRoute->mappingVersion->timer_max_seconds,
            $this->options->dungeonRoute->title,
            $this->options->shrouded_bounty_type === SimulationCraftRaidEventsOptions::SHROUDED_BOUNTY_TYPE_NONE ?
                '' : sprintf('keystone_bounty=%s', $this->options->shrouded_bounty_type),
            $this->options->key_level,
        );

        $pullStrings = [];
        foreach ($this->raidEventPulls as $raidEventPull) {
            $pullStrings[] = $raidEventPull->toString();
        }

        $result .= implode(PHP_EOL, $pullStrings);

        // Trims all individual lines: https://stackoverflow.com/a/1655181/771270
        return preg_replace('/^\s+|\s+$/m', '', $result);
    }
}
