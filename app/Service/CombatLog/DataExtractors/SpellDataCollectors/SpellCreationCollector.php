<?php

namespace App\Service\CombatLog\DataExtractors\SpellDataCollectors;

use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\Interrupt;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\Spell\Spell as SpellModel;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class SpellCreationCollector implements SpellDataCollectorInterface
{
    /**
     * Newly created spells — SpellCreated events written in afterCollect.
     *
     * @var Collection<int, SpellModel>
     */
    private Collection $pendingNewSpells;

    private ?string $currentCombatLogFilePath = null;

    /**
     * @param Collection<int, SpellModel> $allSpells
     */
    public function __construct(
        private readonly Collection                         $allSpells,
        private readonly SpellDataExtractorLoggingInterface $log,
    ) {
        $this->pendingNewSpells = collect();
    }

    public function beforeCollect(string $combatLogFilePath): void
    {
        $this->currentCombatLogFilePath = $combatLogFilePath;
    }

    public function ensureSpellExists(ExtractedDataResult $result, Spell $prefix): void
    {
        $spellId     = $prefix->getSpellId();
        $schoolsMask = $prefix->getSpellSchoolMask();

        /** @var SpellModel|null $existingSpell */
        $existingSpell = $this->allSpells->get($spellId);
        if ($existingSpell !== null) {
            $this->repairMissingSchoolsMask($result, $existingSpell, $schoolsMask);

            return;
        }

        $createdSpell = $this->createSpellModel($result, $spellId, $prefix->getSpellName(), $schoolsMask);
        $this->allSpells->put($spellId, $createdSpell);
        $this->pendingNewSpells->put($spellId, $createdSpell);

        $this->log->createMissingSpellCreatedSpell($createdSpell->name, $spellId);
    }

    public function ensureInterruptSpellExists(ExtractedDataResult $result, Interrupt $interrupt): void
    {
        $spellId = $interrupt->getExtraSpellId();

        /** @var SpellModel|null $existingSpell */
        $existingSpell = $this->allSpells->get($spellId);
        if ($existingSpell !== null) {
            // The interrupted spell's school is trusted to create a spell on the line below, so it is good enough
            // to repair one too
            $this->repairMissingSchoolsMask($result, $existingSpell, $interrupt->getExtraSchool());

            return;
        }

        $createdSpell = $this->createSpellModel($result, $spellId, $interrupt->getExtraSpellName(), $interrupt->getExtraSchool());
        $this->allSpells->put($spellId, $createdSpell);
        $this->pendingNewSpells->put($spellId, $createdSpell);

        $this->log->createMissingSpellCreatedSpell($createdSpell->name, $spellId);
    }

    public function afterCollect(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        foreach ($this->pendingNewSpells as $spellId => $spell) {
            CombatLogSpellEvent::create([
                'spell_id'        => $spellId,
                'event_type'      => CombatLogSpellEventType::SpellCreated,
                'property'        => null,
                'combat_log_path' => $this->currentCombatLogFilePath,
            ]);
        }

        $this->pendingNewSpells         = collect();
        $this->currentCombatLogFilePath = null;
    }

    /**
     * Backfills the school of a spell that has none. Spells created from a combat log before #3845 all got
     * schools_mask 0, and their real school cannot be recovered from the database - only from a log that mentions
     * them again, which is exactly here. Spells that legitimately have no school log 0x0 and stay untouched.
     */
    private function repairMissingSchoolsMask(ExtractedDataResult $result, SpellModel $spell, int $schoolsMask): void
    {
        if ($schoolsMask === 0 || $spell->schools_mask !== 0) {
            return;
        }

        $spell->schools_mask = $schoolsMask;

        if ($spell->save()) {
            // Every other spell mutation in this pipeline is auditable from the Compendium's activity feed and
            // attributable to a combat log; a repaired school is no different
            CombatLogSpellEvent::create([
                'spell_id'        => $spell->id,
                'event_type'      => CombatLogSpellEventType::SchoolRecorded,
                'property'        => null,
                'combat_log_path' => $this->currentCombatLogFilePath,
            ]);

            $result->updatedSpell();
            $this->log->ensureSpellExistsRepairedSchoolsMask($spell->id, $schoolsMask);
        }
    }

    private function createSpellModel(ExtractedDataResult $result, int $spellId, string $name, int $schoolsMask): SpellModel
    {
        $createdSpell = SpellModel::create([
            'id'           => $spellId,
            'dispel_type'  => '',
            'icon_name'    => '',
            'name'         => $name,
            'schools_mask' => $schoolsMask,
            'aura'         => false,
        ]);
        $createdSpell->setRelation('spellDungeons', collect());
        $result->createdSpell();

        return $createdSpell;
    }
}
