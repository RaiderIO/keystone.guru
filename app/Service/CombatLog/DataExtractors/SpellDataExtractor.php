<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Dungeon;
use App\Models\Spell\Spell as SpellModel;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use App\Service\Wowhead\WowheadServiceInterface;
use Illuminate\Support\Collection;

class SpellDataExtractor implements DataExtractorInterface
{
    /** @var Collection<int, CombatLogEvent> */
    private Collection $spellIdsForDungeon;

    /** @var Collection<int, SpellModel> */
    private Collection $allSpells;

    private SpellDataExtractorLoggingInterface $log;

    public function __construct(
        private WowheadServiceInterface $wowheadService
    ) {
        $this->spellIdsForDungeon = collect();
        $this->allSpells          = SpellModel::all()->keyBy('id');

        $log = App::make(SpellDataExtractorLoggingInterface::class);
        /** @var SpellDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(): void
    {

    }

    public function extractData(ExtractedDataResult $result, DataExtractionCurrentDungeon $currentDungeon, BaseEvent $parsedEvent): void
    {
        if (!($parsedEvent instanceof CombatLogEvent)) {
            // Don't log anything because that'd just spam the hell out of it
            return;
        }

        $prefix = $parsedEvent->getPrefix();
        if (!($prefix instanceof Spell)) {
            return;
        }

        $suffix = $parsedEvent->getSuffix();
        if ($suffix instanceof AuraApplied) {
            $sourceGuid = $parsedEvent->getGenericData()->getSourceGuid();
            if ($sourceGuid instanceof Creature &&
                // Only actual creatures - not pets
                $sourceGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE) {
                if (!$this->spellIdsForDungeon->has($prefix->getSpellId())) {
                    $this->spellIdsForDungeon->put($prefix->getSpellId(), $parsedEvent);

                    $spell = SpellModel::find($prefix->getSpellId());
                    if ($spell !== null) {
                        // If this dungeon wasn't assigned to the spell yet..
                        if (!$spell->spellDungeons()
                            ->where('dungeon_id', $currentDungeon->dungeon->id)
                            ->exists()) {

                            // Assign it
                            SpellDungeon::create([
                                'spell_id'   => $spell->id,
                                'dungeon_id' => $currentDungeon->dungeon->id,
                            ]);

                            $this->log->extractDataAssignedDungeonToSpell($prefix->getSpellId(), $currentDungeon->dungeon->id);
                        }
                    }
                }
            }
        }
    }

    public function afterExtract(): void
    {
        // Now that we've extract spell data from the combat log, either update the data that's there in the database,
        // or fetch some additional info from Wowhead

        foreach ($this->spellIdsForDungeon as $dungeonId => $spellsByDungeon) {
            $this->log->afterExtractDungeonStart(__(Dungeon::findOrFail($dungeonId)->name, [], 'en_US'));

            foreach ($spellsByDungeon as $spellId => $combatLogEvent) {
                /**
                 * @var int            $spellId
                 * @var CombatLogEvent $combatLogEvent
                 */
                if ($this->allSpells->has($spellId)) {
                    // Update the spell based on a found combat log event
                    $this->updateSpell($this->allSpells->get($spellId), $combatLogEvent);
                    continue;
                }

                // Create a new spell and fetch the info for it
                $createdSpell = $this->createSpellAndFetchInfo($this->wowheadService, $spellId, $combatLogEvent);
                if ($createdSpell instanceof SpellModel) {
                    // Add to master list so that it doesn't get inserted twice
                    $this->allSpells->put($spellId, $createdSpell);

                    $this->log->afterExtractCreatedSpell($createdSpell->name, $spellId);
                }
            }
            $this->log->afterExtractDungeonEnd();
        }
    }

    private function updateSpell(SpellModel $spell, CombatLogEvent $combatLogEvent): bool
    {
        // Update aura state
        return $spell->update([
            'aura' => $combatLogEvent->getGenericData()->getDestGuid() instanceof Creature &&
                $combatLogEvent->getGenericData()->getDestGuid()->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE,
        ]);
    }

    private function createSpellAndFetchInfo(
        WowheadServiceInterface $wowheadService,
        int                     $spellId,
        CombatLogEvent          $combatLogEvent
    ): ?SpellModel {
        $spellDataResult = $wowheadService->getSpellData($spellId);

        $destGuid = $combatLogEvent->getGenericData()->getDestGuid();

        return SpellModel::create([
            'id'           => $spellId,
            'icon_name'    => $spellDataResult->getIconName(),
            'name'         => $spellDataResult->getName(),
            'dispel_type'  => $spellDataResult->getDispelType(),
            'schools_mask' => $spellDataResult->getSchoolsMask(),
            // Only when the target is a creature
            'aura'         => $destGuid instanceof Creature && $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE,
        ]);
    }
}
