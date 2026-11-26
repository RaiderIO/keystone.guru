<?php

namespace App\Logic\SimulationCraft;

use App\Models\Enemy;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Support\Collection;

/**
 * The mechanics of every boss encounter whose boss is among a pull's enemies, and what they do to the pull.
 */
class RaidEventPullBossMechanics
{
    /**
     * @param Collection<int, NpcEncounterMechanic> $mechanics Every row of the encounters whose boss is in the pull
     */
    private function __construct(
        private readonly Collection $mechanics,
    ) {
    }

    /**
     * @param Collection<int, NpcEncounterMechanic> $dungeonMechanics Every row of the route's dungeon
     * @param Collection<int, Enemy>                $enemies          The pull's enemies
     */
    public static function forPull(Collection $dungeonMechanics, Collection $enemies): self
    {
        $npcIds = $enemies->pluck('npc_id')->flip();

        $encounterIds = $dungeonMechanics
            ->filter(static fn(NpcEncounterMechanic $mechanic): bool => $mechanic->type === NpcEncounterMechanicType::Boss &&
                $npcIds->has($mechanic->npc_id))
            ->pluck('encounter_id')
            ->flip();

        return new self($dungeonMechanics
            ->filter(static fn(NpcEncounterMechanic $mechanic): bool => $encounterIds->has($mechanic->encounter_id))
            ->values());
    }

    /**
     * An NPC fighting on the party's side is no enemy to kill.
     */
    public function isFriendly(?int $npcId): bool
    {
        return $this->hasRow(NpcEncounterMechanicType::Friendly, $npcId);
    }

    /**
     * An NPC whose health scales with the key level but not with Fortified or Tyrannical.
     */
    public function isAffixExempt(?int $npcId): bool
    {
        return $this->hasRow(NpcEncounterMechanicType::AffixExempt, $npcId);
    }

    /**
     * SimulationCraft shares the health of every enemy of the pull, so the pull may only say so when all of them belong
     * to one shared health group.
     *
     * @param Collection<int, Enemy> $enemies The enemies left in the pull
     */
    public function hasSharedHealth(Collection $enemies): bool
    {
        if ($enemies->isEmpty()) {
            return false;
        }

        return $this->getRows(NpcEncounterMechanicType::SharedHealth)->contains(
            static fn(NpcEncounterMechanic $mechanic): bool => $enemies->every(
                static fn(Enemy $enemy): bool => in_array($enemy->npc_id, $mechanic->getSharedHealthNpcIds(), true),
            ),
        );
    }

    /**
     * The adds, invulnerability windows and forced movement of the pull's encounters. A row SimulationCraft cannot
     * express faithfully is left out: adds no player attacks or that lack a health, a health trigger that happens
     * once, adds that never died and never come back, and anything whose target is not in the pull.
     *
     * @param Collection<int, RaidEventPullEnemy> $pullEnemies
     *
     * @return list<RaidEventPullChildEvent>
     */
    public function createChildEvents(SimulationCraftRaidEventsOptions $options, int $pullIndex, Collection $pullEnemies): array
    {
        $result = [];

        foreach ($this->mechanics as $mechanic) {
            $childEvent = match ($mechanic->type) {
                NpcEncounterMechanicType::Adds         => $this->createAdds($options, $pullIndex, $pullEnemies, $mechanic),
                NpcEncounterMechanicType::Invulnerable => $this->createInvulnerable($pullIndex, $pullEnemies, $mechanic),
                NpcEncounterMechanicType::Movement     => $this->createMovement($pullIndex, $pullEnemies, $mechanic),
                default                                => null,
            };

            if ($childEvent !== null) {
                $result[] = $childEvent;
            }
        }

        return $result;
    }

    /**
     * @param Collection<int, RaidEventPullEnemy> $pullEnemies
     */
    private function createAdds(
        SimulationCraftRaidEventsOptions $options,
        int                              $pullIndex,
        Collection                       $pullEnemies,
        NpcEncounterMechanic             $mechanic,
    ): ?RaidEventPullAdds {
        $npc = $mechanic->npc;
        if (($mechanic->data['attackable'] ?? false) !== true || $npc === null ||
            $npc->getHealthByGameVersion($options->dungeonRoute->mappingVersion->gameVersion) === null) {
            return null;
        }

        $timing = $this->createTiming($pullEnemies, $mechanic);
        // Adds that never died in any observed kill stay until the next wave at most
        $durationMs = $mechanic->duration_ms ?? $mechanic->cooldown_ms;
        if ($timing === null || $durationMs === null) {
            return null;
        }

        // SimulationCraft refuses adds whose duration exceeds their cooldown: one event cannot have two waves up
        if ($mechanic->cooldown_ms !== null) {
            $durationMs = min($durationMs, $mechanic->cooldown_ms);
        }

        return new RaidEventPullAdds(
            $pullIndex,
            $timing,
            sprintf('Adds_%02d_%s', $pullIndex, RaidEventPullEnemy::getNpcSlug($npc)),
            max(1, $mechanic->count ?? 1),
            RaidEventPullEnemy::calculateNpcHealth($options, $npc, $this->isAffixExempt($npc->id)),
            $durationMs,
        );
    }

