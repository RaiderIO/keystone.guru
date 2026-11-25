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
 */
class SpellCooldownImportService implements SpellCooldownImportServiceInterface
{
    /** `TraitCond.CondType` values that restrict who may take a node, as opposed to granting or erroring on it. */
    private const array RESTRICTING_TRAIT_COND_TYPES = [
        0, // Available
        1, // Visible
    ];

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

        foreach ($this->wagoToolsService->readTable('SpellName', $build) as $row) {
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

        foreach ($this->wagoToolsService->readTable('SpellCooldowns', $build) as $row) {
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

        foreach ($this->wagoToolsService->readTable('SpellCategories', $build) as $row) {
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

        foreach ($this->wagoToolsService->readTable('SpellCategory', $build) as $row) {
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

        foreach ($this->wagoToolsService->readTable('ChrSpecialization', $build) as $row) {
            $specializationId = (int)($row['ID'] ?? 0);
            $classId          = (int)($row['ClassID'] ?? 0);

            $classIdBySpecializationId[$specializationId] = $classId;
            $specializationIdsByClass[$classId][]         = $specializationId;
        }

        $classIdByTraitTreeId = $this->readClassIdByTraitTreeId($build, $classIdBySpecializationId);

        // 1. A specialization's own spells
        $fromSpecializationSpells = [];

        foreach ($this->wagoToolsService->readTable('SpecializationSpells', $build) as $row) {
            $spellId = (int)($row['SpellID'] ?? 0);

            if (isset($wantedIds[$spellId])) {
                $fromSpecializationSpells[$spellId][(int)($row['SpecID'] ?? 0)] = true;
            }
        }

        // 2. The talent trees
        $fromTalents = $this->readTalentSpecializations($build, $wantedIds, $classIdByTraitTreeId, $specializationIdsByClass);

        // 3. The class's own skill line
        $classIdBySkillLineId = [];

        foreach ($this->wagoToolsService->readTable('SkillLineXTraitTree', $build) as $row) {
            $traitTreeId = (int)($row['TraitTreeID'] ?? 0);

            if (isset($classIdByTraitTreeId[$traitTreeId])) {
                $classIdBySkillLineId[(int)($row['SkillLineID'] ?? 0)] = $classIdByTraitTreeId[$traitTreeId];
            }
        }

        $fromClassSkillLine = [];

        foreach ($this->wagoToolsService->readTable('SkillLineAbility', $build) as $row) {
            $spellId = (int)($row['Spell'] ?? 0);
            $classId = $classIdBySkillLineId[(int)($row['SkillLine'] ?? 0)] ?? null;

            if ($classId !== null && isset($wantedIds[$spellId])) {
                foreach ($specializationIdsByClass[$classId] ?? [] as $specializationId) {
                    $fromClassSkillLine[$spellId][$specializationId] = true;
                }
            }
        }

        $result = [];

        foreach (array_keys($wantedIds) as $spellId) {
            $specializationIds = ($fromSpecializationSpells[$spellId] ?? []) + ($fromTalents[$spellId] ?? []);

            if ($specializationIds === []) {
                $specializationIds = $fromClassSkillLine[$spellId] ?? [];
            }

            if ($specializationIds !== []) {
                $result[$spellId] = array_keys($specializationIds);
            }
        }

        return $result;
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

        foreach ($this->wagoToolsService->readTable('TraitTreeLoadout', $build) as $row) {
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
     * @param  array<int, bool>             $wantedIds
     * @param  array<int, int>              $classIdByTraitTreeId
     * @param  array<int, list<int>>        $specializationIdsByClass
     * @return array<int, array<int, bool>> spell id => Blizzard specialization id => true
     *
     * @throws WagoToolsDownloadException
     */
    private function readTalentSpecializations(
        string $build,
        array  $wantedIds,
        array  $classIdByTraitTreeId,
        array  $specializationIdsByClass,
    ): array {
        $spellIdByDefinitionId = [];

        foreach ($this->wagoToolsService->readTable('TraitDefinition', $build) as $row) {
            $spellId = (int)($row['SpellID'] ?? 0);

            if (isset($wantedIds[$spellId])) {
                $spellIdByDefinitionId[(int)($row['ID'] ?? 0)] = $spellId;
            }
        }

        $spellIdByEntryId = [];

        foreach ($this->wagoToolsService->readTable('TraitNodeEntry', $build) as $row) {
            $spellId = $spellIdByDefinitionId[(int)($row['TraitDefinitionID'] ?? 0)] ?? null;

            if ($spellId !== null) {
                $spellIdByEntryId[(int)($row['ID'] ?? 0)] = $spellId;
            }
        }

        $spellIdsByNodeId = [];

        foreach ($this->wagoToolsService->readTable('TraitNodeXTraitNodeEntry', $build) as $row) {
            $spellId = $spellIdByEntryId[(int)($row['TraitNodeEntryID'] ?? 0)] ?? null;

            if ($spellId !== null) {
                $spellIdsByNodeId[(int)($row['TraitNodeID'] ?? 0)][$spellId] = true;
            }
        }

        $classIdByNodeId = [];

        foreach ($this->wagoToolsService->readTable('TraitNode', $build) as $row) {
            $nodeId  = (int)($row['ID'] ?? 0);
            $classId = $classIdByTraitTreeId[(int)($row['TraitTreeID'] ?? 0)] ?? null;

            if ($classId !== null && isset($spellIdsByNodeId[$nodeId])) {
                $classIdByNodeId[$nodeId] = $classId;
            }
        }

        $allowedSpecializationIdsByNodeId = $this->readNodeSpecializationRestrictions($build, $classIdByNodeId);

        $result = [];

        foreach ($classIdByNodeId as $nodeId => $classId) {
            $specializationIds = $allowedSpecializationIdsByNodeId[$nodeId] ?? null;

            foreach ($specializationIdsByClass[$classId] ?? [] as $specializationId) {
                if ($specializationIds !== null && !isset($specializationIds[$specializationId])) {
                    continue;
                }

                foreach (array_keys($spellIdsByNodeId[$nodeId]) as $spellId) {
                    $result[$spellId][$specializationId] = true;
                }
            }
        }

        return $result;
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

        foreach ($this->wagoToolsService->readTable('SpecSetMember', $build) as $row) {
            $specializationIdsBySpecSetId[(int)($row['SpecSet'] ?? 0)][(int)($row['ChrSpecializationID'] ?? 0)] = true;
        }

        $specSetIdByCondId = [];

        foreach ($this->wagoToolsService->readTable('TraitCond', $build) as $row) {
            $specSetId = (int)($row['SpecSetID'] ?? 0);

            if ($specSetId !== 0 && in_array((int)($row['CondType'] ?? -1), self::RESTRICTING_TRAIT_COND_TYPES, true)) {
                $specSetIdByCondId[(int)($row['ID'] ?? 0)] = $specSetId;
            }
        }

        $condIdsByNodeId = [];

        foreach ($this->wagoToolsService->readTable('TraitNodeXTraitCond', $build) as $row) {
            $nodeId = (int)($row['TraitNodeID'] ?? 0);
            $condId = (int)($row['TraitCondID'] ?? 0);

            if (isset($classIdByNodeId[$nodeId], $specSetIdByCondId[$condId])) {
                $condIdsByNodeId[$nodeId][] = $condId;
            }
        }

        $condIdsByGroupId = [];

        foreach ($this->wagoToolsService->readTable('TraitNodeGroupXTraitCond', $build) as $row) {
            $condId = (int)($row['TraitCondID'] ?? 0);

            if (isset($specSetIdByCondId[$condId])) {
                $condIdsByGroupId[(int)($row['TraitNodeGroupID'] ?? 0)][] = $condId;
            }
        }

        foreach ($this->wagoToolsService->readTable('TraitNodeGroupXTraitNode', $build) as $row) {
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
}
