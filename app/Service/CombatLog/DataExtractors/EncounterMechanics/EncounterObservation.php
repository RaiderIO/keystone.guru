<?php

namespace App\Service\CombatLog\DataExtractors\EncounterMechanics;

/**
 * Everything one boss encounter attempt showed about its units, from ENCOUNTER_START to ENCOUNTER_END. Units are
 * tracked per GUID on raw strings only (no Guid objects, no models) and reduced to per-NPC facts in
 * {@see toData()}. Nothing here decides what a mechanic is: that is the aggregation's job.
 *
 * All times are milliseconds since ENCOUNTER_START, all HP percentages 0-100.
 */
class EncounterObservation
{
    public const string REACTION_HOSTILE  = 'hostile';
    public const string REACTION_NEUTRAL  = 'neutral';
    public const string REACTION_FRIENDLY = 'friendly';

    /** COMBATLOG_OBJECT_CONTROL_PLAYER: players, their pets, guardians and totems */
    public const int UNIT_FLAG_CONTROL_PLAYER = 0x100;

    private const int UNIT_FLAG_REACTION_FRIENDLY = 0x10;
    private const int UNIT_FLAG_REACTION_NEUTRAL  = 0x20;
    private const int UNIT_FLAG_REACTION_HOSTILE  = 0x40;

    /** Distinct GUIDs first seen within this many ms of the previous one belong to the same wave */
    public const int WAVE_GAP_MS = 3000;

    /** A stretch without player damage this long or longer is a no-damage window */
    public const int NO_DAMAGE_WINDOW_MIN_MS = 5000;

    /** Auras applied this close to a no-damage window's start are reported with it */
    public const int NO_DAMAGE_WINDOW_AURA_MARGIN_MS = 3000;

    /** Only units that took at least this many player damage events report their no-damage windows */
    public const int NO_DAMAGE_WINDOW_MIN_PLAYER_DAMAGE_EVENTS = 100;

    public const int SHARED_HEALTH_MAX_DIFF_PCT = 3;

    public const int SHARED_HEALTH_MIN_PAIRED_SAMPLES = 10;

    /**
     * Over the paired samples both units must have moved at least this many HP points, or agreeing proves nothing.
     * Uncoiled Writhes share one pool yet each spans only 4-13 points of it while alive.
     */
    public const int SHARED_HEALTH_MIN_PAIRED_SPAN_PCT = 3;

    public const int SHARED_HEALTH_MAX_MIN_HP_PCT = 70;

    /** The shared-health comparison is pairwise, so it only looks at the most-damaged units */
    public const int SHARED_HEALTH_MAX_CANDIDATES = 50;

    public const int MAX_LIFETIMES_PER_NPC = 50;

    private const int MAX_NO_DAMAGE_WINDOWS_PER_UNIT = 50;

    private const int MAX_RECENT_AURAS_PER_UNIT = 64;

    /** One HP sample per unit per second, for at most this many seconds */
    private const int MAX_HP_SAMPLES_PER_UNIT = 1800;

    private const int MAX_UNITS = 1000;

    /** A movement window compares each player's position this many seconds apart */
    public const int MOVEMENT_SPAN_SECONDS = 3;

    /** All players but one must have moved at least this many yards over the span */
    public const int MOVEMENT_MIN_DISTANCE = 12;

    /** A second only counts as moving when at least this many players have a position at both ends of the span */
    public const int MOVEMENT_MIN_PLAYERS = 4;

    /** Moving seconds this close together form one movement window */
    public const int MOVEMENT_MAX_GAP_SECONDS = 2;

    /** Casts this long before a movement window's start are reported with it */
    public const int MOVEMENT_CAST_MS_BEFORE = 4000;

    /** Casts this long after a movement window's start are reported with it: a slam can land well into the move */
    public const int MOVEMENT_CAST_MS_AFTER = self::MOVEMENT_SPAN_SECONDS * 1000;

    public const int MOVEMENT_MAX_CAST_SPELL_IDS = 10;

    /** One position per player per second, for at most this many seconds */
    private const int MAX_POSITIONS_PER_PLAYER = 1800;

    private const int MAX_PLAYERS = 40;

    private const int MAX_CASTS = 10000;