    /**
     * @param Collection<int, RaidEventPullEnemy> $pullEnemies
     */
    private function createInvulnerable(
        int                  $pullIndex,
        Collection           $pullEnemies,
        NpcEncounterMechanic $mechanic,
    ): ?RaidEventPullInvulnerable {
        $target = $this->findPullEnemyOfNpc($pullEnemies, $mechanic->npc_id);
        $timing = $this->createTiming($pullEnemies, $mechanic);
        if ($target === null || $timing === null || $mechanic->duration_ms === null) {
            return null;
        }

        return new RaidEventPullInvulnerable($pullIndex, $timing, $target->getName(), $mechanic->duration_ms);
    }

    /**
     * @param Collection<int, RaidEventPullEnemy> $pullEnemies
     */
    private function createMovement(
        int                  $pullIndex,
        Collection           $pullEnemies,
        NpcEncounterMechanic $mechanic,
    ): ?RaidEventPullMovement {
        $distance = (float)($mechanic->data['median_distance'] ?? 0);
        $timing   = $this->createTiming($pullEnemies, $mechanic);
        if ($distance <= 0 || $timing === null) {
            return null;
        }

        return new RaidEventPullMovement($pullIndex, $timing, $distance);
    }

    /**
     * @param Collection<int, RaidEventPullEnemy> $pullEnemies
     */
    private function createTiming(Collection $pullEnemies, NpcEncounterMechanic $mechanic): ?RaidEventPullTiming
    {
        if ($mechanic->first_ms !== null) {
            return RaidEventPullTiming::atTime($mechanic->first_ms, $mechanic->cooldown_ms);
        }

        if ($mechanic->first_health_pct === null || $mechanic->cooldown_ms === null) {
            return null;
        }

        $boss = $this->findBossPullEnemy($pullEnemies, $mechanic->encounter_id);
        if ($boss === null) {
            return null;
        }

        return RaidEventPullTiming::atHealth($mechanic->first_health_pct, $boss->getName(), $mechanic->cooldown_ms);
    }

    /**
     * The pull's enemy of the encounter's first boss that is in the pull.
     *
     * @param Collection<int, RaidEventPullEnemy> $pullEnemies
     */
    private function findBossPullEnemy(Collection $pullEnemies, int $encounterId): ?RaidEventPullEnemy
    {
        foreach ($this->getRows(NpcEncounterMechanicType::Boss) as $bossMechanic) {
            if ($bossMechanic->encounter_id !== $encounterId) {
                continue;
            }

            $pullEnemy = $this->findPullEnemyOfNpc($pullEnemies, $bossMechanic->npc_id);
            if ($pullEnemy !== null) {
                return $pullEnemy;
            }
        }

        return null;
    }

    /**
     * @param Collection<int, RaidEventPullEnemy> $pullEnemies
     */
    private function findPullEnemyOfNpc(Collection $pullEnemies, ?int $npcId): ?RaidEventPullEnemy
    {
        if ($npcId === null) {
            return null;
        }

        return $pullEnemies->first(
            static fn(RaidEventPullEnemy $pullEnemy): bool => $pullEnemy->getEnemy()->npc_id === $npcId,
        );
    }

    private function hasRow(NpcEncounterMechanicType $type, ?int $npcId): bool
    {
        return $npcId !== null && $this->getRows($type)->contains(
            static fn(NpcEncounterMechanic $mechanic): bool => $mechanic->npc_id === $npcId,
        );
    }

    /**
     * @return Collection<int, NpcEncounterMechanic>
     */
    private function getRows(NpcEncounterMechanicType $type): Collection
    {
        return $this->mechanics->filter(static fn(NpcEncounterMechanic $mechanic): bool => $mechanic->type === $type);
    }
}
