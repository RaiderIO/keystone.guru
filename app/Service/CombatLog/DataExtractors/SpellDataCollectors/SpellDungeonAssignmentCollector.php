<?php

namespace App\Service\CombatLog\DataExtractors\SpellDataCollectors;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Models\Spell\Spell as SpellModel;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class SpellDungeonAssignmentCollector implements SpellDataCollectorInterface
{
    /** @var Collection<int, Collection<int, CombatLogEvent>> */
    private Collection $spellIdsForDungeon;

    /**
     * @param Collection<int, SpellModel> $allSpells
     */
    public function __construct(
        private readonly Collection                         $allSpells,
        private readonly SpellDataExtractorLoggingInterface $log,
    ) {
        $this->spellIdsForDungeon = collect();
    }

    public function beforeCollect(string $combatLogFilePath): void
    {
    }

    public function collect(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        CombatLogEvent               $parsedEvent,
        Spell                        $prefix,
    ): void {
        if (!$this->spellIdsForDungeon->has($currentDungeon->dungeon->id)) {
            $this->spellIdsForDungeon->put($currentDungeon->dungeon->id, collect());
        }

        /** @var Collection<int, CombatLogEvent> $spellIdsForDungeon */
        $spellIdsForDungeon = $this->spellIdsForDungeon->get($currentDungeon->dungeon->id);

        if (!$spellIdsForDungeon->has($prefix->getSpellId())) {
            $spellIdsForDungeon->put($prefix->getSpellId(), $parsedEvent);

            $spell = $this->allSpells->get($prefix->getSpellId());

            // Only assign spells that are NOT player spells!
            if (
                $spell !== null &&
                $spell->category === sprintf('spellcategory.%s', SpellModel::CATEGORY_UNKNOWN)
            ) {
                // If this dungeon wasn't assigned to the spell yet..
                if ($spell->spellDungeons
                    ->where('dungeon_id', $currentDungeon->dungeon->id)
                    ->isEmpty()) {
                    // Assign it
                    SpellDungeon::create([
                        'spell_id'   => $spell->id,
                        'dungeon_id' => $currentDungeon->dungeon->id,
                    ]);

                    $spell->unsetRelation('spellDungeons')->load('spellDungeons');

                    $result->createdSpellDungeon();

                    $this->log->assignDungeonToSpellAssignedDungeonToSpell($prefix->getSpellId(), $currentDungeon->dungeon->id);
                }
            }
        }
    }

    public function afterCollect(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->spellIdsForDungeon = collect();
    }
}