    /** cast_counts keeps only this many of the most cast spells */
    public const int MAX_CAST_COUNT_SPELLS = 50;

    /** A cast starting less than this long after the previous cast of the same spell, by any caster, extends that burst in cast_counts */
    public const int CAST_COUNT_BURST_GAP_MS = 3000;

    private const int NPC_ID_GUID_PART = 5;

    /**
     * @var array<string, array{
     *     npcId: int,
     *     firstSeenMs: int,
     *     reaction: string,
     *     playerOwned: bool,
     *     summonedByNpcId: int|null,
     *     bossHpPctAtFirstSeen: float|null,
     *     maxHp: int,
     *     hpPct: float|null,
     *     minHpPct: float|null,
     *     hpSamples: array<int, float>,
     *     playerDamageEvents: int,
     *     lastPlayerDamageMs: int|null,
     *     hpPctAtLastPlayerDamage: float|null,
     *     recentAuras: list<array{int, int}>,
     *     noDamageWindows: list<array{t: int, duration_ms: int, hp_pct: float|null, aura_spell_ids: list<int>}>,
     *     deaths: int,
     *     diedMs: int|null,
     * }> keyed by GUID
     */
    private array $units = [];

    /** @var array<string, array<int, array{float, float, int}>> player GUID => second => [x, y, ui map id] */
    private array $playerPositions = [];

    /** @var list<array{int, int, string, bool}> [ms since start, spell id, caster GUID, is SPELL_CAST_START] */
    private array $casts = [];

    private ?string $mostPlayerDamagedGuid = null;

    private int $mostPlayerDamageEvents = 0;

    public function __construct(
        public readonly int  $encounterId,
        public readonly int  $dungeonId,
        public readonly ?int $keyLevel,
        public readonly ?int $affixGroupId,
        public readonly int  $startMs,
    ) {
    }

    /**
     * Starts tracking a Creature or Vehicle GUID the first time it shows up. A unit whose flags carry no reaction
     * yet is picked up at its next sighting instead.
     */
    public function seeUnit(string $guid, string $flags, int $timestampMs): void
    {
        if (isset($this->units[$guid]) ||
            (!str_starts_with($guid, 'Creature-') && !str_starts_with($guid, 'Vehicle-')) ||
            count($this->units) >= self::MAX_UNITS) {
            return;
        }

        $unitFlags = self::parseUnitFlags($flags);
        if (($unitFlags & self::UNIT_FLAG_REACTION_HOSTILE) !== 0) {
            $reaction = self::REACTION_HOSTILE;
        } elseif (($unitFlags & self::UNIT_FLAG_REACTION_NEUTRAL) !== 0) {
            $reaction = self::REACTION_NEUTRAL;
        } elseif (($unitFlags & self::UNIT_FLAG_REACTION_FRIENDLY) !== 0) {
            $reaction = self::REACTION_FRIENDLY;
        } else {
            return;
        }

        $parts = explode('-', $guid);

        $this->units[$guid] = [
            'npcId'                   => (int)($parts[self::NPC_ID_GUID_PART] ?? 0),
            'firstSeenMs'             => $timestampMs - $this->startMs,
            'reaction'                => $reaction,
            'playerOwned'             => ($unitFlags & self::UNIT_FLAG_CONTROL_PLAYER) !== 0,
            'summonedByNpcId'         => null,
            'bossHpPctAtFirstSeen'    => $this->mostPlayerDamagedGuid === null ? null : $this->units[$this->mostPlayerDamagedGuid]['hpPct'],
            'maxHp'                   => 0,
            'hpPct'                   => null,
            'minHpPct'                => null,
            'hpSamples'               => [],
            'playerDamageEvents'      => 0,
            'lastPlayerDamageMs'      => null,
            'hpPctAtLastPlayerDamage' => null,
            'recentAuras'             => [],
            'noDamageWindows'         => [],
            'deaths'                  => 0,
            'diedMs'                  => null,
        ];
    }

    public function isTracked(string $guid): bool
    {
        return isset($this->units[$guid]);
    }

    /**
     * Whether a source is a player or something a player owns. A summon can carry NPC flags while its ownership is
     * known from its SPELL_SUMMON or its owner GUID.
     */
    public function isPlayerSource(string $guid, string $flags): bool
    {
        return ($this->units[$guid]['playerOwned'] ?? false) || self::isPlayerControlled($guid, $flags);
    }

