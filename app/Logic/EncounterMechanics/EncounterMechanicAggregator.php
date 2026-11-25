<?php

namespace App\Logic\EncounterMechanics;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcEncounterMechanicType;
use App\Service\CombatLog\DataExtractors\EncounterMechanics\EncounterObservation;

/**
 * Turns the raw observations of one boss encounter's kills, one per run, into the mechanics that recur across them.
 * The shape of an observation's data is {@see EncounterObservation::toData()}. Pure: the caller loads the runs and
 * the NPC facts and writes the rows.
 *
 * @phpstan-type EncounterRun array{key_level: int|null, data: array<string, mixed>}
 * @phpstan-type MechanicRow array{
 *     type: string,
 *     npc_id: int|null,
 *     spell_id: int|null,
 *     first_ms: int|null,
 *     first_health_pct: float|null,
 *     cooldown_ms: int|null,
 *     duration_ms: int|null,
 *     count: int|null,
 *     run_count: int,
 *     total_run_count: int,
 *     data: array<string, mixed>|null
 * }
 */
class EncounterMechanicAggregator
{
    private const float EPSILON = 0.000001;

    /** @var list<EncounterRun> */
    private array $runs = [];

    /** @var array<int, list<array{run: int, npc: array<string, mixed>}>> npc id => its observation in each run it appears in */
    private array $appearancesByNpcId = [];

    /** @var array<int, true> */
    private array $bossNpcIds = [];

    /** @var array<int, true> */
    private array $addNpcIds = [];

    public function __construct(
        private readonly float $recurrenceRatio,
        private readonly int   $firstTimeToleranceMs,
        private readonly float $firstTimeToleranceRatio,
        private readonly float $firstHealthTolerancePct,
        private readonly int   $addsMinFirstMs,
        private readonly float $invulnerableMinHealthPct,
        private readonly int   $movementMinFirstMs,
        private readonly float $movementMaxCastsPerWindow,
        private readonly float $affixExemptTolerance,
        private readonly int   $affixExemptMinKeyLevel,
    ) {
    }

    /**
     * @param  list<EncounterRun> $runs                 one kill per run
     * @param  array<int, true>   $bossClassifiedNpcIds the NPCs classified boss or final boss
     * @param  array<int, float>  $baseHealthByNpcId    base health with its percentage applied, for the dungeon's game version
     * @return list<MechanicRow>
     */
    public function aggregate(array $runs, array $bossClassifiedNpcIds, array $baseHealthByNpcId): array
    {
        $this->runs               = $runs;
        $this->appearancesByNpcId = [];
        $this->bossNpcIds         = [];
        $this->addNpcIds          = [];

        foreach ($this->runs as $runIndex => $run) {
            foreach ($run['data']['npcs'] ?? [] as $npcId => $npc) {
                $this->appearancesByNpcId[(int)$npcId][] = ['run' => $runIndex, 'npc' => $npc];
            }
        }

        ksort($this->appearancesByNpcId);

        $rows = $this->buildBossRows($bossClassifiedNpcIds);
        if ($rows === []) {
            return [];
        }

        return array_merge(
            $rows,
            $this->buildAddsRows($bossClassifiedNpcIds),
            $this->buildInvulnerableRows(),
            $this->buildSharedHealthRows(),
            $this->buildMovementRows(),
            $this->buildFriendlyRows(),
            $this->buildAffixExemptRows($bossClassifiedNpcIds, $baseHealthByNpcId),
        );
    }

    /**
     * @param  array<int, true>  $bossClassifiedNpcIds
     * @return list<MechanicRow>
     */
    private function buildBossRows(array $bossClassifiedNpcIds): array
    {
        $rows = [];
        foreach ($this->appearancesByNpcId as $npcId => $appearances) {
            if (!isset($bossClassifiedNpcIds[$npcId]) ||
                !$this->recurs(count($appearances)) ||
                !$this->isMostly($appearances, static fn(array $npc) => ($npc['reaction'] ?? null) === EncounterObservation::REACTION_HOSTILE)) {
                continue;
            }

            $this->bossNpcIds[$npcId] = true;
            $rows[]                   = $this->row(NpcEncounterMechanicType::Boss, count($appearances), npcId: $npcId);
        }

        return $rows;
    }

