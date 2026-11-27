<?php

namespace App\Service\Spell\Cooldown;

use App\Models\CharacterClassSpecialization;
use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellCategory;
use App\Models\Spell\SpellCooldownSource;
use App\Repositories\Interfaces\Spell\SpellSpecializationRepositoryInterface;
use App\Service\Spell\Cooldown\Dtos\SpellCooldownImportResult;
use App\Service\Spell\Cooldown\Logging\SpellCooldownImportServiceLoggingInterface;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use App\Service\WagoTools\WagoToolsServiceInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use Generator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reads the base cooldown, charges and specializations of our spells from the game client's DB2 tables.
 *
 * Cooldowns: `SpellCooldowns` (RecoveryTime, CategoryRecoveryTime) and, for a spell with charges,
 * `SpellCategories.ChargeCategory` → `SpellCategory` (MaxCharges, ChargeRecoveryTime). Talents that change these are
 * not applied: the stored value is the base one.
 *
 * Specializations, most specific first - a spell that one of them places stops there:
 * 1. `SpecializationSpells`, a specialization's own spells;
 * 2. the talent trees: `TraitDefinition` → `TraitNodeEntry` → `TraitNodeXTraitNodeEntry` → `TraitNode`, whose tree
 *    belongs to one class (`TraitTreeLoadout` → `ChrSpecialization`), narrowed to the specializations its
 *    `TraitCond` spec sets (`SpecSetMember`) make the node available or visible to;
 * 3. the class's own skill line (`SkillLineAbility`, via `SkillLineXTraitTree`), i.e. every specialization of it.
 *
 * A spell none of them places - an aura, a proc, an older id of a reworked ability - inherits the specializations of
 * the nearest spell the client links it to, up to MAX_LINK_DEPTH links away: a talent or specialization spell that
 * overrides or shows it (`TraitDefinition.OverridesSpellID`/`VisibleSpellID`, `SpecializationSpells.OverridesSpellID`),
 * the spell replacing it (`SpellReplacement`), a spell that teaches it (`SpellLearnSpell`), or one with an effect that
 * triggers it (`SpellEffect.EffectTriggerSpell`). When the nearest such spells belong to more than one class, it
 * inherits nothing.
 */
class SpellCooldownImportService implements SpellCooldownImportServiceInterface
{
    /** `TraitCond.CondType` values that restrict who may take a node, as opposed to granting or erroring on it. */
    private const array RESTRICTING_TRAIT_COND_TYPES = [
        0, // Available
        1, // Visible
    ];

    /** How many links a spell may be from the spell it inherits its specializations from. */
    private const int MAX_LINK_DEPTH = 3;

    public function __construct(
        private readonly WagoToolsServiceInterface                  $wagoToolsService,
        private readonly WowheadServiceInterface                    $wowheadService,
        private readonly SpellSpecializationRepositoryInterface     $spellSpecializationRepository,
        private readonly SpellCooldownImportServiceLoggingInterface $log,
    ) {
    }

