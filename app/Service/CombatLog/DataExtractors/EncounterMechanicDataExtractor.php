<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Range;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\EncounterEndInterface;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\EncounterStartInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Repositories\Interfaces\CombatLog\CombatLogEncounterObservationRepositoryInterface;
use App\Service\CombatLog\DataExtractors\EncounterMechanics\EncounterObservation;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;

/**
 * Records what every boss encounter attempt in a combat log showed: per NPC its reaction, max HP, spawn waves,
 * summoner, deaths and the player damage it took, the stretches the most-attacked units took no player damage, the
 * units whose HP moved in lockstep and the moments the whole party was forced to move. One raw observation per
 * attempt, kill or wipe; an attempt without an ENCOUNTER_END in the file is dropped. Deciding which of it is a
 * mechanic is left to the aggregation.
 */
class EncounterMechanicDataExtractor implements DataExtractorInterface
{
    private const string EVENT_SPELL_SUMMON       = 'SPELL_SUMMON';
    private const string EVENT_SPELL_AURA_APPLIED = 'SPELL_AURA_APPLIED';

    private const array CAST_EVENTS = [
        'SPELL_CAST_START'   => true,
        'SPELL_CAST_SUCCESS' => true,
    ];

    /** SWING_DAMAGE_LANDED repeats SWING_DAMAGE from the target's side and is left out so a swing counts once */
    private const array DAMAGE_EVENTS = [
        'SPELL_DAMAGE'          => true,
        'SPELL_PERIODIC_DAMAGE' => true,
        'SPELL_BUILDING_DAMAGE' => true,
        'RANGE_DAMAGE'          => true,
        'SWING_DAMAGE'          => true,
    ];

    private ?EncounterObservation $encounterObservation = null;

    private string $combatLogFilePath = '';

    /** @var list<array<string, mixed>> */
    private array $records = [];