    /**
     * @param  array<int, true>  $bossClassifiedNpcIds
     * @return list<MechanicRow>
     */
    private function buildAddsRows(array $bossClassifiedNpcIds): array
    {
        $rows = [];
        foreach ($this->appearancesByNpcId as $npcId => $appearances) {
            // A unit that never shows its health is an invisible helper or an area effect, not something to fight
            if (isset($bossClassifiedNpcIds[$npcId]) ||
                !$this->recurs(count($appearances)) ||
                $this->isMostlyFriendly($appearances) ||
                !$this->isMostly($appearances, static fn(array $npc) => ($npc['max_hp'] ?? 0) > 0)) {
                continue;
            }

            /** @var list<list<array{t: int, count: int, boss_hp_pct: float|null}>> $wavesPerRun */
            $wavesPerRun = array_values(array_filter(
                array_map(static fn(array $appearance) => array_values($appearance['npc']['waves'] ?? []), $appearances),
                static fn(array $waves) => $waves !== [],
            ));
            if ($wavesPerRun === []) {
                continue;
            }

            $firstTimes = array_map(static fn(array $waves) => $waves[0]['t'], $wavesPerRun);
            $firstMs    = self::median($firstTimes);
            if ($firstMs < $this->addsMinFirstMs) {
                continue;
            }

            $firstHealthPct = null;
            if (!$this->firstTimesAgree($firstTimes, $firstMs, count($appearances))) {
                $firstHealthPct = $this->getAgreeingFirstHealthPct($wavesPerRun, count($appearances));
                if ($firstHealthPct === null) {
                    continue;
                }

                $firstMs = null;
            }

            $lifetimes = [];
            foreach ($appearances as $appearance) {
                array_push($lifetimes, ...array_values($appearance['npc']['lifetimes_ms'] ?? []));
            }

            $this->addNpcIds[$npcId] = true;
            $rows[]                  = $this->row(
                NpcEncounterMechanicType::Adds,
                count($appearances),
                npcId: $npcId,
                firstMs: $firstMs === null ? null : (int)round($firstMs),
                firstHealthPct: $firstHealthPct,
                cooldownMs: $this->getCooldownMs($wavesPerRun),
                durationMs: $lifetimes === [] ? null : (int)round(self::median($lifetimes)),
                count: (int)round(self::median(array_merge(...array_map(
                    static fn(array $waves) => array_map(static fn(array $wave) => $wave['count'], $waves),
                    $wavesPerRun,
                )))),
                data: [
                    'attackable'       => $this->isMostly($appearances, static fn(array $npc) => ($npc['player_damage_events'] ?? 0) > 0),
                    'summoned_by_boss' => $this->isMostly($appearances, fn(array $npc) => isset($this->bossNpcIds[(int)($npc['summoned_by_npc_id'] ?? 0)])),
                ],
            );
        }

        return $rows;
    }

    /**
     * @return list<MechanicRow>
     */
    private function buildInvulnerableRows(): array
    {
        /** @var array<int, list<array{run: int, npc_id: int, t: int, duration_ms: int}>> $windowsBySpellId */
        $windowsBySpellId = [];
        foreach ($this->runs as $runIndex => $run) {
            foreach ($run['data']['no_damage_windows'] ?? [] as $window) {
                $healthPct = $window['hp_pct'] ?? null;
                if (!isset($this->bossNpcIds[(int)$window['npc_id']]) ||
                    ($healthPct !== null && $healthPct < $this->invulnerableMinHealthPct)) {
                    continue;
                }

                foreach (array_unique($window['aura_spell_ids'] ?? []) as $spellId) {
                    $windowsBySpellId[(int)$spellId][] = [
                        'run'         => $runIndex,
                        'npc_id'      => (int)$window['npc_id'],
                        't'           => (int)$window['t'],
                        'duration_ms' => (int)$window['duration_ms'],
                    ];
                }
            }
        }

        if ($windowsBySpellId === []) {
            return [];
        }

        // Ties go to the lowest spell id, so a rebuild picks the same aura
        ksort($windowsBySpellId);
        $spellId = array_key_first($windowsBySpellId);
        foreach ($windowsBySpellId as $candidateSpellId => $windows) {
            if (count($windows) > count($windowsBySpellId[$spellId])) {
                $spellId = $candidateSpellId;
            }
        }

        $windowCountByNpcId = array_count_values(array_column($windowsBySpellId[$spellId], 'npc_id'));
        arsort($windowCountByNpcId);
        $npcId   = (int)array_key_first($windowCountByNpcId);
        $windows = array_values(array_filter($windowsBySpellId[$spellId], static fn(array $window) => $window['npc_id'] === $npcId));

        $row = $this->buildRecurringWindowRow(NpcEncounterMechanicType::Invulnerable, $windows, $spellId);
        if ($row === null) {
            return [];
        }

        $row['npc_id'] = $npcId;

        return [$row];
    }