    public function markPlayerOwned(string $guid): void
    {
        if (isset($this->units[$guid])) {
            $this->units[$guid]['playerOwned'] = true;
        }
    }

    public function recordHealth(string $guid, int $currentHp, int $maxHp, int $timestampMs): void
    {
        if ($maxHp <= 0 || !isset($this->units[$guid])) {
            return;
        }

        $unit = &$this->units[$guid];

        $hpPct         = 100 * $currentHp / $maxHp;
        $unit['hpPct'] = $hpPct;
        if ($maxHp > $unit['maxHp']) {
            $unit['maxHp'] = $maxHp;
        }

        if ($unit['minHpPct'] === null || $hpPct < $unit['minHpPct']) {
            $unit['minHpPct'] = $hpPct;
        }

        $second = intdiv($timestampMs - $this->startMs, 1000);
        if (isset($unit['hpSamples'][$second]) || count($unit['hpSamples']) < self::MAX_HP_SAMPLES_PER_UNIT) {
            $unit['hpSamples'][$second] = $hpPct;
        }
    }

    /**
     * A damage event from a player-controlled source onto a tracked unit. The gap since the unit's previous one
     * becomes a no-damage window when it is long enough - the unit evidently is alive at its end.
     */
    public function recordPlayerDamage(string $guid, int $timestampMs): void
    {
        if (!isset($this->units[$guid])) {
            return;
        }

        $unit = &$this->units[$guid];

        $lastPlayerDamageMs = $unit['lastPlayerDamageMs'];
        if ($lastPlayerDamageMs !== null &&
            $timestampMs - $lastPlayerDamageMs >= self::NO_DAMAGE_WINDOW_MIN_MS &&
            count($unit['noDamageWindows']) < self::MAX_NO_DAMAGE_WINDOWS_PER_UNIT) {
            $auraSpellIds = [];
            foreach ($unit['recentAuras'] as [$auraMs, $spellId]) {
                if (abs($auraMs - $lastPlayerDamageMs) <= self::NO_DAMAGE_WINDOW_AURA_MARGIN_MS) {
                    $auraSpellIds[$spellId] = $spellId;
                }
            }

            sort($auraSpellIds);

            $unit['noDamageWindows'][] = [
                't'              => $lastPlayerDamageMs - $this->startMs,
                'duration_ms'    => $timestampMs - $lastPlayerDamageMs,
                'hp_pct'         => $unit['hpPctAtLastPlayerDamage'],
                'aura_spell_ids' => $auraSpellIds,
            ];
        }

        $unit['lastPlayerDamageMs']      = $timestampMs;
        $unit['hpPctAtLastPlayerDamage'] = $unit['hpPct'];
        $unit['playerDamageEvents']++;

        if ($unit['recentAuras'] !== []) {
            $unit['recentAuras'] = array_values(array_filter(
                $unit['recentAuras'],
                static fn(array $aura) => $aura[0] >= $timestampMs - self::NO_DAMAGE_WINDOW_AURA_MARGIN_MS,
            ));
        }

        if ($unit['playerDamageEvents'] > $this->mostPlayerDamageEvents && !$unit['playerOwned']) {
            $this->mostPlayerDamagedGuid  = $guid;
            $this->mostPlayerDamageEvents = $unit['playerDamageEvents'];
        }
    }

    /**
     * The in-game position of a player; the last one in each second of the encounter is kept.
     */
    public function recordPlayerPosition(string $playerGuid, float $x, float $y, int $uiMapId, int $timestampMs): void
    {
        if (!isset($this->playerPositions[$playerGuid])) {
            if (count($this->playerPositions) >= self::MAX_PLAYERS) {
                return;
            }

            $this->playerPositions[$playerGuid] = [];
        }

        $second = intdiv($timestampMs - $this->startMs, 1000);
        if (isset($this->playerPositions[$playerGuid][$second]) || count($this->playerPositions[$playerGuid]) < self::MAX_POSITIONS_PER_PLAYER) {
            $this->playerPositions[$playerGuid][$second] = [$x, $y, $uiMapId];
        }
    }