    public function __construct(
        private readonly CombatLogEncounterObservationRepositoryInterface $combatLogEncounterObservationRepository,
    ) {
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->encounterObservation = null;
        $this->combatLogFilePath    = $combatLogFilePath;
        $this->records              = [];
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
        if ($this->encounterObservation === null) {
            if ($parsedEvent instanceof EncounterStartInterface) {
                $this->startEncounter($currentDungeon, $parsedEvent);
            }

            return;
        }

        if ($parsedEvent instanceof CombatLogEvent) {
            $this->extractCombatLogEvent($this->encounterObservation, $parsedEvent);
        } elseif ($parsedEvent instanceof UnitDied && $parsedEvent->getEventName() === SpecialEvent::SPECIAL_EVENT_UNIT_DIED) {
            $this->encounterObservation->recordDeath(
                $parsedEvent->getGenericData()->getDestGuidRaw(),
                $parsedEvent->getTimestamp()->getTimestampMs(),
            );
        } elseif ($parsedEvent instanceof EncounterEndInterface) {
            $this->endEncounter($this->encounterObservation, $parsedEvent);
        } elseif ($parsedEvent instanceof EncounterStartInterface) {
            // The previous attempt never ended in this file
            $this->startEncounter($currentDungeon, $parsedEvent);
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        if ($this->records !== []) {
            $this->combatLogEncounterObservationRepository->insert($this->records);
        }

        $this->encounterObservation = null;
        $this->records              = [];
    }

    private function startEncounter(DataExtractionCurrentDungeon $currentDungeon, EncounterStartInterface&BaseEvent $encounterStart): void
    {
        $this->encounterObservation = new EncounterObservation(
            $encounterStart->getEncounterId(),
            $currentDungeon->dungeon->id,
            $currentDungeon->keyLevel,
            $currentDungeon->affixGroup?->id,
            $encounterStart->getTimestamp()->getTimestampMs(),
        );
    }

    private function endEncounter(EncounterObservation $encounterObservation, EncounterEndInterface&BaseEvent $encounterEnd): void
    {
        $this->encounterObservation = null;

        if ($encounterEnd->getEncounterId() !== $encounterObservation->encounterId) {
            return;
        }

        $now             = Carbon::now();
        $this->records[] = [
            'dungeon_id'      => $encounterObservation->dungeonId,
            'encounter_id'    => $encounterObservation->encounterId,
            'run_key'         => WalkGraphDataExtractor::getRunKey($this->combatLogFilePath),
            'combat_log_path' => $this->combatLogFilePath,
            'key_level'       => $encounterObservation->keyLevel,
            'affix_group_id'  => $encounterObservation->affixGroupId,
            'success'         => $encounterEnd->getSuccess() === 1,
            'duration_ms'     => $encounterEnd->getTimestamp()->getTimestampMs() - $encounterObservation->startMs,
            'data'            => json_encode($encounterObservation->toData()),
            'created_at'      => $now,
            'updated_at'      => $now,
        ];
    }

    private function extractCombatLogEvent(EncounterObservation $encounterObservation, CombatLogEvent $combatLogEvent): void
    {
        $timestampMs = $combatLogEvent->getTimestamp()->getTimestampMs();
        $genericData = $combatLogEvent->getGenericData();
        $sourceGuid  = $genericData->getSourceGuidRaw();
        $destGuid    = $genericData->getDestGuidRaw();

        $encounterObservation->seeUnit($sourceGuid, $genericData->getSourceFlags(), $timestampMs);
        $encounterObservation->seeUnit($destGuid, $genericData->getDestFlags(), $timestampMs);

        if ($combatLogEvent instanceof AdvancedCombatLogEvent) {
            $advancedData = $combatLogEvent->getAdvancedData();
            $infoGuid     = $advancedData->getInfoGuidRaw();
            if (str_starts_with($infoGuid, 'Player-')) {
                $encounterObservation->recordPlayerPosition(
                    $infoGuid,
                    $advancedData->getPositionX(),
                    $advancedData->getPositionY(),
                    $advancedData->getUiMapId(),
                    $timestampMs,
                );
            } elseif ($encounterObservation->isTracked($infoGuid)) {
                if (str_starts_with($advancedData->getOwnerGuidRaw(), 'Player-')) {
                    $encounterObservation->markPlayerOwned($infoGuid);
                } else {
                    $encounterObservation->recordHealth($infoGuid, $advancedData->getCurrentHP(), $advancedData->getMaxHP(), $timestampMs);
                }
            }
        }

        $eventName = $combatLogEvent->getEventName();
        if (isset(self::DAMAGE_EVENTS[$eventName])) {
            if ($encounterObservation->isTracked($destGuid) &&
                $encounterObservation->isPlayerSource($sourceGuid, $genericData->getSourceFlags())) {
                $encounterObservation->recordPlayerDamage($destGuid, $timestampMs);
            }
        } elseif ($eventName === self::EVENT_SPELL_AURA_APPLIED) {
            $prefix = $combatLogEvent->getPrefix();
            if ($prefix instanceof Range &&
                $encounterObservation->isTracked($destGuid) &&
                !$encounterObservation->isPlayerSource($sourceGuid, $genericData->getSourceFlags())) {
                $encounterObservation->recordAuraApplied($destGuid, $prefix->getSpellId(), $timestampMs);
            }
        } elseif (isset(self::CAST_EVENTS[$eventName])) {
            $prefix = $combatLogEvent->getPrefix();
            if ($prefix instanceof Range) {
                $encounterObservation->recordCast($sourceGuid, $prefix->getSpellId(), $timestampMs);
            }
        } elseif ($eventName === self::EVENT_SPELL_SUMMON) {
            $encounterObservation->recordSummon(
                $sourceGuid,
                EncounterObservation::isPlayerControlled($sourceGuid, $genericData->getSourceFlags()),
                $destGuid,
            );
        }
    }
}
