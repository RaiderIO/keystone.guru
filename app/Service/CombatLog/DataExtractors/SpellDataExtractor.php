<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBroken;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBrokenSpell;
use App\Logic\CombatLog\CombatEvents\Suffixes\Interrupt;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Models\Spell\Spell as SpellModel;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\DataExtractors\SpellDataCollectors\NpcSpellAssignmentCollector;
use App\Service\CombatLog\DataExtractors\SpellDataCollectors\SpellCreationCollector;
use App\Service\CombatLog\DataExtractors\SpellDataCollectors\SpellDataCollectorInterface;
use App\Service\CombatLog\DataExtractors\SpellDataCollectors\SpellDungeonAssignmentCollector;
use App\Service\CombatLog\DataExtractors\SpellDataCollectors\SpellPropertyObservationCollector;
use App\Service\CombatLog\DataExtractors\SpellDataCollectors\SummonedNpcCollector;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class SpellDataExtractor implements DataExtractorInterface
{
    /** @var Collection<int, SpellModel> */
    private readonly Collection $allSpells;

    private readonly SummonedNpcCollector $summonedNpcCollector;

    private readonly SpellCreationCollector $spellCreationCollector;

    private readonly SpellPropertyObservationCollector $propertyObservationCollector;

    private readonly SpellDungeonAssignmentCollector $dungeonAssignmentCollector;

    private readonly NpcSpellAssignmentCollector $npcSpellAssignmentCollector;

    /**
     * ORDER IS LOAD-BEARING: SpellCreated events must be written before PropertyChanged
     * events so the audit feed is chronologically correct.
     *
     * @var list<SpellDataCollectorInterface>
     */
    private readonly array $collectors;

    public function __construct()
    {
        $this->allSpells = SpellModel::with('spellDungeons')->get()->keyBy('id');

        $log = App::make(SpellDataExtractorLoggingInterface::class);
        /** @var SpellDataExtractorLoggingInterface $log */

        $this->summonedNpcCollector         = new SummonedNpcCollector($log);
        $this->spellCreationCollector       = new SpellCreationCollector($this->allSpells, $log);
        $this->propertyObservationCollector = new SpellPropertyObservationCollector($this->allSpells);
        $this->dungeonAssignmentCollector   = new SpellDungeonAssignmentCollector($this->allSpells, $log);
        $this->npcSpellAssignmentCollector  = new NpcSpellAssignmentCollector($this->allSpells, $log);

        // ORDER IS LOAD-BEARING: SpellCreated events must be written before PropertyChanged
        // events so the audit feed is chronologically correct.
        $this->collectors = [
            $this->summonedNpcCollector,
            $this->spellCreationCollector,
            $this->propertyObservationCollector,
            $this->dungeonAssignmentCollector,
            $this->npcSpellAssignmentCollector,
        ];
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        foreach ($this->collectors as $collector) {
            $collector->beforeCollect($combatLogFilePath);
        }
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
        if (!($parsedEvent instanceof CombatLogEvent)) {
            // Don't log anything because that'd just spam the hell out of it
            return;
        }

        $prefix = $parsedEvent->getPrefix();
        if (!($prefix instanceof Spell)) {
            return;
        }

        // Track summoned NPCs; skip further processing for summon events themselves
        if ($this->summonedNpcCollector->processSummon($parsedEvent)) {
            return;
        }

        $suffix      = $parsedEvent->getSuffix();
        $sourceGuid  = $parsedEvent->getGenericData()->getSourceGuid();
        $sourceIsNpc = $sourceGuid instanceof Creature &&
            // Only actual creatures - not pets
            $sourceGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE;

        $destGuid  = $parsedEvent->getGenericData()->getDestGuid();
        $destIsNpc = $destGuid instanceof Creature &&
            // Only actual creatures - not pets
            $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE;

        // Npcs can cast buffs on one another, and it'll count for a spell that they cast
        // Some player spells cause NPCs to cast spells on other NPCs, but those will be debuffs
        // (such as Death Knight Blinding Sleet), we don't want to attribute that to the NPC
        // 8/2/2024 16:24:18.477-4  SPELL_AURA_APPLIED,Creature-0-2085-2290-22744-164921-00012D4051,"Drust Harvester",0xa48,0x0,Creature-0-2085-2290-22744-164921-00012D4051,"Drust Harvester",0xa48,0x0,317898,"Blinding Sleet",0x10,DEBUFF
        if ($sourceIsNpc &&
            // Ignore summoned NPCs
            !$this->summonedNpcCollector->isSummoned($sourceGuid->getId()) &&
            // If destination is an NPC, and it's a buff, or if the target was a player
            (($destIsNpc && $suffix instanceof AuraAppliedInterface && $suffix->getAuraType() === AuraBase::AURA_TYPE_BUFF) ||
                $destGuid instanceof Player)) {
            // 8/2/2024 16:37:04.342-4  SPELL_AURA_BROKEN_SPELL,Creature-0-2085-2290-22770-171772-00002D40C5,"Mistveil Defender",0xa48,0x0,Player-4184-005B8B04,"Gulagcool-TheseGoToEleven-TR",0x512,0x0,1784,"Stealth",0x1,457129,"Deathstalker's Mark",1,DEBUFF
            // If the NPC broke an aura - that's not the NPC casting "Stealth" on a player - no it broke it out of it,
            // so don't assign that spell to this NPC
            if (!($suffix instanceof AuraBrokenSpell) && !($suffix instanceof AuraBroken)) {
                $this->spellCreationCollector->ensureSpellExists($result, $prefix);
                $this->propertyObservationCollector->collectFromEvent($parsedEvent, $prefix);
                $this->dungeonAssignmentCollector->collect($result, $currentDungeon, $parsedEvent, $prefix);
                $this->npcSpellAssignmentCollector->collect($result, $currentDungeon, $parsedEvent, $sourceGuid, $prefix);
            }
        }

        // Track interrupted NPC spells: when a player interrupts a creature, mark the interrupted spell as interruptible.
        if ($suffix instanceof Interrupt &&
            $sourceGuid instanceof Player &&
            $destGuid instanceof Creature &&
            $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE &&
            !$this->summonedNpcCollector->isSummoned($destGuid->getId())) {
            $this->spellCreationCollector->ensureInterruptSpellExists($result, $suffix);
            $this->propertyObservationCollector->collectInterrupt($suffix->getExtraSpellId());
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        foreach ($this->collectors as $collector) {
            $collector->afterCollect($result, $combatLogFilePath);
        }
    }
}