    /**
     * A SPELL_CAST_START or SPELL_CAST_SUCCESS by a tracked unit. Whether its caster counts is decided once the
     * encounter is over and the caster's ownership is known.
     */
    public function recordCast(string $casterGuid, int $spellId, bool $isCastStart, int $timestampMs): void
    {
        if (isset($this->units[$casterGuid]) && count($this->casts) < self::MAX_CASTS) {
            $this->casts[] = [$timestampMs - $this->startMs, $spellId, $casterGuid, $isCastStart];
        }
    }

    /**
     * An aura a non-player applied to a tracked unit.
     */
    public function recordAuraApplied(string $guid, int $spellId, int $timestampMs): void
    {
        if (!isset($this->units[$guid])) {
            return;
        }

        $unit = &$this->units[$guid];
        if (count($unit['recentAuras']) >= self::MAX_RECENT_AURAS_PER_UNIT) {
            array_shift($unit['recentAuras']);
        }

        $unit['recentAuras'][] = [$timestampMs, $spellId];
    }

    public function recordSummon(string $sourceGuid, bool $sourceIsPlayerControlled, string $destGuid): void
    {
        if (!isset($this->units[$destGuid])) {
            return;
        }

        if ($sourceIsPlayerControlled || ($this->units[$sourceGuid]['playerOwned'] ?? false)) {
            $this->units[$destGuid]['playerOwned'] = true;
        } elseif (isset($this->units[$sourceGuid]) && $this->units[$destGuid]['summonedByNpcId'] === null) {
            $this->units[$destGuid]['summonedByNpcId'] = $this->units[$sourceGuid]['npcId'];
        }
    }

    public function recordDeath(string $guid, int $timestampMs): void
    {
        if (!isset($this->units[$guid])) {
            return;
        }

        $this->units[$guid]['deaths']++;
        $this->units[$guid]['diedMs'] ??= $timestampMs - $this->startMs;
    }

    /**
     * @return array{
     *     npcs: array<int, array{
     *         reaction: string,
     *         max_hp: int|null,
     *         count: int,
     *         player_damage_events: int,
     *         deaths: int,
     *         summoned_by_npc_id: int|null,
     *         min_hp_pct: float|null,
     *         waves: list<array{t: int, count: int, boss_hp_pct: float|null}>,
     *         lifetimes_ms: list<int>,
     *     }>,
     *     no_damage_windows: list<array{npc_id: int, t: int, duration_ms: int, hp_pct: float|null, aura_spell_ids: list<int>}>,
     *     shared_health: list<list<int>>,
     *     movement_windows: list<array{t: int, duration_ms: int, players: int, median_distance: float, cast_spell_ids: list<int>}>,
     *     cast_counts: array<int, int>,
     * }
     */
    public function toData(): array
    {
        $units = array_filter($this->units, static fn(array $unit) => !$unit['playerOwned']);
        uasort($units, static fn(array $a, array $b) => $a['firstSeenMs'] <=> $b['firstSeenMs']);

        return [
            'npcs'              => $this->buildNpcs($units),
            'no_damage_windows' => $this->buildNoDamageWindows($units),
            'shared_health'     => $this->buildSharedHealthGroups($units),
            'movement_windows'  => $this->buildMovementWindows(),
            'cast_counts'       => $this->buildCastCounts(),
        ];
    }

    public static function parseUnitFlags(string $flags): int
    {
        return (int)hexdec(substr($flags, 2));
    }

    public static function isPlayerControlled(string $guid, string $flags): bool
    {
        return str_starts_with($guid, 'Player-') ||
            str_starts_with($guid, 'Pet-') ||
            (self::parseUnitFlags($flags) & self::UNIT_FLAG_CONTROL_PLAYER) !== 0;
    }

