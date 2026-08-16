<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Prefixes\Range;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\AuraRemovedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\CastSuccess;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\DamageInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\DamageLandedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\DamageLandedSupportInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\DamageSupportInterface;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Guid;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell as SpellModel;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\ImmunityBypasses\ImmunityDefinitionInterface;
use App\Service\CombatLog\DataExtractors\ImmunityBypasses\ImmunityDefinitions;
use App\Service\CombatLog\DataExtractors\Logging\ImmunityBypassDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Detects NPC abilities that land on a player *despite* an active immunity - the rare, genuinely useful inverse of
 * "this cooldown stops everything". Full immunities stopping a mechanic is assumed knowledge; a mechanic that goes
 * through one is what a route author needs to be warned about.
 *
 * A window is opened by the SPELL_AURA_APPLIED of a known immunity buff on a player and closed by its
 * SPELL_AURA_REMOVED. Any NPC-sourced harmful event landing on that same player inside the window - and covered by
 * what the immunity actually protects against, see {@see ImmunityDefinitionInterface} - is a bypass.
 *
 * A hit is only counted when it is unambiguously *inside* the window: detections are held as candidates and both ends
 * of the window are shrunk by EPSILON_MS, because the log orders same-tick lines arbitrarily and an aura application
 * or expiry sharing a tick with a hit cannot be told apart from the hit landing just outside the window.
 *
 * SPELL_MISSED is the immunity doing its job and is ignored by construction: only events that actually landed
 * (SPELL_DAMAGE, a harmful SPELL_AURA_APPLIED) are ever considered.
 */
class ImmunityBypassDataExtractor implements DataExtractorInterface
{
    /**
     * Slack at both ends of an immunity window. The same value the counter detector settled on: correlated lines land
     * in the same server tick (0-1 ms apart), so 100 ms discards the genuinely ambiguous boundary cases while leaving
     * the interior of even the shortest immunity (Anti-Magic Shell, 5 s) almost entirely usable.
     */
    public const int EPSILON_MS = 100;

    /**
     * How long a remembered NPC cast stays usable for provenance. Nothing that resolved longer ago than this is
     * still in flight, and the map must not grow without bound.
     */
    public const int CAST_PROVENANCE_MAX_AGE_MS = 15000;

    /** @var int How many resolutions of the same ability by the same caster are kept - enough to cover overlap. */
    private const int CAST_PROVENANCE_MAX_PER_ABILITY = 8;

    /** @var string A hit that dealt damage. */
    private const string KIND_DAMAGE = 'damage';

    /** @var string A harmful aura that was applied. */
    private const string KIND_AURA = 'aura';

    /** @var Collection<int, ImmunityDefinitionInterface> Keyed by immunity buff spell id. */
    private readonly Collection $definitionsByBuffSpellId;

    /** @var Collection<string, ImmunityDefinitionInterface> Keyed by SpellProperty value. */
    private readonly Collection $definitionsByProperty;

    /**
     * Immunity windows currently open, keyed by the player guid they belong to. A player can hold several at once
     * (Blessing of Protection layered under Divine Shield), and a hit is judged against each of them separately.
     *
     * @var Collection<string, array<int, array{definition: ImmunityDefinitionInterface, buffSpellId: int, appliedAt: Carbon, candidates: array<int, array{spellId: int, npcId: int, dungeonId: int|null, ts: Carbon, kind: string}>}>>
     */
    private Collection $activeImmunityWindows;

    /**
     * When each NPC ability recently *resolved*, keyed by caster guid + spell id, as epoch milliseconds. A hit whose
     * ability resolved before the immunity went up was already in flight and is not a bypass - see the check in
     * recordBypassCandidates. Several timestamps are kept per key because a re-cast must not hide the earlier,
     * still-in-flight one.
     *
     * Ordered oldest-first: PHP preserves insertion order and every remember re-inserts its key at the back, so the
     * front is always the least recently touched entry and expiry is a walk from there rather than a rebuild of the
     * whole map - see expireProvenance. Kept as a plain array rather than a Collection because this is the hottest
     * structure in the extractor: it is written on every NPC SPELL_CAST_SUCCESS.
     *
     * @var array<string, array<int, int>>
     */
    private array $recentNpcCastSuccesses;

