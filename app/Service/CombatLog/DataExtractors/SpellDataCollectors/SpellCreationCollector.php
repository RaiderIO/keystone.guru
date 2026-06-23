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
        $spellId = $prefix->getSpellId();
        if ($this->allSpells->has($spellId)) {
            return;
        }

        $createdSpell = $this->createSpellModel($result, $spellId, $prefix->getSpellName(), (int)$prefix->getSpellSchool());
        $this->allSpells->put($spellId, $createdSpell);
        $this->pendingNewSpells->put($spellId, $createdSpell);

        $this->log->createMissingSpellCreatedSpell($createdSpell->name, $spellId);
    }

    public function ensureInterruptSpellExists(ExtractedDataResult $result, Interrupt $interrupt): void
    {
        $spellId = $interrupt->getExtraSpellId();
        if ($this->allSpells->has($spellId)) {
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