    /**
     * @return list<MechanicRow>
     */
    private function buildSharedHealthRows(): array
    {
        /** @var array<string, array<int, true>> $runsByGroupKey */
        $runsByGroupKey = [];
        foreach ($this->runs as $runIndex => $run) {
            foreach ($run['data']['shared_health'] ?? [] as $group) {
                $npcIds = array_map(intval(...), $group);
                sort($npcIds);
                $runsByGroupKey[implode(',', $npcIds)][$runIndex] = true;
            }
        }

        ksort($runsByGroupKey);

        $rows = [];
        foreach ($runsByGroupKey as $groupKey => $runIndices) {
            if (!$this->recurs(count($runIndices))) {
                continue;
            }

            $npcIds         = array_map(intval(...), explode(',', (string)$groupKey));
            $distinctNpcIds = array_values(array_unique($npcIds));
            $bossNpcIds     = array_values(array_filter($distinctNpcIds, fn(int $npcId) => isset($this->bossNpcIds[$npcId])));
            $addNpcIds      = array_values(array_filter($distinctNpcIds, fn(int $npcId) => isset($this->addNpcIds[$npcId])));
            if ($bossNpcIds === [] && $addNpcIds === []) {
                continue;
            }

            $rows[] = $this->row(
                NpcEncounterMechanicType::SharedHealth,
                count($runIndices),
                npcId: min($bossNpcIds === [] ? $addNpcIds : $bossNpcIds),
                count: count($npcIds),
                data: ['npc_ids' => $distinctNpcIds],
            );
        }

        return $rows;
    }

