<?php

namespace App\Logic\SimulationCraft;

use App\Models\MountableArea;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\SimulationCraft\SimulationCraftRaidBuffs;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Illuminate\Support\Collection;

class RaidEventsCollection implements RaidEventOutputInterface, RaidEventsCollectionInterface
{
    /** @var Collection<int, RaidEventPull> */
    private Collection $raidEventPulls;

    /**
     * @param Collection<int, NpcEncounterMechanic>|null $dungeonEncounterMechanics Every boss mechanic of the route's
     *                                                                              dungeon, or null to apply none
     */
    public function __construct(
        private readonly CoordinatesServiceInterface      $coordinatesService,
        private readonly KillZonePathServiceInterface     $killZonePathService,
        private readonly SimulationCraftRaidEventsOptions $options,
        private readonly ?Collection                      $dungeonEncounterMechanics = null,
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

        // An npc that appears in several pulls is hydrated as a separate Npc instance per pull, so
        // without this every one of them fetches its own npc_healths rows (#4586)
        $this->options->dungeonRoute->loadMissing(['killZones.enemies.npc.npcHealths']);

        $previousKillZone = null;
        foreach ($this->options->dungeonRoute->killZones as $killZone) {
            // Skip empty pulls
            if ($killZone->getEnemies()->count() === 0) {
                continue;
            }

            $raidEventPull = new RaidEventPull($this->coordinatesService, $this->options, $this->dungeonEncounterMechanics)
                ->calculateRaidEventPullEnemies($killZone, $pathsToKillZones[$killZone->id] ?? [], $previousKillZone);

            // A pull of nothing but NPCs fighting on the party's side is as empty as a pull without enemies
            if (!$raidEventPull->hasEnemies()) {
                continue;
            }

            $this->raidEventPulls->push($raidEventPull);
            $previousKillZone = $killZone;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRaidEventPulls(): Collection
    {
        return $this->raidEventPulls;
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        $result = sprintf(
            '
            fight_style=DungeonRoute
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
            enemy="%s"
            enemy_health=999999
            keystone_level=%d
            raid_events=/invulnerable,cooldown=5160,duration=5160,retarget=1
        ',
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::ArcaneIntellect) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::PowerWordFortitude) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::MarkOfTheWild) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::BattleShout) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::MysticTouch) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::ChaosBrand) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::Skyfury) ? 1 : 0,
            $this->options->hasRaidBuff(SimulationCraftRaidBuffs::HuntersMark) ? 1 : 0,
            $this->options->dungeonRoute->title,
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