    /**
     * @param  array<string, array<string, mixed>> $units non-player-owned, ordered by first sighting
     * @return array<int, array<string, mixed>>
     */
    private function buildNpcs(array $units): array
    {
        /** @var array<int, list<array<string, mixed>>> $unitsByNpcId */
        $unitsByNpcId = [];
        foreach ($units as $unit) {
            $unitsByNpcId[$unit['npcId']][] = $unit;
        }

        $npcs = [];
        foreach ($unitsByNpcId as $npcId => $npcUnits) {
            $maxHp              = 0;
            $minHpPct           = null;
            $playerDamageEvents = 0;
            $deaths             = 0;
            $summonedByNpcId    = null;
            $lifetimesMs        = [];
            $waves              = [];
            $lastFirstSeenMs    = null;

            foreach ($npcUnits as $unit) {
                $maxHp = max($maxHp, $unit['maxHp']);
                if ($unit['minHpPct'] !== null && ($minHpPct === null || $unit['minHpPct'] < $minHpPct)) {
                    $minHpPct = $unit['minHpPct'];
                }

                $playerDamageEvents += $unit['playerDamageEvents'];
                $deaths += $unit['deaths'];
                $summonedByNpcId ??= $unit['summonedByNpcId'];

                if ($unit['diedMs'] !== null && count($lifetimesMs) < self::MAX_LIFETIMES_PER_NPC) {
                    $lifetimesMs[] = $unit['diedMs'] - $unit['firstSeenMs'];
                }

                if ($lastFirstSeenMs !== null && $unit['firstSeenMs'] - $lastFirstSeenMs <= self::WAVE_GAP_MS) {
                    $waves[count($waves) - 1]['count']++;
                } else {
                    $waves[] = [
                        't'           => $unit['firstSeenMs'],
                        'count'       => 1,
                        'boss_hp_pct' => self::roundPct($unit['bossHpPctAtFirstSeen']),
                    ];
                }

                $lastFirstSeenMs = $unit['firstSeenMs'];
            }

            $npcs[$npcId] = [
                'reaction'             => $npcUnits[0]['reaction'],
                'max_hp'               => $maxHp > 0 ? $maxHp : null,
                'count'                => count($npcUnits),
                'player_damage_events' => $playerDamageEvents,
                'deaths'               => $deaths,
                'summoned_by_npc_id'   => $summonedByNpcId,
                'min_hp_pct'           => self::roundPct($minHpPct),
                'waves'                => $waves,
                'lifetimes_ms'         => $lifetimesMs,
            ];
        }

        return $npcs;
    }

    /**
     * @param  array<string, array<string, mixed>>                                                               $units
     * @return list<array{npc_id: int, t: int, duration_ms: int, hp_pct: float|null, aura_spell_ids: list<int>}>
     */
    private function buildNoDamageWindows(array $units): array
    {
        $noDamageWindows = [];
        foreach ($units as $unit) {
            if ($unit['playerDamageEvents'] < self::NO_DAMAGE_WINDOW_MIN_PLAYER_DAMAGE_EVENTS) {
                continue;
            }

            foreach ($unit['noDamageWindows'] as $noDamageWindow) {
                $noDamageWindows[] = [
                    'npc_id'         => $unit['npcId'],
                    't'              => $noDamageWindow['t'],
                    'duration_ms'    => $noDamageWindow['duration_ms'],
                    'hp_pct'         => self::roundPct($noDamageWindow['hp_pct']),
                    'aura_spell_ids' => $noDamageWindow['aura_spell_ids'],
                ];
            }
        }

        usort($noDamageWindows, static fn(array $a, array $b) => [$a['t'], $a['npc_id']] <=> [$b['t'], $b['npc_id']]);

        return $noDamageWindows;
    }

