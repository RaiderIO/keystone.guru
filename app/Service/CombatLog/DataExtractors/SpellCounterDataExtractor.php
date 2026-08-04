<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Range;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\AuraRemovedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\CastFailed;
use App\Logic\CombatLog\CombatEvents\Suffixes\CastStart;
use App\Logic\CombatLog\CombatEvents\Suffixes\CastSuccess;
use App\Logic\CombatLog\CombatEvents\Suffixes\Interrupt;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Guid;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell as SpellModel;
use App\Service\CombatLog\DataExtractors\Logging\SpellCounterDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\SpellCounters\SpellCounterDefinitionInterface;
use App\Service\CombatLog\DataExtractors\SpellCounters\SpellCounterDefinitions;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Detects "spell counter" tricks - a player using a counter ability (Rogue Vanish, Night Elf Shadowmeld) to make an
 * incoming NPC ability fizzle - and records them on the Spell that was countered.
 *
 * Three signatures are recognized:
 * - A: an NPC targeting debuff (applied with a nil source guid, linked back to the NPC's SPELL_CAST_START) is dropped
 *   within EPSILON_MS of a counter cast. The fact attaches to the NPC's *cast* spell id.
 * - B: an NPC-sourced debuff (a channel) on the counter-caster is removed within EPSILON_MS of the counter cast, well
 *   before its natural duration. The fact attaches to the debuff's own spell id.
 * - C: an NPC SPELL_CAST_START never reaches SPELL_CAST_SUCCESS and the same caster starts a fresh cast shortly after
 *   a counter cast, with no other explanation (no fail, no interrupt, no death, no loss-of-control debuff on the
 *   caster).
 */
class SpellCounterDataExtractor implements DataExtractorInterface
{
    /**
     * Two-sided window between a counter cast and the debuff removal it caused. The server handles both in the same
     * tick, so every verified true positive correlates within 0-1 ms; a churning AoE debuff whose natural removal
     * landed 392 ms from an unrelated Vanish was the only observed false positive, so 100 ms keeps ample slack
     * without admitting coincidences.
     */
    public const int EPSILON_MS = 100;

    /** @var int Window in which a nil-source debuff is linked back to a temporally adjacent NPC SPELL_CAST_START. */
    public const int CAST_START_LINK_WINDOW_MS = 500;

    /** @var int Maximum gap between a counter cast and the CAST_START that supersedes an abandoned cast. */
    public const int C_SUPERSEDE_WINDOW_MS = 3000;

    /** @var int Pending NPC casts older than this are considered stale and are evicted without resolving. */
    public const int PENDING_CAST_MAX_AGE_MS = 15000;

    /** @var int Debuffs older than this are pruned - they will never be attributed to a counter anymore. */
    public const int ACTIVE_DEBUFF_MAX_AGE_MS = 300000;

    /**
     * Debuff mechanics that plausibly explain an NPC abandoning a cast (loss of control). Damage and utility debuffs
     * (bleeds, Judgment, Consecration, ...) land on trash NPCs near-constantly mid-combat and must not veto signature
     * C, or it would never fire in practice.
     *
     * @var array<int, string>
     */
    public const array CAST_DISTURBING_MECHANICS = [
        SpellModel::MECHANIC_ASLEEP,
        SpellModel::MECHANIC_BANISHED,
        SpellModel::MECHANIC_CHARMED,
        SpellModel::MECHANIC_DISORIENTED,
        SpellModel::MECHANIC_FLEEING,
        SpellModel::MECHANIC_FROZEN,
        SpellModel::MECHANIC_GRIPPED,
        SpellModel::MECHANIC_HORRIFIED,
        SpellModel::MECHANIC_INCAPACITATED,
        SpellModel::MECHANIC_INTERRUPTED,
        SpellModel::MECHANIC_POLYMORPHED,
        SpellModel::MECHANIC_SAPPED,
        SpellModel::MECHANIC_SHACKLED,
        SpellModel::MECHANIC_SILENCED,
        SpellModel::MECHANIC_STUNNED,
        SpellModel::MECHANIC_TURNED,
    ];

    /** @var Collection<int, SpellCounterDefinitionInterface> Keyed by trigger cast spell id. */
    private readonly Collection $definitionsByTriggerSpellId;

    /** @var Collection<string, SpellCounterDefinitionInterface> Keyed by SpellProperty value. */
    private readonly Collection $definitionsByProperty;

    /**
     * Debuffs currently applied to players by a nil source or an NPC creature.
     *
     * @var Collection<string, array{appliedAt: Carbon, sourceGuid: string|null, spellId: int, linkedNpcCastSpellId: int|null}>
     */
    private Collection $activeDebuffs;

    /**
     * Recent NPC SPELL_CAST_START events, used to link nil-source debuffs back to the cast that applied them.
     *
     * @var Collection<int, array{ts: Carbon, casterGuid: string, spellId: int, spellName: string}>
     */
    private Collection $recentNpcCastStarts;

    /**
     * The single in-flight cast per NPC caster guid.
     *
     * @var Collection<string, array{spellId: int, startedAt: Carbon, disturbed: bool}>
     */
    private Collection $pendingNpcCasts;

    /**
     * Recent player counter casts.
     *
     * @var Collection<int, array{ts: Carbon, playerGuid: string, definition: SpellCounterDefinitionInterface}>
     */
    private Collection $recentCounterCasts;

    /**
     * Debuff removals seen *before* the counter cast that caused them - the backward half of the two-sided window.
     *
     * @var Collection<int, array{ts: Carbon, playerGuid: string, attributedSpellId: int, appliedAt: Carbon, signature: string}>
     */
    private Collection $recentDebuffRemovals;

    /**
     * All (spell_id, property) counter pairs detected this session - batch-upserted in afterExtract.
     *
     * @var Collection<string, array{spell_id: int, property: SpellProperty}>
     */
    private Collection $pendingCounterObservations;

    /**
     * Lazily memoized `spells`.`duration` per spell id, in milliseconds. False marks a spell that has no row.
     *
     * @var Collection<int, int|null|false>
     */
    private Collection $spellDurationCache;

    /**
     * Lazily memoized `spells`.`mechanic` per spell id. False marks a spell that has no row.
     *
     * @var Collection<int, string|null|false>
     */
    private Collection $spellMechanicCache;

    private readonly SpellCounterDataExtractorLoggingInterface $log;

    private ?string $currentCombatLogFilePath = null;

    public function __construct()
    {
        $definitionsByTriggerSpellId = collect();
        $definitionsByProperty       = collect();

        foreach (SpellCounterDefinitions::all() as $definition) {
            foreach ($definition->getTriggerCastSpellIds() as $triggerCastSpellId) {
                $definitionsByTriggerSpellId->put($triggerCastSpellId, $definition);
            }

            $definitionsByProperty->put($definition->getProperty()->value, $definition);
        }

        $this->definitionsByTriggerSpellId = $definitionsByTriggerSpellId;
        $this->definitionsByProperty       = $definitionsByProperty;

        $this->pendingCounterObservations = collect();
        $this->spellDurationCache         = collect();
        $this->spellMechanicCache         = collect();
        $this->resetCorrelationState();

        $log = App::make(SpellCounterDataExtractorLoggingInterface::class);
        /** @var SpellCounterDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->currentCombatLogFilePath = $combatLogFilePath;
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
        // A new run invalidates every in-flight correlation
        if ($parsedEvent instanceof ChallengeModeStart ||
            $parsedEvent instanceof ChallengeModeEnd ||
            $parsedEvent instanceof ZoneChange) {
            $this->resetCorrelationState();

            return;
        }

        // UnitDestroyed extends UnitDied - a dead caster explains its abandoned cast
        if ($parsedEvent instanceof UnitDied) {
            $destGuid = $parsedEvent->getGenericData()->getDestGuid();
            if ($destGuid !== null) {
                $this->pendingNpcCasts->forget($destGuid->getGuid());
            }

            return;
        }

        if (!($parsedEvent instanceof CombatLogEvent)) {
            return;
        }

        // Range carries the spell id/name and is the base of the Spell, SpellPeriodic and SpellBuilding prefixes
        $prefix = $parsedEvent->getPrefix();
        if (!($prefix instanceof Range)) {
            return;
        }

        $suffix      = $parsedEvent->getSuffix();
        $genericData = $parsedEvent->getGenericData();
        $sourceGuid  = $genericData->getSourceGuid();
        $destGuid    = $genericData->getDestGuid();
        $timestamp   = $parsedEvent->getTimestamp();

        $sourceNpcGuid = $this->npcCreatureGuid($sourceGuid);
        $destNpcGuid   = $this->npcCreatureGuid($destGuid);

        if ($suffix instanceof CastStart) {
            if ($sourceNpcGuid !== null) {
                $this->handleNpcCastStart($sourceNpcGuid, $prefix->getSpellId(), $prefix->getSpellName(), $timestamp);
            }
        } elseif ($suffix instanceof CastSuccess) {
            if ($sourceGuid instanceof Player) {
                $this->handlePlayerCastSuccess($sourceGuid->getGuid(), $prefix->getSpellId(), $timestamp);
            } elseif ($sourceNpcGuid !== null) {
                // The cast resolved - it needs no further explanation
                $this->pendingNpcCasts->forget($sourceNpcGuid);
            }
        } elseif ($suffix instanceof CastFailed) {
            if ($sourceNpcGuid !== null) {
                $this->pendingNpcCasts->forget($sourceNpcGuid);
            }
        } elseif ($suffix instanceof Interrupt) {
            // The interrupted caster is the *destination* of a SPELL_INTERRUPT
            if ($destNpcGuid !== null) {
                $this->pendingNpcCasts->forget($destNpcGuid);
            }
        } elseif ($suffix instanceof AuraAppliedInterface) {
            if ($suffix->getAuraType() === AuraBase::AURA_TYPE_DEBUFF) {
                $this->handleDebuffApplied($sourceGuid, $destGuid, $prefix->getSpellId(), $prefix->getSpellName(), $timestamp);
            }
        } elseif ($suffix instanceof AuraRemovedInterface) {
            if ($suffix->getAuraType() === AuraBase::AURA_TYPE_DEBUFF && $destGuid instanceof Player) {
                $this->handleDebuffRemoved($destGuid->getGuid(), $prefix->getSpellId(), $timestamp);
            }
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        if ($this->pendingCounterObservations->isNotEmpty()) {
            $now  = Carbon::now()->toDateTimeString();
            $rows = $this->pendingCounterObservations->map(fn(array $observation) => [
                'spell_id'        => $observation['spell_id'],
                'property'        => $observation['property']->value,
                'observed_on'     => Carbon::today()->toDateString(),
                'combat_log_path' => $this->currentCombatLogFilePath ?? '',
                'created_at'      => $now,
                'updated_at'      => $now,
            ])->values()->all();

            CombatLogSpellPropertyObservation::upsert(
                $rows,
                ['spell_id', 'property', 'observed_on'],
                ['combat_log_path', 'updated_at'],
            );

            /** @var Collection<int, SpellModel> $spells */
            $spells = SpellModel::query()
                ->whereIn('id', $this->pendingCounterObservations->pluck('spell_id')->unique()->all())
                ->get()
                ->keyBy('id');

            foreach ($this->pendingCounterObservations as $observation) {
                $this->applyCounterToSpell($result, $spells, $observation['spell_id'], $observation['property']);
            }
        }

        $this->pendingCounterObservations = collect();
        $this->spellDurationCache         = collect();
        $this->spellMechanicCache         = collect();
        $this->currentCombatLogFilePath   = null;
        $this->resetCorrelationState();
    }

    /**
     * Sets the counter bit on the spell that was countered, and writes the audit event for it.
     *
     * @param Collection<int, SpellModel> $spells
     */
    private function applyCounterToSpell(
        ExtractedDataResult $result,
        Collection          $spells,
        int                 $spellId,
        SpellProperty       $property,
    ): void {
        /** @var SpellModel|null $spell */
        $spell = $spells->get($spellId);
        if ($spell === null) {
            // The observation is kept regardless - the spell may be created by a later combat log
            $this->log->afterExtractSpellNotFound($spellId, $property->value);

            return;
        }

        /** @var SpellCounterDefinitionInterface|null $definition */
        $definition = $this->definitionsByProperty->get($property->value);
        if ($definition === null) {
            return;
        }

        $counterBit = $definition->getCounterBit();
        if (($spell->counters_mask & $counterBit) !== 0) {
            $this->log->afterExtractCounterAlreadyKnown($spellId, $property->value);

            return;
        }

        $spell->counters_mask |= $counterBit;

        if ($spell->save()) {
            CombatLogSpellEvent::create([
                'spell_id'        => $spellId,
                'event_type'      => CombatLogSpellEventType::PropertyChanged,
                'property'        => $property,
                'combat_log_path' => $this->currentCombatLogFilePath,
            ]);

            $result->addedSpellCounter();
        }
    }

    /**
     * Signature C: resolve the previous, unresolved cast of this caster before recording the new one.
     */
    private function handleNpcCastStart(string $casterGuid, int $spellId, string $spellName, Carbon $timestamp): void
    {
        /** @var array{spellId: int, startedAt: Carbon, disturbed: bool}|null $pendingCast */
        $pendingCast = $this->pendingNpcCasts->get($casterGuid);
        if ($pendingCast !== null) {
            $this->resolveAbandonedCast($casterGuid, $pendingCast, $timestamp);
        }

        $this->pendingNpcCasts->put($casterGuid, [
            'spellId'   => $spellId,
            'startedAt' => $timestamp,
            'disturbed' => false,
        ]);
        $this->prunePendingNpcCasts($timestamp);

        $this->recentNpcCastStarts->push([
            'ts'         => $timestamp,
            'casterGuid' => $casterGuid,
            'spellId'    => $spellId,
            'spellName'  => $spellName,
        ]);
        $this->pruneByAge($this->recentNpcCastStarts, $timestamp, self::CAST_START_LINK_WINDOW_MS);
    }

    /**
     * A cast that never resolved counts as countered only when nothing else explains it and a counter cast happened
     * while it was in flight.
     *
     * @param array{spellId: int, startedAt: Carbon, disturbed: bool} $pendingCast
     */
    private function resolveAbandonedCast(string $casterGuid, array $pendingCast, Carbon $timestamp): void
    {
        if ($pendingCast['disturbed']) {
            $this->log->extractDataAbandonedCastWasDisturbed($casterGuid, $pendingCast['spellId']);

            return;
        }

        $ageMs = $this->millisecondsBetween($pendingCast['startedAt'], $timestamp);
        if ($ageMs > self::PENDING_CAST_MAX_AGE_MS) {
            $this->log->extractDataAbandonedCastTooOld($casterGuid, $pendingCast['spellId'], $ageMs);

            return;
        }

        foreach ($this->recentCounterCasts->reverse() as $counterCast) {
            $counterOffsetMs = $this->millisecondsBetween($pendingCast['startedAt'], $counterCast['ts']);
            // The counter must have happened while the cast was in flight - a hair before it started is allowed
            // because same-instant ordering in the combat log is arbitrary
            if ($counterOffsetMs < -self::EPSILON_MS) {
                continue;
            }

            $supersedeGapMs = $this->millisecondsBetween($counterCast['ts'], $timestamp);
            if ($supersedeGapMs < 0 || $supersedeGapMs > self::C_SUPERSEDE_WINDOW_MS) {
                continue;
            }

            $this->queueCounterObservation('C', $pendingCast['spellId'], $counterCast['definition'], $counterCast['playerGuid']);

            return;
        }

        $this->log->extractDataAbandonedCastHasNoCounterInWindow($casterGuid, $pendingCast['spellId']);
    }

    /**
     * A player counter cast closes the backward half of the two-sided window: debuff removals seen just before it are
     * attributed retroactively.
     */
    private function handlePlayerCastSuccess(string $playerGuid, int $spellId, Carbon $timestamp): void
    {
        /** @var SpellCounterDefinitionInterface|null $definition */
        $definition = $this->definitionsByTriggerSpellId->get($spellId);
        if ($definition === null) {
            return;
        }

        $this->recentCounterCasts->push([
            'ts'         => $timestamp,
            'playerGuid' => $playerGuid,
            'definition' => $definition,
        ]);
        $this->pruneByAge($this->recentCounterCasts, $timestamp, self::C_SUPERSEDE_WINDOW_MS);

        foreach ($this->recentDebuffRemovals as $debuffRemoval) {
            if ($debuffRemoval['playerGuid'] !== $playerGuid) {
                continue;
            }

            if (abs($this->millisecondsBetween($debuffRemoval['ts'], $timestamp)) > self::EPSILON_MS) {
                continue;
            }

            $this->queueCounterObservation($debuffRemoval['signature'], $debuffRemoval['attributedSpellId'], $definition, $playerGuid);
        }
    }

    private function handleDebuffApplied(?Guid $sourceGuid, ?Guid $destGuid, int $spellId, string $spellName, Carbon $timestamp): void
    {
        // A loss-of-control debuff landing on an NPC mid-cast is a possible explanation for it abandoning that cast.
        // Plain damage/utility debuffs land on trash near-constantly and are not disqualifying.
        $destNpcGuid = $this->npcCreatureGuid($destGuid);
        if ($destNpcGuid !== null) {
            /** @var array{spellId: int, startedAt: Carbon, disturbed: bool}|null $pendingCast */
            $pendingCast = $this->pendingNpcCasts->get($destNpcGuid);
            if ($pendingCast !== null && $this->isCastDisturbingDebuff($spellId)) {
                $pendingCast['disturbed'] = true;
                $this->pendingNpcCasts->put($destNpcGuid, $pendingCast);
            }

            return;
        }

        if (!($destGuid instanceof Player)) {
            return;
        }

        // Only NPC-inflicted debuffs are interesting - a nil source is how targeting debuffs are logged
        if ($sourceGuid !== null && $this->npcCreatureGuid($sourceGuid) === null) {
            return;
        }

        $this->activeDebuffs->put($this->activeDebuffKey($destGuid->getGuid(), $spellId), [
            'appliedAt'            => $timestamp,
            'sourceGuid'           => $sourceGuid?->getGuid(),
            'spellId'              => $spellId,
            'linkedNpcCastSpellId' => $sourceGuid === null ? $this->findLinkedNpcCastSpellId($spellName, $timestamp) : null,
        ]);
        $this->pruneActiveDebuffs($timestamp);
    }

    private function handleDebuffRemoved(string $playerGuid, int $spellId, Carbon $timestamp): void
    {
        $activeDebuffKey = $this->activeDebuffKey($playerGuid, $spellId);

        /** @var array{appliedAt: Carbon, sourceGuid: string|null, spellId: int, linkedNpcCastSpellId: int|null}|null $activeDebuff */
        $activeDebuff = $this->activeDebuffs->get($activeDebuffKey);
        if ($activeDebuff === null) {
            return;
        }

        $this->activeDebuffs->forget($activeDebuffKey);

        // Signature A attributes the fact to the NPC's cast, signature B to the debuff itself
        $attributedSpellId = $activeDebuff['linkedNpcCastSpellId'] ?? $spellId;
        $signature         = $activeDebuff['linkedNpcCastSpellId'] === null ? 'B' : 'A';
        $observedLifetime  = $this->millisecondsBetween($activeDebuff['appliedAt'], $timestamp);

        // The duration is that of the debuff that was actually removed, not of the cast it is attributed to
        $duration = $this->getSpellDurationMs($spellId);
        if ($duration !== null && $duration > 0 && $observedLifetime >= $duration - self::EPSILON_MS) {
            $this->log->extractDataDebuffExpiredNaturally($spellId, $observedLifetime, $duration);

            return;
        }

        foreach ($this->recentCounterCasts->reverse() as $counterCast) {
            if ($counterCast['playerGuid'] !== $playerGuid) {
                continue;
            }

            if (abs($this->millisecondsBetween($counterCast['ts'], $timestamp)) > self::EPSILON_MS) {
                continue;
            }

            $this->queueCounterObservation($signature, $attributedSpellId, $counterCast['definition'], $playerGuid);

            return;
        }

        // No counter cast yet - it may still arrive within EPSILON_MS
        $this->recentDebuffRemovals->push([
            'ts'                => $timestamp,
            'playerGuid'        => $playerGuid,
            'attributedSpellId' => $attributedSpellId,
            'appliedAt'         => $activeDebuff['appliedAt'],
            'signature'         => $signature,
        ]);
        $this->pruneByAge($this->recentDebuffRemovals, $timestamp, self::EPSILON_MS);
    }

    private function queueCounterObservation(
        string                          $signature,
        int                             $spellId,
        SpellCounterDefinitionInterface $definition,
        string                          $playerGuid,
    ): void {
        $property = $definition->getProperty();
        $dedupKey = sprintf('%d-%s', $spellId, $property->value);

        if ($this->pendingCounterObservations->has($dedupKey)) {
            $this->log->extractDataCounterAlreadyQueued($spellId, $property->value);

            return;
        }

        $this->pendingCounterObservations->put($dedupKey, [
            'spell_id' => $spellId,
            'property' => $property,
        ]);

        $this->log->extractDataDetectedSpellCounter($signature, $spellId, $property->value, $playerGuid);
    }

    /**
     * A targeting debuff is logged with a nil source guid - it is linked back to the NPC cast that applied it by
     * temporal adjacency, preferring an exact spell name match.
     */
    private function findLinkedNpcCastSpellId(string $spellName, Carbon $timestamp): ?int
    {
        $fallbackSpellId = null;

        foreach ($this->recentNpcCastStarts->reverse() as $npcCastStart) {
            if (abs($this->millisecondsBetween($npcCastStart['ts'], $timestamp)) > self::CAST_START_LINK_WINDOW_MS) {
                continue;
            }

            if ($npcCastStart['spellName'] === $spellName) {
                return $npcCastStart['spellId'];
            }

            $fallbackSpellId ??= $npcCastStart['spellId'];
        }

        return $fallbackSpellId;
    }

    /**
     * @return int|null The spell's duration in milliseconds, or null when the spell is unknown or has no duration
     */
    /**
     * Whether this debuff carries a loss-of-control mechanic that would explain an NPC abandoning a cast. Unknown
     * spells (no row yet) and mechanic-less debuffs are NOT disturbing - being permissive here is what lets signature
     * C fire at all mid-combat; the observation staleness window self-corrects rare misattributions.
     */
    private function isCastDisturbingDebuff(int $spellId): bool
    {
        if (!$this->spellMechanicCache->has($spellId)) {
            $mechanic = SpellModel::query()->where('id', $spellId)->value('mechanic');

            $this->spellMechanicCache->put($spellId, $mechanic ?? false);
        }

        $mechanic = $this->spellMechanicCache->get($spellId);
        if (!is_string($mechanic)) {
            return false;
        }

        foreach (self::CAST_DISTURBING_MECHANICS as $disturbingMechanic) {
            if ($mechanic === sprintf('spellmechanic.%s', $disturbingMechanic)) {
                return true;
            }
        }

        return false;
    }

    private function getSpellDurationMs(int $spellId): ?int
    {
        if (!$this->spellDurationCache->has($spellId)) {
            $duration = SpellModel::query()->where('id', $spellId)->value('duration');

            $this->spellDurationCache->put($spellId, $duration === null ? false : (int)$duration);
        }

        $duration = $this->spellDurationCache->get($spellId);

        return $duration === false ? null : $duration;
    }

    /**
     * @return string|null The guid string when this is an actual creature - not a pet, vehicle or game object
     */
    private function npcCreatureGuid(?Guid $guid): ?string
    {
        return $guid instanceof Creature && $guid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE
            ? $guid->getGuid()
            : null;
    }

    private function activeDebuffKey(string $destPlayerGuid, int $spellId): string
    {
        return sprintf('%s-%d', $destPlayerGuid, $spellId);
    }

    private function millisecondsBetween(Carbon $from, Carbon $to): int
    {
        return $to->getTimestampMs() - $from->getTimestampMs();
    }

    /**
     * Drops entries older than $maxAgeMs from the head of a chronologically appended list.
     *
     * @template TItem of array<string, mixed>
     *
     * @param Collection<int, TItem> $collection
     */
    private function pruneByAge(Collection $collection, Carbon $timestamp, int $maxAgeMs): void
    {
        while ($collection->isNotEmpty()) {
            /** @var array{ts: Carbon} $first */
            $first = $collection->first();
            if ($this->millisecondsBetween($first['ts'], $timestamp) <= $maxAgeMs) {
                break;
            }

            $collection->shift();
        }
    }

    private function pruneActiveDebuffs(Carbon $timestamp): void
    {
        $this->activeDebuffs->forget(
            $this->activeDebuffs
                ->filter(fn(array $activeDebuff) => $this->millisecondsBetween($activeDebuff['appliedAt'], $timestamp) > self::ACTIVE_DEBUFF_MAX_AGE_MS)
                ->keys()
                ->all(),
        );
    }

    private function prunePendingNpcCasts(Carbon $timestamp): void
    {
        $this->pendingNpcCasts = $this->pendingNpcCasts->filter(
            fn(array $pendingCast) => $this->millisecondsBetween($pendingCast['startedAt'], $timestamp) <= self::PENDING_CAST_MAX_AGE_MS,
        );
    }

    /**
     * Resets everything that correlates events across lines. Queued observations survive - they are only flushed in
     * afterExtract, and a run boundary must not throw away detections made before it.
     */
    private function resetCorrelationState(): void
    {
        $this->activeDebuffs        = collect();
        $this->recentNpcCastStarts  = collect();
        $this->pendingNpcCasts      = collect();
        $this->recentCounterCasts   = collect();
        $this->recentDebuffRemovals = collect();
    }
}