    /**
     * @return list<MechanicRow>
     */
    private function buildMovementRows(): array
    {
        /** @var list<array{run: int, t: int, duration_ms: int, median_distance: float, spell_ids: array<int, true>}> $windows */
        $windows = [];
        foreach ($this->runs as $runIndex => $run) {
            foreach ($run['data']['movement_windows'] ?? [] as $window) {
                if ($window['t'] < $this->movementMinFirstMs) {
                    continue;
                }

                $windows[] = [
                    'run'             => $runIndex,
                    't'               => (int)$window['t'],
                    'duration_ms'     => (int)$window['duration_ms'],
                    'median_distance' => (float)$window['median_distance'],
                    'spell_ids'       => array_fill_keys(array_map(intval(...), $window['cast_spell_ids'] ?? []), true),
                ];
            }
        }

        $rows = [];
        // A window counts for one spell only: the best candidate takes all of its windows, then the rest are ranked again
        while (true) {
            /** @var array<int, list<int>> $windowIndicesBySpellId */
            $windowIndicesBySpellId = [];
            foreach ($windows as $windowIndex => $window) {
                foreach ($window['spell_ids'] as $spellId => $true) {
                    $windowIndicesBySpellId[$spellId][] = $windowIndex;
                }
            }

            /** @var array<int, array{rank: list<int|float>, median_casts_per_run: float|null}> $candidatesBySpellId */
            $candidatesBySpellId = [];
            foreach ($windowIndicesBySpellId as $spellId => $windowIndices) {
                $windowCountByRun = array_count_values(array_column(
                    $this->mergeCloseMovementWindows(array_map(static fn(int $windowIndex) => $windows[$windowIndex], $windowIndices)),
                    'run',
                ));
                if (!$this->recurs(count($windowCountByRun))) {
                    continue;
                }

                $medianWindowsPerRun = self::median(array_values($windowCountByRun));
                $medianCastsPerRun   = $this->getMedianCastsPerRun($spellId, array_keys($windowCountByRun));
                if ($medianCastsPerRun !== null && $medianCastsPerRun > $this->movementMaxCastsPerWindow * $medianWindowsPerRun + self::EPSILON) {
                    continue;
                }

                // Spells whose cast count is known come first, closest to one cast per window; then the most windows
                $candidatesBySpellId[$spellId] = [
                    'rank' => [
                        $medianCastsPerRun === null ? 1 : 0,
                        $medianCastsPerRun === null ? 0 : abs($medianCastsPerRun - $medianWindowsPerRun),
                        -count($windowIndices),
                        $spellId,
                    ],
                    'median_casts_per_run' => $medianCastsPerRun,
                ];
            }

            if ($candidatesBySpellId === []) {
                break;
            }

            uasort($candidatesBySpellId, static fn(array $a, array $b) => $a['rank'] <=> $b['rank']);
            $spellId = (int)array_key_first($candidatesBySpellId);

            $spellWindows = [];
            foreach ($windowIndicesBySpellId[$spellId] as $windowIndex) {
                $spellWindows[] = $windows[$windowIndex];
                unset($windows[$windowIndex]);
            }

            $spellWindows = $this->mergeCloseMovementWindows($spellWindows);
            $row          = $this->buildRecurringWindowRow(NpcEncounterMechanicType::Movement, $spellWindows, $spellId);
            if ($row !== null) {
                $medianCastsPerRun = $candidatesBySpellId[$spellId]['median_casts_per_run'];
                $row['data']       = [
                    'median_distance'      => round(self::median(array_column($spellWindows, 'median_distance')), 1),
                    'median_casts_per_run' => $medianCastsPerRun === null ? null : round($medianCastsPerRun, 1),
                ];
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * A spell often moves the party in two windows a few seconds apart (Raging Squall is cast twice, 3 s apart): its
     * windows in one run starting within the absolute first-time tolerance of an occurrence's first window join it.
     *
     * @template T of array{run: int, t: int, duration_ms: int}
     * @param  list<T> $windows
     * @return list<T>
     */
    private function mergeCloseMovementWindows(array $windows): array
    {
        usort($windows, static fn(array $a, array $b) => [$a['run'], $a['t']] <=> [$b['run'], $b['t']]);

        $occurrences = [];
        foreach ($windows as $window) {
            $lastIndex = count($occurrences) - 1;
            if ($lastIndex >= 0 &&
                $occurrences[$lastIndex]['run'] === $window['run'] &&
                $window['t'] - $occurrences[$lastIndex]['t'] <= $this->firstTimeToleranceMs) {
                $occurrences[$lastIndex]['duration_ms'] = max(
                    $occurrences[$lastIndex]['duration_ms'],
                    $window['t'] + $window['duration_ms'] - $occurrences[$lastIndex]['t'],
                );

                continue;
            }

            $occurrences[] = $window;
        }

        return $occurrences;
    }

    /**
     * The median number of times a spell was cast in the given runs, or null when none of them counted its casts:
     * observations from before cast counts were stored, or a spell outside the counted top.
     *
     * @param list<int> $runIndices
     */
    private function getMedianCastsPerRun(int $spellId, array $runIndices): ?float
    {
        $castCounts = [];
        foreach ($runIndices as $runIndex) {
            $castCount = $this->runs[$runIndex]['data']['cast_counts'][$spellId] ?? null;
            if ($castCount !== null) {
                $castCounts[] = (int)$castCount;
            }
        }

        return $castCounts === [] ? null : self::median($castCounts);
    }

    /**
     * @return list<MechanicRow>
     */
    private function buildFriendlyRows(): array
    {
        $rows = [];
        foreach ($this->appearancesByNpcId as $npcId => $appearances) {
            if (!$this->recurs(count($appearances)) ||
                !$this->isMostlyFriendly($appearances) ||
                !$this->isMostly($appearances, static fn(array $npc) => ($npc['max_hp'] ?? 0) > 0)) {
                continue;
            }

            $rows[] = $this->row(NpcEncounterMechanicType::Friendly, count($appearances), npcId: $npcId);
        }

        return $rows;
    }

    /**
     * @param  array<int, true>  $bossClassifiedNpcIds
     * @param  array<int, float> $baseHealthByNpcId
     * @return list<MechanicRow>
     */
    private function buildAffixExemptRows(array $bossClassifiedNpcIds, array $baseHealthByNpcId): array
    {
        $rows = [];
        foreach ($this->appearancesByNpcId as $npcId => $appearances) {
            $baseHealth = $baseHealthByNpcId[$npcId] ?? 0;
            if ($baseHealth <= 0 ||
                isset($bossClassifiedNpcIds[$npcId]) ||
                !$this->recurs(count($appearances)) ||
                $this->isMostlyFriendly($appearances)) {
                continue;
            }

            $ratios = [];
            foreach ($appearances as $appearance) {
                $keyLevel = $this->runs[$appearance['run']]['key_level'];
                $maxHp    = $appearance['npc']['max_hp'] ?? 0;
                if ($keyLevel === null || $keyLevel < $this->affixExemptMinKeyLevel || $maxHp <= 0) {
                    continue;
                }

                $ratios[] = $maxHp / ($baseHealth * Npc::getKeyLevelScalingFactor($keyLevel));
            }

            $matchingRatios = array_values(array_filter($ratios, fn(float $ratio) => abs($ratio - 1) <= $this->affixExemptTolerance + self::EPSILON));
            if ($matchingRatios === [] || count($matchingRatios) * 2 <= count($ratios)) {
                continue;
            }

            $rows[] = $this->row(
                NpcEncounterMechanicType::AffixExempt,
                count($matchingRatios),
                npcId: $npcId,
                data: ['ratio' => round(self::median($ratios), 3), 'key_level_run_count' => count($ratios)],
            );
        }

        return $rows;
    }

    /**
     * A row for windows that recur by time: no-damage windows carrying one aura, or movement windows following one
     * cast. Null when too few runs have them or their first times disagree.
     *
     * @param  list<array{run: int, t: int, duration_ms: int}> $windows
     * @return MechanicRow|null
     */
    private function buildRecurringWindowRow(NpcEncounterMechanicType $type, array $windows, int $spellId): ?array
    {
        /** @var array<int, list<array{t: int, duration_ms: int}>> $windowsByRun */
        $windowsByRun = [];
        foreach ($windows as $window) {
            $windowsByRun[$window['run']][] = $window;
        }

        if (!$this->recurs(count($windowsByRun))) {
            return null;
        }

        foreach ($windowsByRun as &$runWindows) {
            usort($runWindows, static fn(array $a, array $b) => $a['t'] <=> $b['t']);
        }

        unset($runWindows);

        $firstTimes = array_map(static fn(array $runWindows) => $runWindows[0]['t'], array_values($windowsByRun));
        $firstMs    = self::median($firstTimes);
        if (!$this->firstTimesAgree($firstTimes, $firstMs, count($firstTimes))) {
            return null;
        }

        return $this->row(
            $type,
            count($windowsByRun),
            spellId: $spellId,
            firstMs: (int)round($firstMs),
            cooldownMs: $this->getCooldownMs(array_values($windowsByRun)),
            durationMs: (int)round(self::median(array_column($windows, 'duration_ms'))),
        );
    }

    /**
     * The median gap between consecutive occurrences over all runs, or null when a typical run has only one.
     *
     * @param list<list<array{t: int}>> $occurrencesPerRun each run's occurrences, in time order
     */
    private function getCooldownMs(array $occurrencesPerRun): ?int
    {
        if (self::median(array_map(count(...), $occurrencesPerRun)) <= 1) {
            return null;
        }

        $gaps = [];
        foreach ($occurrencesPerRun as $occurrences) {
            for ($i = 1; $i < count($occurrences); $i++) {
                $gaps[] = $occurrences[$i]['t'] - $occurrences[$i - 1]['t'];
            }
        }

        return $gaps === [] ? null : (int)round(self::median($gaps));
    }

    /**
     * @param list<list<array{boss_hp_pct: float|null}>> $wavesPerRun
     */
    private function getAgreeingFirstHealthPct(array $wavesPerRun, int $runCount): ?float
    {
        $firstHealthPcts = array_values(array_filter(
            array_map(static fn(array $waves) => $waves[0]['boss_hp_pct'] ?? null, $wavesPerRun),
            static fn(?float $healthPct) => $healthPct !== null,
        ));
        if ($firstHealthPcts === []) {
            return null;
        }

        $median   = self::median($firstHealthPcts);
        $agreeing = count(array_filter($firstHealthPcts, fn(float $healthPct) => abs($healthPct - $median) <= $this->firstHealthTolerancePct + self::EPSILON));

        return $this->isRecurringShare($agreeing, $runCount) ? round($median, 1) : null;
    }

    /**
     * @param list<int> $firstTimes
     */
    private function firstTimesAgree(array $firstTimes, float $median, int $runCount): bool
    {
        $tolerance = max($this->firstTimeToleranceMs, $this->firstTimeToleranceRatio * $median);
        $agreeing  = count(array_filter($firstTimes, static fn(int $t) => abs($t - $median) <= $tolerance + self::EPSILON));

        return $this->isRecurringShare($agreeing, $runCount);
    }

    private function recurs(int $runCount): bool
    {
        return $this->isRecurringShare($runCount, count($this->runs));
    }

    private function isRecurringShare(int $count, int $total): bool
    {
        return $total > 0 && $count + self::EPSILON >= $this->recurrenceRatio * $total;
    }

    /**
     * @param list<array{run: int, npc: array<string, mixed>}> $appearances
     */
    private function isMostlyFriendly(array $appearances): bool
    {
        return $this->isMostly($appearances, static fn(array $npc) => ($npc['reaction'] ?? null) === EncounterObservation::REACTION_FRIENDLY);
    }

    /**
     * Whether more than half of the appearances pass.
     *
     * @param list<array{run: int, npc: array<string, mixed>}> $appearances
     * @param callable(array<string, mixed>): bool             $predicate
     */
    private function isMostly(array $appearances, callable $predicate): bool
    {
        $passing = count(array_filter($appearances, static fn(array $appearance) => $predicate($appearance['npc'])));

        return $passing * 2 > count($appearances);
    }

    /**
     * @param  array<string, mixed>|null $data
     * @return MechanicRow
     */
    private function row(
        NpcEncounterMechanicType $type,
        int                      $runCount,
        ?int                     $npcId = null,
        ?int                     $spellId = null,
        ?int                     $firstMs = null,
        ?float                   $firstHealthPct = null,
        ?int                     $cooldownMs = null,
        ?int                     $durationMs = null,
        ?int                     $count = null,
        ?array                   $data = null,
    ): array {
        return [
            'type'             => $type->value,
            'npc_id'           => $npcId,
            'spell_id'         => $spellId,
            'first_ms'         => $firstMs,
            'first_health_pct' => $firstHealthPct,
            'cooldown_ms'      => $cooldownMs,
            'duration_ms'      => $durationMs,
            'count'            => $count,
            'run_count'        => $runCount,
            'total_run_count'  => count($this->runs),
            'data'             => $data,
        ];
    }

    public static function fromConfig(): self
    {
        return new self(
            (float)config('keystoneguru.combat_log_encounter_observations.mechanics.recurrence_ratio'),
            (int)config('keystoneguru.combat_log_encounter_observations.mechanics.first_time_tolerance_ms'),
            (float)config('keystoneguru.combat_log_encounter_observations.mechanics.first_time_tolerance_ratio'),
            (float)config('keystoneguru.combat_log_encounter_observations.mechanics.first_health_tolerance_pct'),
            (int)config('keystoneguru.combat_log_encounter_observations.mechanics.adds_min_first_ms'),
            (float)config('keystoneguru.combat_log_encounter_observations.mechanics.invulnerable_min_health_pct'),
            (int)config('keystoneguru.combat_log_encounter_observations.mechanics.movement_min_first_ms'),
            (float)config('keystoneguru.combat_log_encounter_observations.mechanics.movement_max_casts_per_window'),
            (float)config('keystoneguru.combat_log_encounter_observations.mechanics.affix_exempt_tolerance'),
            (int)config('keystoneguru.keystone.affix_scaling_factor_both_min_key_level'),
        );
    }

    /**
     * @param list<int|float> $values
     */
    private static function median(array $values): float
    {
        sort($values);
        $count  = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1 ? (float)$values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