    /**
     * All (spell_id, property) bypasses detected this session - batch-upserted in afterExtract.
     *
     * @var Collection<string, array{spell_id: int, property: SpellProperty, npc_id: int|null, dungeon_id: int|null}>
     */
    private Collection $pendingBypassObservations;

    private readonly ImmunityBypassDataExtractorLoggingInterface $log;

    private ?string $currentCombatLogFilePath = null;

    /** @var int|null The dungeon the extraction currently takes place in - used for SpellDungeon assignment. */
    private ?int $currentDungeonId = null;

    public function __construct()
    {
        $definitionsByBuffSpellId = collect();
        $definitionsByProperty    = collect();

        foreach (ImmunityDefinitions::all() as $definition) {
            foreach ($definition->getBuffSpellIds() as $buffSpellId) {
                $definitionsByBuffSpellId->put($buffSpellId, $definition);
            }

            $definitionsByProperty->put($definition->getProperty()->value, $definition);
        }

        $this->definitionsByBuffSpellId = $definitionsByBuffSpellId;
        $this->definitionsByProperty    = $definitionsByProperty;

        $this->pendingBypassObservations = collect();
        $this->activeImmunityWindows     = collect();
        $this->recentNpcCastSuccesses    = [];

        $log = App::make(ImmunityBypassDataExtractorLoggingInterface::class);
        /** @var ImmunityBypassDataExtractorLoggingInterface $log */

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
        $this->currentDungeonId = $currentDungeon->dungeon->id;

        // A new run invalidates every open window - but not the candidates gathered inside them, which are as good as
        // a window that expired on its own
        if ($parsedEvent instanceof ChallengeModeStart ||
            $parsedEvent instanceof ChallengeModeEnd ||
            $parsedEvent instanceof ZoneChange) {
            $this->closeAllImmunityWindows();

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

        // Remembered unconditionally: provenance is needed for casts that resolved *before* a window even opened
        if ($suffix instanceof CastSuccess) {
            if ($this->isNpcCreature($sourceGuid)) {
                $this->rememberNpcCastSuccess($sourceGuid->getGuid(), $prefix->getSpellId(), $timestamp->getTimestampMs());
            }

            return;
        }

        if (!($destGuid instanceof Player)) {
            return;
        }

        if ($suffix instanceof AuraAppliedInterface && $suffix->getAuraType() === AuraBase::AURA_TYPE_BUFF) {
            $this->openImmunityWindow($destGuid->getGuid(), $prefix->getSpellId(), $timestamp);

            return;
        }

        if ($suffix instanceof AuraRemovedInterface && $suffix->getAuraType() === AuraBase::AURA_TYPE_BUFF) {
            $this->closeImmunityWindow($destGuid->getGuid(), $prefix->getSpellId(), $timestamp);

            return;
        }

        // Everything below only matters while an immunity is up, which is almost never - keep the hot path cheap
        if ($this->activeImmunityWindows->isEmpty()) {
            return;
        }

        // Only actual creatures - not pets, vehicles or game objects, and never another player
        if (!$this->isNpcCreature($sourceGuid)) {
            return;
        }

        if ($suffix instanceof DamageInterface && $this->isFirsthandDamage($suffix)) {
            // A pre-existing damage over time effect ticking through an immunity is the documented partial case, not a
            // property of the ability - a DoT applied *during* the window is caught by the harmful aura branch instead.
            // Periodic events are recognized by name: Prefix's mapping matches SPELL before SPELL_PERIODIC, so a
            // periodic event never actually carries the SpellPeriodic prefix class
            if ($this->isPeriodic($parsedEvent->getEventName()) || $suffix->getAmount() <= 0) {
                return;
            }

            $this->recordBypassCandidates(
                $destGuid->getGuid(),
                $prefix->getSpellId(),
                $sourceGuid,
                $suffix->getSchool(),
                self::KIND_DAMAGE,
                $timestamp,
            );
        } elseif ($suffix instanceof AuraAppliedInterface && $suffix->getAuraType() === AuraBase::AURA_TYPE_DEBUFF) {
            $this->recordBypassCandidates(
                $destGuid->getGuid(),
                $prefix->getSpellId(),
                $sourceGuid,
                $this->parseSchoolMask($prefix->getSpellSchool()),
                self::KIND_AURA,
                $timestamp,
            );
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->closeAllImmunityWindows();

        if ($this->pendingBypassObservations->isNotEmpty()) {
            $now  = Carbon::now()->toDateTimeString();
            $rows = $this->pendingBypassObservations->map(fn(array $observation) => [
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
                ->whereIn('id', $this->pendingBypassObservations->pluck('spell_id')->unique()->all())
                ->get()
                ->keyBy('id');

            foreach ($this->pendingBypassObservations as $observation) {
                $this->applyBypassToSpell($result, $spells, $observation['spell_id'], $observation['property']);
                $this->assignSpellToNpc($result, $spells, $observation);
            }
        }

        $this->pendingBypassObservations = collect();
        $this->recentNpcCastSuccesses    = [];
        $this->currentCombatLogFilePath  = null;
        $this->currentDungeonId          = null;
    }

    /**
     * Sets the immunity bypass bit on the spell that landed, and writes the audit event for it.
     *
     * @param Collection<int, SpellModel> $spells
     */
    private function applyBypassToSpell(
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

        /** @var ImmunityDefinitionInterface|null $definition */
        $definition = $this->definitionsByProperty->get($property->value);
        if ($definition === null) {
            return;
        }

        $immunityBit = $definition->getImmunityBit();
        if (($spell->bypasses_immunities_mask & $immunityBit) !== 0) {
            $this->log->afterExtractBypassAlreadyKnown($spellId, $property->value);

            return;
        }

        $spell->bypasses_immunities_mask |= $immunityBit;

        if ($spell->save()) {
            CombatLogSpellEvent::create([
                'spell_id'        => $spellId,
                'event_type'      => CombatLogSpellEventType::PropertyChanged,
                'property'        => $property,
                'combat_log_path' => $this->currentCombatLogFilePath,
            ]);

            $result->addedSpellImmunityBypass();
        }
    }

    /**
     * A detected bypass is proof the NPC casts this spell. Mirrors SpellCounterDataExtractor's assignment: cast-only
     * and nil-destination spells never pass SpellDataExtractor's assignment gate, so without this the bypassing spell
     * would be invisible on the NPC's compendium page.
     *
     * @param Collection<int, SpellModel>                                                           $spells
     * @param array{spell_id: int, property: SpellProperty, npc_id: int|null, dungeon_id: int|null} $observation
     */
    private function assignSpellToNpc(ExtractedDataResult $result, Collection $spells, array $observation): void
    {
        $npcId = $observation['npc_id'];

        /** @var SpellModel|null $spell */
        $spell = $spells->get($observation['spell_id']);

        // Same gate as NpcSpellAssignmentCollector: a categorized player spell must never be assigned to an NPC, but a
        // null category is allowed - spells first created from a combat log carry no category until Wowhead is fetched
        if ($npcId === null ||
            $spell === null ||
            ($spell->category !== null && $spell->category !== sprintf('spellcategory.%s', SpellModel::CATEGORY_UNKNOWN))) {
            return;
        }

        /** @var Npc|null $npc */
        $npc = Npc::with('npcSpells')->find($npcId);
        if ($npc === null) {
            return;
        }

        $spellId = $observation['spell_id'];
        if ($npc->npcSpells->filter(fn(NpcSpell $npcSpell) => $npcSpell->spell_id === $spellId)->isNotEmpty()) {
            return;
        }

        NpcSpell::create([
            'npc_id'   => $npcId,
            'spell_id' => $spellId,
        ]);

        if ($observation['dungeon_id'] !== null && !SpellDungeon::where('spell_id', $spellId)
            ->where('dungeon_id', $observation['dungeon_id'])->exists()) {
            SpellDungeon::create([
                'spell_id'   => $spellId,
                'dungeon_id' => $observation['dungeon_id'],
            ]);
        }

        CombatLogNpcEvent::create([
            'npc_id'          => $npcId,
            'event_type'      => CombatLogNpcEventType::SpellAssigned,
            'model_class'     => SpellModel::class,
            'model_id'        => $spellId,
            'combat_log_path' => $this->currentCombatLogFilePath,
        ]);

        $result->createdNpcSpell();
        $this->log->afterExtractAssignedBypassingSpellToNpc($npcId, $spellId);
    }

    private function openImmunityWindow(string $playerGuid, int $buffSpellId, Carbon $timestamp): void
    {
        /** @var ImmunityDefinitionInterface|null $definition */
        $definition = $this->definitionsByBuffSpellId->get($buffSpellId);
        if ($definition === null) {
            return;
        }

        $windows = $this->activeImmunityWindows->get($playerGuid, []);

        // A re-application replaces the previous window of the same immunity - its candidates are flushed as if it had
        // expired, since the removal that must have happened in between was never logged
        foreach ($windows as $index => $window) {
            if ($window['buffSpellId'] === $buffSpellId) {
                $this->flushWindowCandidates($playerGuid, $window, null);
                unset($windows[$index]);
            }
        }

        $windows[] = [
            'definition'  => $definition,
            'buffSpellId' => $buffSpellId,
            'appliedAt'   => $timestamp,
            'candidates'  => [],
        ];

        $this->activeImmunityWindows->put($playerGuid, array_values($windows));

        $this->log->extractDataOpenedImmunityWindow($playerGuid, $buffSpellId, $definition->getProperty()->value);
    }

    private function closeImmunityWindow(string $playerGuid, int $buffSpellId, Carbon $timestamp): void
    {
        if (!$this->definitionsByBuffSpellId->has($buffSpellId)) {
            return;
        }

        $windows = $this->activeImmunityWindows->get($playerGuid, []);

        foreach ($windows as $index => $window) {
            if ($window['buffSpellId'] !== $buffSpellId) {
                continue;
            }

            $this->flushWindowCandidates($playerGuid, $window, $timestamp);
            unset($windows[$index]);
        }

        if (empty($windows)) {
            $this->activeImmunityWindows->forget($playerGuid);
        } else {
            $this->activeImmunityWindows->put($playerGuid, array_values($windows));
        }
    }

    /**
     * Closes every window still open, flushing their candidates as if the window had run its full course. Used at a
     * run boundary and at the end of the combat log, where no removal will ever arrive.
     */
    private function closeAllImmunityWindows(): void
    {
        foreach ($this->activeImmunityWindows as $playerGuid => $windows) {
            foreach ($windows as $window) {
                $this->flushWindowCandidates($playerGuid, $window, null);
            }
        }

        $this->activeImmunityWindows = collect();
    }

    /**
     * Records this landed event against every open window of this player it is a genuine bypass of.
     */
    private function recordBypassCandidates(
        string   $playerGuid,
        int      $spellId,
        Creature $sourceGuid,
        int      $schoolMask,
        string   $kind,
        Carbon   $timestamp,
    ): void {
        $windows = $this->activeImmunityWindows->get($playerGuid);
        if ($windows === null) {
            return;
        }

        $resolvedAtsMs = $this->recentNpcCastSuccessesFor($sourceGuid->getGuid(), $spellId, $timestamp);

        foreach ($windows as $index => $window) {
            $definition  = $window['definition'];
            $windowAgeMs = $this->millisecondsBetween($window['appliedAt'], $timestamp);

            // maxDurationMs is a hard ceiling, not only a fallback for a missing removal line: if a talent extends the
            // real duration past it and the SPELL_AURA_REMOVED simply hasn't been processed yet (or never arrives),
            // this force-closes the window here regardless. That trade is deliberate - it can only cause a genuine
            // late-window bypass to be missed, never a false "bypasses immunity" claim, which matches the conservative
            // design of this whole extractor. Where the removal was never logged, this is also where that expiry
            // fell, known exactly, so the trailing epsilon applies just as it does to a logged removal
            if ($windowAgeMs > $definition->getMaxDurationMs()) {
                $this->flushWindowCandidates(
                    $playerGuid,
                    $window,
                    $window['appliedAt']->copy()->addMilliseconds($definition->getMaxDurationMs()),
                );
                unset($windows[$index]);

                continue;
            }

            if ($windowAgeMs < self::EPSILON_MS) {
                continue;
            }

            // The ability resolved before the immunity went up - what landed was already in flight, which the
            // immunity was never going to stop. *Any* still-recent cast before the window disqualifies the hit, not
            // just the newest one: a re-cast inside the window cannot be told apart from the earlier projectile
            // finally landing. Abilities with no cast line at all (persistent ground effects, beams) have no
            // provenance and are judged on the window alone
            if ($this->resolvedBeforeWindow($resolvedAtsMs, $window['appliedAt'])) {
                $this->log->extractDataCandidateResolvedBeforeWindow($spellId, $definition->getProperty()->value, $windowAgeMs);

                continue;
            }

            if ($kind === self::KIND_DAMAGE ? !$definition->blocksDamage() : !$definition->blocksHarmfulAuras()) {
                continue;
            }

            // Damage of a school the immunity never covered is expected behaviour, not a bypass
            if (($schoolMask & $definition->getProtectedSchoolsMask()) === 0) {
                continue;
            }

            $window['candidates'][] = [
                'spellId' => $spellId,
                'npcId'   => $sourceGuid->getId(),
                // Captured here, not at flush time: a ZoneChange flushes open windows *after* it has already moved
                // currentDungeonId on to the new dungeon
                'dungeonId' => $this->currentDungeonId,
                'ts'        => $timestamp,
                'kind'      => $kind,
            ];
            $windows[$index] = $window;
        }

        if (empty($windows)) {
            $this->activeImmunityWindows->forget($playerGuid);
        } else {
            $this->activeImmunityWindows->put($playerGuid, array_values($windows));
        }
    }

    /**
     * Confirms a closed window's candidates. Anything within EPSILON_MS of the removal is dropped: the log orders
     * same-tick lines arbitrarily, so a hit that close to the end could equally have landed after the immunity fell.
     *
     * @param array{definition: ImmunityDefinitionInterface, buffSpellId: int, appliedAt: Carbon, candidates: array<int, array{spellId: int, npcId: int, dungeonId: int|null, ts: Carbon, kind: string}>} $window
     */
    private function flushWindowCandidates(string $playerGuid, array $window, ?Carbon $removedAt): void
    {
        $definition = $window['definition'];
        $property   = $definition->getProperty();

        foreach ($window['candidates'] as $candidate) {
            if ($removedAt !== null) {
                $offsetToRemovalMs = $this->millisecondsBetween($candidate['ts'], $removedAt);
                if ($offsetToRemovalMs < self::EPSILON_MS) {
                    $this->log->extractDataCandidateRejectedAtWindowEnd($candidate['spellId'], $property->value, $offsetToRemovalMs);

                    continue;
                }
            }

            $this->queueBypassObservation(
                $candidate['kind'],
                $candidate['spellId'],
                $definition,
                $playerGuid,
                $candidate['npcId'],
                $candidate['dungeonId'],
                $this->millisecondsBetween($window['appliedAt'], $candidate['ts']),
            );
        }
    }

    private function queueBypassObservation(
        string                      $kind,
        int                         $spellId,
        ImmunityDefinitionInterface $definition,
        string                      $playerGuid,
        int                         $npcId,
        ?int                        $dungeonId,
        int                         $windowOffsetMs,
    ): void {
        $property = $definition->getProperty();
        $dedupKey = sprintf('%d-%s', $spellId, $property->value);

        if ($this->pendingBypassObservations->has($dedupKey)) {
            $this->log->extractDataBypassAlreadyQueued($spellId, $property->value);

            return;
        }

        $this->pendingBypassObservations->put($dedupKey, [
            'spell_id'   => $spellId,
            'property'   => $property,
            'npc_id'     => $npcId,
            'dungeon_id' => $dungeonId,
        ]);

        $this->log->extractDataDetectedImmunityBypass($kind, $spellId, $property->value, $playerGuid, $windowOffsetMs);
    }

    private function isPeriodic(string $eventName): bool
    {
        return str_starts_with($eventName, Prefix::PREFIX_SPELL_PERIODIC);
    }

    private function rememberNpcCastSuccess(string $casterGuid, int $spellId, int $timestampMs): void
    {
        $key = $this->npcCastKey($casterGuid, $spellId);

        $castAts = $this->recentNpcCastSuccesses[$key] ?? [];

        // Removed before being written back so the key moves to the back of the map: updating it in place would keep
        // it at its original position and break the oldest-first ordering expireProvenance relies on
        unset($this->recentNpcCastSuccesses[$key]);

        $castAts[] = $timestampMs;

        $this->recentNpcCastSuccesses[$key] = count($castAts) > self::CAST_PROVENANCE_MAX_PER_ABILITY
            ? array_slice($castAts, -self::CAST_PROVENANCE_MAX_PER_ABILITY)
            : $castAts;

        $this->expireProvenance($timestampMs);
    }

    /**
     * Drops every key whose most recent resolution is too old to explain anything that could still land, walking the
     * oldest-first map from the front and stopping at the first key that is still live.
     *
     * Amortised O(1) per event - a key is visited exactly once, on the event that evicts it - where pruning by map
     * size instead rebuilt all of it on every event once the live set sat near the threshold. Keys are dropped whole
     * rather than filtered per timestamp, so a surviving key can still hold individually stale timestamps; stripping
     * those is the read path's job, see withinProvenanceAge.
     */
    private function expireProvenance(int $timestampMs): void
    {
        $cutoffMs = $timestampMs - self::CAST_PROVENANCE_MAX_AGE_MS;

        while ($this->recentNpcCastSuccesses !== []) {
            $oldestKey = array_key_first($this->recentNpcCastSuccesses);
            $castAts   = $this->recentNpcCastSuccesses[$oldestKey];

            // The last entry is the newest: timestamps are appended in log order, and array_slice keeps the tail
            if ($castAts[array_key_last($castAts)] >= $cutoffMs) {
                break;
            }

            unset($this->recentNpcCastSuccesses[$oldestKey]);
        }
    }

    /**
     * @return array<int, int> Every remembered resolution of this ability still recent enough to explain this event
     */
    private function recentNpcCastSuccessesFor(string $casterGuid, int $spellId, Carbon $timestamp): array
    {
        $castAts = $this->recentNpcCastSuccesses[$this->npcCastKey($casterGuid, $spellId)] ?? [];

        return $castAts === [] ? [] : $this->withinProvenanceAge($castAts, $timestamp->getTimestampMs());
    }

    /**
     * @param  array<int, int> $castAts
     * @return array<int, int>
     */
    private function withinProvenanceAge(array $castAts, int $timestampMs): array
    {
        return array_values(array_filter($castAts, static function (int $castAtMs) use ($timestampMs): bool {
            $ageMs = $timestampMs - $castAtMs;

            // A negative age is a cast from the future - only reachable when combat logs are replayed out of order,
            // and never an explanation for this event
            return $ageMs >= 0 && $ageMs <= self::CAST_PROVENANCE_MAX_AGE_MS;
        }));
    }

    /**
     * @param array<int, int> $resolvedAtsMs
     */
    private function resolvedBeforeWindow(array $resolvedAtsMs, Carbon $appliedAt): bool
    {
        $appliedAtMs = $appliedAt->getTimestampMs();

        foreach ($resolvedAtsMs as $resolvedAtMs) {
            if ($resolvedAtMs - $appliedAtMs < -self::EPSILON_MS) {
                return true;
            }
        }

        return false;
    }

    private function npcCastKey(string $casterGuid, int $spellId): string
    {
        return sprintf('%s-%d', $casterGuid, $spellId);
    }

    /**
     * Whether this guid is an actual creature - not a pet, vehicle, game object or player.
     *
     * @phpstan-assert-if-true Creature $guid
     */
    private function isNpcCreature(?Guid $guid): bool
    {
        return $guid instanceof Creature && $guid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE;
    }

    /**
     * Whether this is the damage event itself rather than one of its echoes: the _LANDED and _SUPPORT variants
     * describe the same hit a second time (for support attribution) and must not be counted again.
     */
    private function isFirsthandDamage(DamageInterface $suffix): bool
    {
        return !($suffix instanceof DamageLandedInterface) &&
            !($suffix instanceof DamageSupportInterface) &&
            !($suffix instanceof DamageLandedSupportInterface);
    }

    /**
     * The prefix school is logged as a hex string ("0x20"), unlike the damage suffix school which is already a mask.
     */
    private function parseSchoolMask(string $spellSchool): int
    {
        return str_starts_with(strtolower($spellSchool), '0x')
            ? (int)hexdec(substr($spellSchool, 2))
            : (int)$spellSchool;
    }

    private function millisecondsBetween(Carbon $from, Carbon $to): int
    {
        return $to->getTimestampMs() - $from->getTimestampMs();
    }
}