    /**
     * Groups of units with the same max HP whose HP % moved in lockstep while they lost a fair share of it.
     *
     * @param  array<string, array<string, mixed>> $units
     * @return list<list<int>>                     npc ids per group, one entry per unit
     */
    private function buildSharedHealthGroups(array $units): array
    {
        $candidates = array_filter($units, static fn(array $unit) => $unit['maxHp'] > 0 &&
            $unit['playerDamageEvents'] > 0 &&
            $unit['minHpPct'] !== null &&
            $unit['minHpPct'] < self::SHARED_HEALTH_MAX_MIN_HP_PCT &&
            count($unit['hpSamples']) >= self::SHARED_HEALTH_MIN_PAIRED_SAMPLES);

        uasort($candidates, static fn(array $a, array $b) => $b['playerDamageEvents'] <=> $a['playerDamageEvents']);
        $candidates = array_slice($candidates, 0, self::SHARED_HEALTH_MAX_CANDIDATES, true);

        /** @var array<int, list<string>> $guidsByMaxHp */
        $guidsByMaxHp = [];
        foreach ($candidates as $guid => $unit) {
            $guidsByMaxHp[$unit['maxHp']][] = (string)$guid;
        }

        /** @var array<string, string> $parentByGuid union-find over the matching pairs */
        $parentByGuid = [];
        $findRoot     = static function (string $guid) use (&$parentByGuid): string {
            while ($parentByGuid[$guid] !== $guid) {
                $guid = $parentByGuid[$guid];
            }

            return $guid;
        };

        foreach ($guidsByMaxHp as $guids) {
            $guidCount = count($guids);
            for ($i = 0; $i < $guidCount; $i++) {
                for ($j = $i + 1; $j < $guidCount; $j++) {
                    if (!$this->movedInLockstep($candidates[$guids[$i]]['hpSamples'], $candidates[$guids[$j]]['hpSamples'])) {
                        continue;
                    }

                    $parentByGuid[$guids[$i]] ??= $guids[$i];
                    $parentByGuid[$guids[$j]] ??= $guids[$j];
                    $parentByGuid[$findRoot($guids[$j])] = $findRoot($guids[$i]);
                }
            }
        }

        /** @var array<string, list<int>> $npcIdsByRoot */
        $npcIdsByRoot = [];
        foreach (array_keys($parentByGuid) as $guid) {
            $npcIdsByRoot[$findRoot($guid)][] = $candidates[$guid]['npcId'];
        }

        $groups = [];
        foreach ($npcIdsByRoot as $npcIds) {
            sort($npcIds);
            $groups[] = $npcIds;
        }

        sort($groups);

        return $groups;
    }

    /**
     * Stretches in which (nearly) the whole party was displaced far within a few seconds, with the casts of hostile
     * and neutral units around their start.
     *
     * @return list<array{t: int, duration_ms: int, players: int, median_distance: float, cast_spell_ids: list<int>}>
     */
    private function buildMovementWindows(): array
    {
        /** @var array<int, list<float>> $distancesBySecond */
        $distancesBySecond = [];
        foreach ($this->playerPositions as $positions) {
            foreach ($positions as $second => [$x, $y, $uiMapId]) {
                $later = $positions[$second + self::MOVEMENT_SPAN_SECONDS] ?? null;
                if ($later === null || $later[2] !== $uiMapId) {
                    continue;
                }

                $distancesBySecond[$second][] = sqrt(($later[0] - $x) ** 2 + ($later[1] - $y) ** 2);
            }
        }

        ksort($distancesBySecond);

        /** @var list<array{first: int, last: int, distances: list<float>}> $windows */
        $windows = [];
        foreach ($distancesBySecond as $second => $distances) {
            if (count($distances) < self::MOVEMENT_MIN_PLAYERS) {
                continue;
            }

            sort($distances);
            if ($distances[1] < self::MOVEMENT_MIN_DISTANCE) {
                continue;
            }

            $lastWindowIndex = count($windows) - 1;
            if ($lastWindowIndex >= 0 && $second - $windows[$lastWindowIndex]['last'] <= self::MOVEMENT_MAX_GAP_SECONDS) {
                $windows[$lastWindowIndex]['last'] = $second;
            } else {
                $windows[] = ['first' => $second, 'last' => $second, 'distances' => $distances];
            }
        }

        $movementWindows = [];
        foreach ($windows as $window) {
            $startMs      = $window['first'] * 1000;
            $castSpellIds = [];
            foreach ($this->casts as [$castMs, $spellId, $casterGuid]) {
                if ($castMs < $startMs - self::MOVEMENT_CAST_MS_BEFORE || $castMs > $startMs + self::MOVEMENT_CAST_MS_AFTER) {
                    continue;
                }

                $caster = $this->units[$casterGuid];
                if ($caster['playerOwned'] || $caster['reaction'] === self::REACTION_FRIENDLY) {
                    continue;
                }

                $castSpellIds[$spellId] = $spellId;
                if (count($castSpellIds) >= self::MOVEMENT_MAX_CAST_SPELL_IDS) {
                    break;
                }
            }

            $movementWindows[] = [
                't'               => $startMs,
                'duration_ms'     => ($window['last'] + self::MOVEMENT_SPAN_SECONDS - $window['first']) * 1000,
                'players'         => count($window['distances']),
                'median_distance' => round(self::median($window['distances']), 1),
                'cast_spell_ids'  => array_values($castSpellIds),
            ];
        }

        return $movementWindows;
    }