    public function importCooldowns(
        string  $product,
        int     $gameVersionId,
        ?string $build = null,
        bool    $fillGapsFromWowhead = true,
    ): ?SpellCooldownImportResult {
        $build ??= $this->wagoToolsService->getLatestBuild($product);

        if ($build === null) {
            $this->log->importCooldownsUnknownBuild($product);

            return null;
        }

        try {
            $this->log->importCooldownsStart($product, $build, $gameVersionId);

            /** @var Collection<int, Spell> $spells */
            $spells = Spell::query()
                ->select(['id', 'category', 'cooldown_ms', 'charges', 'charge_cooldown_ms', 'cooldown_source'])
                ->where('game_version_id', $gameVersionId)
                ->get();
            $ourIds = array_fill_keys($spells->pluck('id')->all(), true);

            $presentIds = $this->readPresentSpellIds($build, $ourIds);

            // A build that knows none of our spells is a download that went wrong, not a patch that removed them all
            if ($presentIds === [] && $spells->isNotEmpty()) {
                $this->log->importCooldownsNoSpellsFound($product, $build);

                return null;
            }

            $cooldownUpdatedCount = $this->persistCooldowns($build, $spells, $presentIds);

            $specializationUpdatedCount = 0;
            $specializationsImported    = true;

            try {
                $specializationUpdatedCount = $this->persistSpecializations($build, $presentIds);
            } catch (WagoToolsDownloadException $exception) {
                // A client without talent trees (the classic products) says nothing about specializations, so
                // nothing about ours may change on the strength of it
                $this->log->importSpecializationsTableUnavailable($build, $exception->getMessage());
                $specializationsImported = false;
            }

            $wowheadFilledCount = $fillGapsFromWowhead && $gameVersionId === GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]
                ? $this->fillGapsFromWowhead($spells, $presentIds)
                : 0;

            return new SpellCooldownImportResult(
                build: $build,
                spellCount: $spells->count(),
                presentCount: count($presentIds),
                cooldownUpdatedCount: $cooldownUpdatedCount,
                specializationUpdatedCount: $specializationUpdatedCount,
                specializationsImported: $specializationsImported,
                wowheadFilledCount: $wowheadFilledCount,
            );
        } finally {
            $this->log->importCooldownsEnd();
        }
    }

    /**
     * Which of our spells the build has a row for at all - a spell missing from it has no opinion either way, and
     * keeps what we have.
     *
     * @param  array<int, bool> $ourIds
     * @return array<int, bool>
     */
    private function readPresentSpellIds(string $build, array $ourIds): array
    {
        $presentIds = [];

        foreach ($this->readTable('SpellName', $build, ['ID'], mustHaveRows: true) as $row) {
            $spellId = (int)($row['ID'] ?? 0);

            if (isset($ourIds[$spellId])) {
                $presentIds[$spellId] = true;
            }
        }

        return $presentIds;
    }

    /**
     * @param Collection<int, Spell> $spells
     * @param array<int, bool>       $presentIds
     */
    private function persistCooldowns(string $build, Collection $spells, array $presentIds): int
    {
        $cooldowns        = $this->readCooldowns($build, $presentIds);
        $chargeCategories = $this->readChargeCategories($build, $presentIds);
        $charges          = $this->readCharges($build);

        $updatedCount = 0;

        foreach ($spells as $spell) {
            if (!isset($presentIds[$spell->id])) {
                continue;
            }

            $charge = $charges[$chargeCategories[$spell->id] ?? 0] ?? null;

            // No SpellCooldowns row is the client saying the spell has no cooldown of its own
            $cooldownMs       = $cooldowns[$spell->id] ?? 0;
            $chargeCount      = $charge['maxCharges'] ?? 1;
            $chargeCooldownMs = $charge['chargeRecoveryTime'] ?? null;

            if ($cooldownMs === $spell->cooldown_ms
                && $chargeCount === $spell->charges
                && $chargeCooldownMs === $spell->charge_cooldown_ms
                && $spell->cooldown_source === SpellCooldownSource::Db2) {
                continue;
            }

            Spell::query()
                ->whereKey($spell->id)
                ->update([
                    'cooldown_ms'        => $cooldownMs,
                    'charges'            => $chargeCount,
                    'charge_cooldown_ms' => $chargeCooldownMs,
                    'cooldown_source'    => SpellCooldownSource::Db2->value,
                ]);

            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * @param  array<int, bool> $wantedIds
     * @return array<int, int>  spell id => cooldown in ms
     */
    private function readCooldowns(string $build, array $wantedIds): array
    {
        $cooldowns    = [];
        $difficulties = [];

        foreach ($this->readTable('SpellCooldowns', $build, ['SpellID', 'DifficultyID', 'RecoveryTime', 'CategoryRecoveryTime'], mustHaveRows: true) as $row) {
            $spellId = (int)($row['SpellID'] ?? 0);

            if (!isset($wantedIds[$spellId])) {
                continue;
            }

            $difficulty = (int)($row['DifficultyID'] ?? 0);

            // Rows exist per difficulty; the base difficulty is the spell as a player casts it, and anything else
            // only fills in for a spell that has no base row at all
            if (isset($difficulties[$spellId]) && ($difficulty !== 0 || $difficulties[$spellId] === 0)) {
                continue;
            }

            $cooldowns[$spellId]    = max((int)($row['RecoveryTime'] ?? 0), (int)($row['CategoryRecoveryTime'] ?? 0));
            $difficulties[$spellId] = $difficulty;
        }

        return $cooldowns;
    }

    /**
     * @param  array<int, bool> $wantedIds
     * @return array<int, int>  spell id => SpellCategory id of its charges
     */
    private function readChargeCategories(string $build, array $wantedIds): array
    {
        $chargeCategories = [];
        $difficulties     = [];

        foreach ($this->readTable('SpellCategories', $build, ['SpellID', 'DifficultyID', 'ChargeCategory'], mustHaveRows: true) as $row) {
            $spellId = (int)($row['SpellID'] ?? 0);

            if (!isset($wantedIds[$spellId])) {
                continue;
            }

            $difficulty = (int)($row['DifficultyID'] ?? 0);

            if (isset($difficulties[$spellId]) && ($difficulty !== 0 || $difficulties[$spellId] === 0)) {
                continue;
            }

            $chargeCategories[$spellId] = (int)($row['ChargeCategory'] ?? 0);
            $difficulties[$spellId]     = $difficulty;
        }

        return $chargeCategories;
    }

    /**
     * @return array<int, array{maxCharges: int, chargeRecoveryTime: int}> SpellCategory id => its charges
     */
    private function readCharges(string $build): array
    {
        $charges = [];

        foreach ($this->readTable('SpellCategory', $build, ['ID', 'MaxCharges', 'ChargeRecoveryTime'], mustHaveRows: true) as $row) {
            $maxCharges = (int)($row['MaxCharges'] ?? 0);

            if ($maxCharges <= 0) {
                continue;
            }

            $charges[(int)($row['ID'] ?? 0)] = [
                'maxCharges'         => $maxCharges,
                'chargeRecoveryTime' => (int)($row['ChargeRecoveryTime'] ?? 0),
            ];
        }

        return $charges;
    }

    /**
     * @param array<int, bool> $presentIds
     *
     * @throws WagoToolsDownloadException
     */
    private function persistSpecializations(string $build, array $presentIds): int
    {
        $ourSpecializationIds = CharacterClassSpecialization::query()->pluck('id', 'specialization_id')
            ->mapWithKeys(static fn($id, $specializationId) => [(int)$specializationId => (int)$id])
            ->all();

        $blizzardSpecializationIds = $this->readSpecializationsBySpellId($build, $presentIds);

        $specializationIdsBySpellId = [];

        foreach (array_keys($presentIds) as $spellId) {
            $specializationIdsBySpellId[$spellId] = array_values(array_filter(array_map(
                static fn(int $blizzardSpecializationId) => $ourSpecializationIds[$blizzardSpecializationId] ?? null,
                $blizzardSpecializationIds[$spellId] ?? [],
            )));
        }

        return $this->spellSpecializationRepository->replaceForSpells($specializationIdsBySpellId);
    }

    /**
     * @param  array<int, bool>      $wantedIds
     * @return array<int, list<int>> spell id => Blizzard specialization ids
     *
     * @throws WagoToolsDownloadException
     */
    private function readSpecializationsBySpellId(string $build, array $wantedIds): array
    {
        $classIdBySpecializationId = [];
        $specializationIdsByClass  = [];

        foreach ($this->readTable('ChrSpecialization', $build, ['ID', 'ClassID'], mustHaveRows: true) as $row) {
            $specializationId = (int)($row['ID'] ?? 0);
            $classId          = (int)($row['ClassID'] ?? 0);

            $classIdBySpecializationId[$specializationId] = $classId;
            $specializationIdsByClass[$classId][]         = $specializationId;
        }

        $classIdByTraitTreeId = $this->readClassIdByTraitTreeId($build, $classIdBySpecializationId);

        $sourceSpellIdsBySpellId = $this->readSourceSpellIdsBySpellId($build);
        $resolvableIds           = $this->collectLinkedSpellIds($wantedIds, $sourceSpellIdsBySpellId);

        // 1. A specialization's own spells, and the spells they override
        $fromSpecializationSpells = [];
        $fromLinks                = [];

        foreach ($this->readTable('SpecializationSpells', $build, ['SpecID', 'SpellID', 'OverridesSpellID']) as $row) {
            $spellId           = (int)($row['SpellID'] ?? 0);
            $overriddenSpellId = (int)($row['OverridesSpellID'] ?? 0);
            $specializationId  = (int)($row['SpecID'] ?? 0);

            if (isset($resolvableIds[$spellId])) {
                $fromSpecializationSpells[$spellId][$specializationId] = true;
            }

            if ($overriddenSpellId !== $spellId && isset($resolvableIds[$overriddenSpellId])) {
                $fromLinks[$overriddenSpellId][$specializationId] = true;
            }
        }

        // 2. The talent trees
        [$fromTalents, $fromTalentLinks] = $this->readTalentSpecializations(
            $build,
            $resolvableIds,
            $classIdByTraitTreeId,
            $specializationIdsByClass,
        );

        foreach ($fromTalentLinks as $spellId => $specializationIds) {
            $fromLinks[$spellId] = ($fromLinks[$spellId] ?? []) + $specializationIds;
        }

        // 3. The class's own skill line
        $classIdBySkillLineId = [];

        foreach ($this->readTable('SkillLineXTraitTree', $build, ['SkillLineID', 'TraitTreeID']) as $row) {
            $traitTreeId = (int)($row['TraitTreeID'] ?? 0);

            if (isset($classIdByTraitTreeId[$traitTreeId])) {
                $classIdBySkillLineId[(int)($row['SkillLineID'] ?? 0)] = $classIdByTraitTreeId[$traitTreeId];
            }
        }

        $fromClassSkillLine = [];

        foreach ($this->readTable('SkillLineAbility', $build, ['SkillLine', 'Spell']) as $row) {
            $spellId = (int)($row['Spell'] ?? 0);
            $classId = $classIdBySkillLineId[(int)($row['SkillLine'] ?? 0)] ?? null;

            if ($classId !== null && isset($resolvableIds[$spellId])) {
                foreach ($specializationIdsByClass[$classId] ?? [] as $specializationId) {
                    $fromClassSkillLine[$spellId][$specializationId] = true;
                }
            }
        }

        $placedSpecializationIds = [];

        foreach (array_keys($resolvableIds) as $spellId) {
            $specializationIds = ($fromSpecializationSpells[$spellId] ?? []) + ($fromTalents[$spellId] ?? []);

            if ($specializationIds === []) {
                $specializationIds = $fromClassSkillLine[$spellId] ?? [];
            }

            if ($specializationIds !== []) {
                $placedSpecializationIds[$spellId] = $specializationIds;
            }
        }

        $result = [];

        foreach (array_keys($wantedIds) as $spellId) {
            $specializationIds = $placedSpecializationIds[$spellId] ?? $this->findLinkedSpecializationIds(
                $spellId,
                $placedSpecializationIds,
                $fromLinks,
                $sourceSpellIdsBySpellId,
                $classIdBySpecializationId,
            );

            if ($specializationIds !== []) {
                $result[$spellId] = array_keys($specializationIds);
            }
        }

        return $result;
    }

    /**
     * The spells the client links each spell to: the spell replacing it, the spells that teach it and the spells
     * with an effect that triggers it.
     *
     * @return array<int, array<int, bool>> spell id => source spell id => true
     *
     * @throws WagoToolsDownloadException
     */
    private function readSourceSpellIdsBySpellId(string $build): array
    {
        $links = [
            ['SpellReplacement', 'SpellID', 'ReplacementSpellID'],
            ['SpellLearnSpell', 'LearnSpellID', 'SpellID'],
            ['SpellEffect', 'EffectTriggerSpell', 'SpellID'],
        ];

        $result = [];

        foreach ($links as [$table, $spellIdColumn, $sourceSpellIdColumn]) {
            foreach ($this->readTable($table, $build, [$spellIdColumn, $sourceSpellIdColumn]) as $row) {
                $spellId       = (int)($row[$spellIdColumn] ?? 0);
                $sourceSpellId = (int)($row[$sourceSpellIdColumn] ?? 0);

                if ($spellId !== 0 && $sourceSpellId !== 0 && $spellId !== $sourceSpellId) {
                    $result[$spellId][$sourceSpellId] = true;
                }
            }
        }

        return $result;
    }

    /**
     * The wanted spells, plus every spell up to MAX_LINK_DEPTH links away from one of them.
     *
     * @param  array<int, bool>             $wantedIds
     * @param  array<int, array<int, bool>> $sourceSpellIdsBySpellId
     * @return array<int, bool>
     */
    private function collectLinkedSpellIds(array $wantedIds, array $sourceSpellIdsBySpellId): array
    {
        $result   = $wantedIds;
        $frontier = array_keys($wantedIds);

        for ($depth = 0; $depth < self::MAX_LINK_DEPTH && $frontier !== []; $depth++) {
            $next = [];

            foreach ($frontier as $spellId) {
                foreach (array_keys($sourceSpellIdsBySpellId[$spellId] ?? []) as $sourceSpellId) {
                    if (!isset($result[$sourceSpellId])) {
                        $result[$sourceSpellId] = true;
                        $next[]                 = $sourceSpellId;
                    }
                }
            }

            $frontier = $next;
        }

        return $result;
    }

    /**
     * The specializations of the nearest spells a spell is linked to, at most MAX_LINK_DEPTH links away. A spell
     * already visited is not walked again.
     *
     * @param  array<int, array<int, bool>> $placedSpecializationIds   spell id => specializations a source placed it in
     * @param  array<int, array<int, bool>> $linkedSpecializationIds   spell id => specializations of the talents and
     *                                                                 specialization spells overriding or showing it
     * @param  array<int, array<int, bool>> $sourceSpellIdsBySpellId
     * @param  array<int, int>              $classIdBySpecializationId
     * @return array<int, bool>             Blizzard specialization id => true
     */
    private function findLinkedSpecializationIds(
        int   $spellId,
        array $placedSpecializationIds,
        array $linkedSpecializationIds,
        array $sourceSpellIdsBySpellId,
        array $classIdBySpecializationId,
    ): array {
        $visited  = [$spellId => true];
        $frontier = [$spellId];

        for ($depth = 0; $depth < self::MAX_LINK_DEPTH && $frontier !== []; $depth++) {
            $found = [];
            $next  = [];

            foreach ($frontier as $linkedSpellId) {
                $found += $linkedSpecializationIds[$linkedSpellId] ?? [];

                foreach (array_keys($sourceSpellIdsBySpellId[$linkedSpellId] ?? []) as $sourceSpellId) {
                    if (isset($visited[$sourceSpellId])) {
                        continue;
                    }

                    $visited[$sourceSpellId] = true;

                    if (isset($placedSpecializationIds[$sourceSpellId])) {
                        $found += $placedSpecializationIds[$sourceSpellId];
                    } else {
                        $next[] = $sourceSpellId;
                    }
                }
            }

            if ($found !== []) {
                $classIds = array_unique(array_map(
                    static fn(int $specializationId) => $classIdBySpecializationId[$specializationId] ?? 0,
                    array_keys($found),
                ));

                return count($classIds) === 1 ? $found : [];
            }

            $frontier = $next;
        }

        return [];
    }

    /**
     * The class each talent tree belongs to, from the specializations it has starter loadouts for. A tree with
     * loadouts for more than one class is no class's talent tree.
     *
     * @param  array<int, int> $classIdBySpecializationId
     * @return array<int, int> TraitTree id => ChrClasses id
     *
     * @throws WagoToolsDownloadException
     */
    private function readClassIdByTraitTreeId(string $build, array $classIdBySpecializationId): array
    {
        $classIdsByTraitTreeId = [];

        foreach ($this->readTable('TraitTreeLoadout', $build, ['TraitTreeID', 'ChrSpecializationID']) as $row) {
            $classId = $classIdBySpecializationId[(int)($row['ChrSpecializationID'] ?? 0)] ?? 0;

            if ($classId !== 0) {
                $classIdsByTraitTreeId[(int)($row['TraitTreeID'] ?? 0)][$classId] = true;
            }
        }

        $classIdByTraitTreeId = [];

        foreach ($classIdsByTraitTreeId as $traitTreeId => $classIds) {
            if (count($classIds) === 1) {
                $classIdByTraitTreeId[$traitTreeId] = array_key_first($classIds);
            }
        }

        return $classIdByTraitTreeId;
    }

    /**
     * @param  array<int, bool>                                                        $wantedIds
     * @param  array<int, int>                                                         $classIdByTraitTreeId
     * @param  array<int, list<int>>                                                   $specializationIdsByClass
     * @return array{0: array<int, array<int, bool>>, 1: array<int, array<int, bool>>} the talents' own spells, and
     *                                                                                 the spells they override or show;
     *                                                                                 spell id => Blizzard
     *                                                                                 specialization id => true
     *
     * @throws WagoToolsDownloadException
     */
    private function readTalentSpecializations(
        string $build,
        array  $wantedIds,
        array  $classIdByTraitTreeId,
        array  $specializationIdsByClass,
    ): array {
        $spellIdsByDefinitionId       = [];
        $linkedSpellIdsByDefinitionId = [];

        foreach ($this->readTable('TraitDefinition', $build, ['ID', 'SpellID', 'OverridesSpellID', 'VisibleSpellID']) as $row) {
            $definitionId = (int)($row['ID'] ?? 0);
            $spellId      = (int)($row['SpellID'] ?? 0);

            if (isset($wantedIds[$spellId])) {
                $spellIdsByDefinitionId[$definitionId] = $spellId;
            }

            foreach (['OverridesSpellID', 'VisibleSpellID'] as $column) {
                $linkedSpellId = (int)($row[$column] ?? 0);

                if ($linkedSpellId !== $spellId && isset($wantedIds[$linkedSpellId])) {
                    $linkedSpellIdsByDefinitionId[$definitionId][$linkedSpellId] = true;
                }
            }
        }

        $definitionIdByEntryId = [];

        foreach ($this->readTable('TraitNodeEntry', $build, ['ID', 'TraitDefinitionID']) as $row) {
            $definitionId = (int)($row['TraitDefinitionID'] ?? 0);

            if (isset($spellIdsByDefinitionId[$definitionId]) || isset($linkedSpellIdsByDefinitionId[$definitionId])) {
                $definitionIdByEntryId[(int)($row['ID'] ?? 0)] = $definitionId;
            }
        }

        $spellIdsByNodeId       = [];
        $linkedSpellIdsByNodeId = [];

        foreach ($this->readTable('TraitNodeXTraitNodeEntry', $build, ['TraitNodeID', 'TraitNodeEntryID']) as $row) {
            $definitionId = $definitionIdByEntryId[(int)($row['TraitNodeEntryID'] ?? 0)] ?? null;

            if ($definitionId === null) {
                continue;
            }

            $nodeId = (int)($row['TraitNodeID'] ?? 0);

            if (isset($spellIdsByDefinitionId[$definitionId])) {
                $spellIdsByNodeId[$nodeId][$spellIdsByDefinitionId[$definitionId]] = true;
            }

            $linkedSpellIdsByNodeId[$nodeId] = ($linkedSpellIdsByNodeId[$nodeId] ?? []) + ($linkedSpellIdsByDefinitionId[$definitionId] ?? []);
        }

        $classIdByNodeId = [];

        foreach ($this->readTable('TraitNode', $build, ['ID', 'TraitTreeID']) as $row) {
            $nodeId  = (int)($row['ID'] ?? 0);
            $classId = $classIdByTraitTreeId[(int)($row['TraitTreeID'] ?? 0)] ?? null;

            if ($classId !== null && (isset($spellIdsByNodeId[$nodeId]) || ($linkedSpellIdsByNodeId[$nodeId] ?? []) !== [])) {
                $classIdByNodeId[$nodeId] = $classId;
            }
        }

        $allowedSpecializationIdsByNodeId = $this->readNodeSpecializationRestrictions($build, $classIdByNodeId);

        $result       = [];
        $linkedResult = [];

        foreach ($classIdByNodeId as $nodeId => $classId) {
            $specializationIds = $allowedSpecializationIdsByNodeId[$nodeId] ?? null;

            foreach ($specializationIdsByClass[$classId] ?? [] as $specializationId) {
                if ($specializationIds !== null && !isset($specializationIds[$specializationId])) {
                    continue;
                }

                foreach (array_keys($spellIdsByNodeId[$nodeId] ?? []) as $spellId) {
                    $result[$spellId][$specializationId] = true;
                }

                foreach (array_keys($linkedSpellIdsByNodeId[$nodeId] ?? []) as $linkedSpellId) {
                    $linkedResult[$linkedSpellId][$specializationId] = true;
                }
            }
        }

        return [$result, $linkedResult];
    }

    /**
     * The specializations a node is restricted to, by the spec-set conditions on the node itself or on any group
     * it is in. A node without one is open to the whole class its tree belongs to, and is absent from the result.
     *
     * @param  array<int, int>              $classIdByNodeId only these nodes are looked at
     * @return array<int, array<int, bool>> TraitNode id => Blizzard specialization id => true
     *
     * @throws WagoToolsDownloadException
     */
    private function readNodeSpecializationRestrictions(string $build, array $classIdByNodeId): array
    {
        $specializationIdsBySpecSetId = [];

        foreach ($this->readTable('SpecSetMember', $build, ['ChrSpecializationID', 'SpecSet']) as $row) {
            $specializationIdsBySpecSetId[(int)($row['SpecSet'] ?? 0)][(int)($row['ChrSpecializationID'] ?? 0)] = true;
        }

        $specSetIdByCondId = [];

        foreach ($this->readTable('TraitCond', $build, ['ID', 'CondType', 'SpecSetID']) as $row) {
            $specSetId = (int)($row['SpecSetID'] ?? 0);

            if ($specSetId !== 0 && in_array((int)($row['CondType'] ?? -1), self::RESTRICTING_TRAIT_COND_TYPES, true)) {
                $specSetIdByCondId[(int)($row['ID'] ?? 0)] = $specSetId;
            }
        }

        $condIdsByNodeId = [];

        foreach ($this->readTable('TraitNodeXTraitCond', $build, ['TraitCondID', 'TraitNodeID']) as $row) {
            $nodeId = (int)($row['TraitNodeID'] ?? 0);
            $condId = (int)($row['TraitCondID'] ?? 0);

            if (isset($classIdByNodeId[$nodeId], $specSetIdByCondId[$condId])) {
                $condIdsByNodeId[$nodeId][] = $condId;
            }
        }

        $condIdsByGroupId = [];

        foreach ($this->readTable('TraitNodeGroupXTraitCond', $build, ['TraitCondID', 'TraitNodeGroupID']) as $row) {
            $condId = (int)($row['TraitCondID'] ?? 0);

            if (isset($specSetIdByCondId[$condId])) {
                $condIdsByGroupId[(int)($row['TraitNodeGroupID'] ?? 0)][] = $condId;
            }
        }

        foreach ($this->readTable('TraitNodeGroupXTraitNode', $build, ['TraitNodeGroupID', 'TraitNodeID']) as $row) {
            $nodeId  = (int)($row['TraitNodeID'] ?? 0);
            $groupId = (int)($row['TraitNodeGroupID'] ?? 0);

            if (isset($classIdByNodeId[$nodeId], $condIdsByGroupId[$groupId])) {
                $condIdsByNodeId[$nodeId] = [...($condIdsByNodeId[$nodeId] ?? []), ...$condIdsByGroupId[$groupId]];
            }
        }

        $result = [];

        foreach ($condIdsByNodeId as $nodeId => $condIds) {
            foreach ($condIds as $condId) {
                $result[$nodeId] = ($result[$nodeId] ?? []) + ($specializationIdsBySpecSetId[$specSetIdByCondId[$condId]] ?? []);
            }
        }

        return $result;
    }

    /**
     * Take the cooldown of the player spells the build does not know from Wowhead's tooltip. A value an earlier
     * build's DB2 gave us is kept: it is still the client's own data.
     *
     * @param Collection<int, Spell> $spells
     * @param array<int, bool>       $presentIds
     */
    private function fillGapsFromWowhead(Collection $spells, array $presentIds): int
    {
        $unknownCategory = SpellCategory::Unknown->translationKey();
        $filledCount     = 0;

        foreach ($spells as $spell) {
            if (isset($presentIds[$spell->id])
                || $spell->category === null
                || $spell->category === $unknownCategory
                || $spell->cooldown_source === SpellCooldownSource::Db2) {
                continue;
            }

            $tooltip = $this->wowheadService->getSpellTooltipText($spell->id);
            $parsed  = $tooltip === null ? null : $this->parseWowheadTooltip($tooltip);

            if ($parsed === null) {
                $this->log->fillGapsFromWowheadNoCooldown($spell->id);

                continue;
            }

            Spell::query()
                ->whereKey($spell->id)
                ->update([
                    'cooldown_ms'        => $parsed['cooldownMs'],
                    'charges'            => $parsed['charges'],
                    'charge_cooldown_ms' => null,
                    'cooldown_source'    => SpellCooldownSource::Wowhead->value,
                ]);

            $this->log->fillGapsFromWowheadFilled($spell->id, $parsed['cooldownMs'], $parsed['charges']);

            $filledCount++;
        }

        return $filledCount;
    }

    /**
     * Wowhead's tooltip states the base cooldown as e.g. `2 min cooldown` or `14 sec cooldown`, followed by
     * `2 Charges` for a spell with charges - in which case the cooldown is the time one charge takes to return.
     *
     * @return array{cooldownMs: int, charges: int|null}|null
     */
    private function parseWowheadTooltip(string $tooltip): ?array
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(sec|min|hr|hour|hours)\s+(?:cooldown|recharge)/i', $tooltip, $matches) !== 1) {
            return null;
        }

        $multiplier = match (strtolower($matches[2])) {
            'sec'   => 1000,
            'min'   => 60_000,
            default => 3_600_000,
        };

        $charges = preg_match('/(\d+)\s+Charges?\b/i', $tooltip, $chargeMatches) === 1 ? (int)$chargeMatches[1] : null;

        return [
            'cooldownMs' => (int)round((float)$matches[1] * $multiplier),
            'charges'    => $charges,
        ];
    }

    /**
     * The rows of a DB2 table, refusing a table that lacks a column the import reads - a missing column would
     * otherwise read as "no value" for every row, and wipe what we have. With `$mustHaveRows`, a table without any
     * row is refused as well: every build has rows in the tables that carry them.
     *
     * @param  list<string>                          $columns
     * @return Generator<int, array<string, string>>
     *
     * @throws WagoToolsDownloadException
     */
    private function readTable(string $table, string $build, array $columns, bool $mustHaveRows = false): Generator
    {
        $hasRows = false;

        foreach ($this->wagoToolsService->readTable($table, $build) as $row) {
            if (!$hasRows) {
                $missingColumns = array_diff($columns, array_keys($row));

                if ($missingColumns !== []) {
                    throw new WagoToolsDownloadException(sprintf(
                        'DB2 table %s for build %s lacks the column(s) %s',
                        $table,
                        $build,
                        implode(', ', $missingColumns),
                    ));
                }

                $hasRows = true;
            }

            yield $row;
        }

        if ($mustHaveRows && !$hasRows) {
            throw new WagoToolsDownloadException(sprintf('DB2 table %s for build %s has no rows', $table, $build));
        }
    }
}
