<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Guid;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use App\Service\CombatLog\Dtos\DataExtraction\NpcHealthObservation;
use Illuminate\Support\Collection;

/**
 * Collects the max HP every real NPC (no pets, no player-owned guardians) was seen with during a challenge mode,
 * so the key-level scaling can be reversed into the base health the mapping stores (#4094).
 *
 * This extractor only observes - it never touches the database. It is deliberately NOT part of
 * {@see DataExtractorFactory}: npc_healths is seeded mapping data, so writing it is a triggered action
 * (combatlog:extractnpchealth, which feeds the observations to NpcHealthExtractionService), not something every
 * ingested log gets to do. Observations accumulate across files on purpose - a Raider.IO run is 6-10 segment files
 * and each NPC shows up in only some of them.
 */
class NpcHealthDataExtractor implements DataExtractorInterface
{
    /** @var Collection<string, NpcHealthObservation> keyed by "<dungeonId>-<npcId>-<keyLevel>" */
    private Collection $observations;

    public function __construct()
    {
        $this->observations = collect();
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
        // Outside a challenge mode (ZoneChange only) we don't know what the max HP was scaled by
        if ($currentDungeon->keyLevel === null || !($parsedEvent instanceof AdvancedCombatLogEvent)) {
            return;
        }

        $advancedData = $parsedEvent->getAdvancedData();

        // Cheap raw-string check before parsing the info GUID into a Guid instance
        if (!Guid::isCreatureGuidString($advancedData->getInfoGuidRaw())) {
            return;
        }

        $guid = $advancedData->getInfoGuid();
        if (!($guid instanceof Creature) || $guid->getUnitType() !== Creature::CREATURE_UNIT_TYPE_CREATURE) {
            return;
        }

        // Player-owned guardians/totems carry an owner GUID - not dungeon NPCs
        if ($advancedData->getOwnerGuid() !== null) {
            return;
        }

        $maxHp = $advancedData->getMaxHP();
        if ($maxHp <= 0) {
            return;
        }

        // Keyed by dungeon too: files of different dungeons may share NPC ids, and the command's "one dungeon at a
        // time" guard relies on seeing both
        $key = sprintf('%d-%d-%d', $currentDungeon->dungeon->id, $guid->getId(), $currentDungeon->keyLevel);

        /** @var NpcHealthObservation|null $observation */
        $observation = $this->observations->get($key);
        if ($observation === null) {
            $observation = new NpcHealthObservation(
                $guid->getId(),
                $currentDungeon->dungeon->id,
                $currentDungeon->keyLevel,
                $this->getAffixes($currentDungeon),
            );
            $this->observations->put($key, $observation);
        }

        $observation->addSample($maxHp);
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
    }

    /**
     * @return Collection<string, NpcHealthObservation>
     */
    public function getObservations(): Collection
    {
        return $this->observations;
    }

    public function reset(): void
    {
        $this->observations = collect();
    }

    /**
     * Only evaluated once per NPC (on its first sighting), so the pluck() is not a per-line cost.
     *
     * @return array<int, string>
     */
    private function getAffixes(DataExtractionCurrentDungeon $currentDungeon): array
    {
        // The affix group can be null for PTR keys for example, which can have arbitrary affixes
        return $currentDungeon->affixGroup?->affixes->pluck('key')->toArray() ?? [];
    }
}