    /**
     * How often each spell was cast by hostile and neutral units, counting a cast once: by its SPELL_CAST_START when
     * the spell has one, else by its SPELL_CAST_SUCCESS (instants and channels log no start). Casts of a spell less than
     * CAST_COUNT_BURST_GAP_MS apart form one burst, which counts once whoever cast them: Taz'Rah and five of its
     * shadows (another npc id) all log Umbral Rupture at the same moment.
     *
     * @return array<int, int> spell id => casts
     */
    private function buildCastCounts(): array
    {
        /** @var list<array{int, int, bool}> $eligibleCasts [ms since start, spell id, is start] */
        $eligibleCasts      = [];
        $spellIdsWithAStart = [];
        foreach ($this->casts as [$castMs, $spellId, $casterGuid, $isCastStart]) {
            $caster = $this->units[$casterGuid];
            if ($caster['playerOwned'] || $caster['reaction'] === self::REACTION_FRIENDLY) {
                continue;
            }

            $eligibleCasts[] = [$castMs, $spellId, $isCastStart];
            if ($isCastStart) {
                $spellIdsWithAStart[$spellId] = true;
            }
        }

        $castCounts               = [];
        $lastBurstCastMsBySpellId = [];
        foreach ($eligibleCasts as [$castMs, $spellId, $isCastStart]) {
            if ($isCastStart !== isset($spellIdsWithAStart[$spellId])) {
                continue;
            }

            if (!isset($lastBurstCastMsBySpellId[$spellId]) || $castMs - $lastBurstCastMsBySpellId[$spellId] >= self::CAST_COUNT_BURST_GAP_MS) {
                $castCounts[$spellId] = ($castCounts[$spellId] ?? 0) + 1;
            }

            $lastBurstCastMsBySpellId[$spellId] = $castMs;
        }

        uksort($castCounts, static fn(int $a, int $b) => [$castCounts[$b], $a] <=> [$castCounts[$a], $b]);

        return array_slice($castCounts, 0, self::MAX_CAST_COUNT_SPELLS, true);
    }

    /**
     * @param list<float> $sortedValues
     */
    private static function median(array $sortedValues): float
    {
        $count  = count($sortedValues);
        $middle = intdiv($count, 2);

        return $count % 2 === 1 ? $sortedValues[$middle] : ($sortedValues[$middle - 1] + $sortedValues[$middle]) / 2;
    }

    /**
     * @param array<int, float> $hpSamplesA
     * @param array<int, float> $hpSamplesB
     */
    private function movedInLockstep(array $hpSamplesA, array $hpSamplesB): bool
    {
        $pairedSamples = 0;
        $minHpPctA     = $maxHpPctA = $minHpPctB = $maxHpPctB = null;
        foreach ($hpSamplesA as $second => $hpPctA) {
            if (!isset($hpSamplesB[$second])) {
                continue;
            }

            $hpPctB = $hpSamplesB[$second];
            if (abs($hpPctA - $hpPctB) > self::SHARED_HEALTH_MAX_DIFF_PCT) {
                return false;
            }

            $minHpPctA = min($minHpPctA ?? $hpPctA, $hpPctA);
            $maxHpPctA = max($maxHpPctA ?? $hpPctA, $hpPctA);
            $minHpPctB = min($minHpPctB ?? $hpPctB, $hpPctB);
            $maxHpPctB = max($maxHpPctB ?? $hpPctB, $hpPctB);
            $pairedSamples++;
        }

        return $pairedSamples >= self::SHARED_HEALTH_MIN_PAIRED_SAMPLES &&
            $maxHpPctA - $minHpPctA >= self::SHARED_HEALTH_MIN_PAIRED_SPAN_PCT &&
            $maxHpPctB - $minHpPctB >= self::SHARED_HEALTH_MIN_PAIRED_SPAN_PCT;
    }

    private static function roundPct(?float $pct): ?float
    {
        return $pct === null ? null : round($pct, 1);
    }
}
