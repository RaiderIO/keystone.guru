<?php

namespace App\Service\CombatLog\DataExtractors\SpellDataCollectors;

use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\Interrupt;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\Spell\Spell as SpellModel;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Database\UniqueConstraintViolationException;
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

        $existingSpell = $this->findSpell($spellId);
        if ($existingSpell !== null) {
            $this->repairMissingSchoolsMask($result, $existingSpell, $schoolsMask);

            return;
        }

        $this->createSpell($result, $spellId, $prefix->getSpellName(), $schoolsMask);
    }

    public function ensureInterruptSpellExists(ExtractedDataResult $result, Interrupt $interrupt): void
    {
        $spellId = $interrupt->getExtraSpellId();

        $existingSpell = $this->findSpell($spellId);
        if ($existingSpell !== null) {
            // The interrupted spell's school is trusted to create a spell on the line below, so it is good enough
            // to repair one too
            $this->repairMissingSchoolsMask($result, $existingSpell, $interrupt->getExtraSchool());

            return;
        }

        $this->createSpell($result, $spellId, $interrupt->getExtraSpellName(), $interrupt->getExtraSchool());
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
     * The catalog is shared across jobs in a long-lived worker (#4058), so it can miss a spell another
     * process created after the catalog was built. Never blind-create on a catalog miss - check the table
     * first, or the insert dies on a duplicate primary key.
     */
    private function findSpell(int $spellId): ?SpellModel
    {
        /** @var SpellModel|null $existingSpell */
        $existingSpell = $this->allSpells->get($spellId);
        if ($existingSpell === null) {
            $existingSpell = SpellModel::with('spellDungeons')->find($spellId);
            if ($existingSpell !== null) {
                $this->allSpells->put($spellId, $existingSpell);
            }
        }

        return $existingSpell;
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

    /**
     * Creates the spell, or - if another worker's job created the same spell id between our findSpell() check
     * and this insert - falls back to the existing-spell path instead of letting the duplicate primary key
     * abort the whole combat log import (#4151). The catalog is shared and long-lived (#4058), but findSpell()
     * only guards against catalog staleness, not against a genuine concurrent insert landing in between.
     */
    private function createSpell(ExtractedDataResult $result, int $spellId, string $name, int $schoolsMask): void
    {
        try {
            $createdSpell = SpellModel::create([
                'id'           => $spellId,
                'dispel_type'  => sprintf('spelldispeltype.%s', SpellModel::DISPEL_TYPE_UNKNOWN),
                'icon_name'    => '',
                'name'         => $name,
                'schools_mask' => $schoolsMask,
                'aura'         => false,
            ]);
        } catch (UniqueConstraintViolationException) {
            $existingSpell = SpellModel::with('spellDungeons')->find($spellId);
            if ($existingSpell !== null) {
                $this->allSpells->put($spellId, $existingSpell);
                $this->repairMissingSchoolsMask($result, $existingSpell, $schoolsMask);
            }

            return;
        }

        $createdSpell->setRelation('spellDungeons', collect());
        $result->createdSpell();

        $this->allSpells->put($spellId, $createdSpell);
        $this->pendingNewSpells->put($spellId, $createdSpell);

        $this->log->createMissingSpellCreatedSpell($createdSpell->name, $spellId);
    }
}
