<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Characteristic;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Service\CombatLog\DataExtractors\Logging\NpcCharacteristicDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class NpcCharacteristicDataExtractor implements DataExtractorInterface
{
    /**
     * Maps spell IDs (as observed on NPC targets in combat logs) to Characteristic keys.
     * When any of these spells is applied to an NPC via SPELL_AURA_APPLIED, the corresponding
     * characteristic is added to that NPC.
     */
    public const SPELL_CHARACTERISTIC_MAP = [
        // TAUNT
        355    => Characteristic::CHARACTERISTIC_TAUNT,
        56222  => Characteristic::CHARACTERISTIC_TAUNT,
        185245 => Characteristic::CHARACTERISTIC_TAUNT,
        6795   => Characteristic::CHARACTERISTIC_TAUNT,
        2649   => Characteristic::CHARACTERISTIC_TAUNT,
        115546 => Characteristic::CHARACTERISTIC_TAUNT,
        62124  => Characteristic::CHARACTERISTIC_TAUNT,
        // INCAPACITATE
        20066  => Characteristic::CHARACTERISTIC_INCAPACITATE,
        217832 => Characteristic::CHARACTERISTIC_INCAPACITATE,
        1776   => Characteristic::CHARACTERISTIC_INCAPACITATE,
        187650 => Characteristic::CHARACTERISTIC_INCAPACITATE,
        115078 => Characteristic::CHARACTERISTIC_INCAPACITATE,
        // SUBJUGATE_DEMON
        1098 => Characteristic::CHARACTERISTIC_SUBJUGATE_DEMON,
        // CONTROL_UNDEAD
        111673 => Characteristic::CHARACTERISTIC_CONTROL_UNDEAD,
        // SILENCE
        47476  => Characteristic::CHARACTERISTIC_SILENCE,
        78675  => Characteristic::CHARACTERISTIC_SILENCE,
        248920 => Characteristic::CHARACTERISTIC_SILENCE,
        31935  => Characteristic::CHARACTERISTIC_SILENCE,
        15487  => Characteristic::CHARACTERISTIC_SILENCE,
        703    => Characteristic::CHARACTERISTIC_SILENCE,
        30108  => Characteristic::CHARACTERISTIC_SILENCE,
        // KNOCK
        132469 => Characteristic::CHARACTERISTIC_KNOCK,
        375058 => Characteristic::CHARACTERISTIC_KNOCK,
        51490  => Characteristic::CHARACTERISTIC_KNOCK,
        115770 => Characteristic::CHARACTERISTIC_KNOCK,
        // GRIP
        49576  => Characteristic::CHARACTERISTIC_GRIP,
        108199 => Characteristic::CHARACTERISTIC_GRIP,
        // SHACKLE_UNDEAD
        40135 => Characteristic::CHARACTERISTIC_SHACKLE_UNDEAD,
        // MIND_CONTROL
        605 => Characteristic::CHARACTERISTIC_MIND_CONTROL,
        // POLYMORPH
        118    => Characteristic::CHARACTERISTIC_POLYMORPH,
        28272  => Characteristic::CHARACTERISTIC_POLYMORPH,
        161353 => Characteristic::CHARACTERISTIC_POLYMORPH,
        61305  => Characteristic::CHARACTERISTIC_POLYMORPH,
        161354 => Characteristic::CHARACTERISTIC_POLYMORPH,
        61721  => Characteristic::CHARACTERISTIC_POLYMORPH,
        126819 => Characteristic::CHARACTERISTIC_POLYMORPH,
        28271  => Characteristic::CHARACTERISTIC_POLYMORPH,
        277792 => Characteristic::CHARACTERISTIC_POLYMORPH,
        277787 => Characteristic::CHARACTERISTIC_POLYMORPH,
        391622 => Characteristic::CHARACTERISTIC_POLYMORPH,
        460392 => Characteristic::CHARACTERISTIC_POLYMORPH,
        // ROOT
        339    => Characteristic::CHARACTERISTIC_ROOT,
        102359 => Characteristic::CHARACTERISTIC_ROOT,
        145532 => Characteristic::CHARACTERISTIC_ROOT,
        116095 => Characteristic::CHARACTERISTIC_ROOT,
        103828 => Characteristic::CHARACTERISTIC_ROOT,
        // FEAR
        5782  => Characteristic::CHARACTERISTIC_FEAR,
        5484  => Characteristic::CHARACTERISTIC_FEAR,
        85966 => Characteristic::CHARACTERISTIC_FEAR,
        5246  => Characteristic::CHARACTERISTIC_FEAR,
        // BANISH
        71298 => Characteristic::CHARACTERISTIC_BANISH,
        // DISORIENT
        213691 => Characteristic::CHARACTERISTIC_DISORIENT,
        96447  => Characteristic::CHARACTERISTIC_DISORIENT,
        115750 => Characteristic::CHARACTERISTIC_DISORIENT,
        2094   => Characteristic::CHARACTERISTIC_DISORIENT,
        // REPENTANCE
        // 20066 is already mapped to INCAPACITATE above; REPENTANCE uses the same spell in some contexts
        // IMPRISON
        // 217832 is already mapped to INCAPACITATE above
        // SAP
        6770 => Characteristic::CHARACTERISTIC_SAP,
        // STUN
        179057 => Characteristic::CHARACTERISTIC_STUN,
        191427 => Characteristic::CHARACTERISTIC_STUN,
        211881 => Characteristic::CHARACTERISTIC_STUN,
        221562 => Characteristic::CHARACTERISTIC_STUN,
        47481  => Characteristic::CHARACTERISTIC_STUN,
        5211   => Characteristic::CHARACTERISTIC_STUN,
        371032 => Characteristic::CHARACTERISTIC_STUN,
        19577  => Characteristic::CHARACTERISTIC_STUN,
        119381 => Characteristic::CHARACTERISTIC_STUN,
        853    => Characteristic::CHARACTERISTIC_STUN,
        64044  => Characteristic::CHARACTERISTIC_STUN,
        408    => Characteristic::CHARACTERISTIC_STUN,
        1833   => Characteristic::CHARACTERISTIC_STUN,
        192058 => Characteristic::CHARACTERISTIC_STUN,
        51533  => Characteristic::CHARACTERISTIC_STUN,
        30283  => Characteristic::CHARACTERISTIC_STUN,
        89766  => Characteristic::CHARACTERISTIC_STUN,
        107570 => Characteristic::CHARACTERISTIC_STUN,
        46968  => Characteristic::CHARACTERISTIC_STUN,
        385952 => Characteristic::CHARACTERISTIC_STUN,
        // SLOW
        1715   => Characteristic::CHARACTERISTIC_SLOW,
        31589  => Characteristic::CHARACTERISTIC_SLOW,
        45524  => Characteristic::CHARACTERISTIC_SLOW,
        58180  => Characteristic::CHARACTERISTIC_SLOW,
        5116   => Characteristic::CHARACTERISTIC_SLOW,
        32908  => Characteristic::CHARACTERISTIC_SLOW,
        128951 => Characteristic::CHARACTERISTIC_SLOW,
        18100  => Characteristic::CHARACTERISTIC_SLOW,
        116    => Characteristic::CHARACTERISTIC_SLOW,
        12611  => Characteristic::CHARACTERISTIC_SLOW,
        44614  => Characteristic::CHARACTERISTIC_SLOW,
        35507  => Characteristic::CHARACTERISTIC_SLOW,
        26679  => Characteristic::CHARACTERISTIC_SLOW,
        3408   => Characteristic::CHARACTERISTIC_SLOW,
        196840 => Characteristic::CHARACTERISTIC_SLOW,
        147732 => Characteristic::CHARACTERISTIC_SLOW,
        // SLEEP_WALK
        360806 => Characteristic::CHARACTERISTIC_SLEEP_WALK,
        // SCARE_BEAST
        1513 => Characteristic::CHARACTERISTIC_SCARE_BEAST,
        // HIBERNATE
        2637 => Characteristic::CHARACTERISTIC_HIBERNATE,
        // TURN_EVIL
        10326 => Characteristic::CHARACTERISTIC_TURN_EVIL,
        // MIND_SOOTHE
        453 => Characteristic::CHARACTERISTIC_MIND_SOOTHE,
    ];

    /** @var Collection<int, Npc|false> */
    private Collection $npcCache;

    /** @var Collection<string> */
    private Collection $addedCharacteristics;

    private readonly NpcCharacteristicDataExtractorLoggingInterface $log;

    public function __construct()
    {
        $this->npcCache             = collect();
        $this->addedCharacteristics = collect();

        $log = App::make(NpcCharacteristicDataExtractorLoggingInterface::class);
        /** @var NpcCharacteristicDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
        if (!($parsedEvent instanceof CombatLogEvent)) {
            return;
        }

        $prefix = $parsedEvent->getPrefix();
        if (!($prefix instanceof Spell)) {
            return;
        }

        if (!($parsedEvent->getSuffix() instanceof AuraAppliedInterface)) {
            return;
        }

        $destGuid = $parsedEvent->getGenericData()->getDestGuid();
        if (!($destGuid instanceof Creature) ||
            $destGuid->getUnitType() !== Creature::CREATURE_UNIT_TYPE_CREATURE) {
            return;
        }

        $spellId = $prefix->getSpellId();
        if (!isset(self::SPELL_CHARACTERISTIC_MAP[$spellId])) {
            return;
        }

        $characteristicKey = self::SPELL_CHARACTERISTIC_MAP[$spellId];
        $characteristicId  = Characteristic::ALL[$characteristicKey];
        $npcId             = $destGuid->getId();

        /** @var Npc|null|false $npc */
        $npc = $this->npcCache->get($npcId);
        if ($npc === false) {
            return;
        }

        if ($npc === null) {
            $npc = Npc::with('npcCharacteristics')->find($npcId);
            $this->npcCache->put($npcId, $npc ?? false);
        }

        if (!($npc instanceof Npc)) {
            $this->log->extractDataNpcNotFound($npcId);

            return;
        }

        $dedupKey = sprintf('%d-%d', $npcId, $characteristicId);
        if ($this->addedCharacteristics->contains($dedupKey)) {
            $this->log->extractDataCharacteristicAlreadyAssigned($npcId, $characteristicKey);

            return;
        }

        $alreadyHasCharacteristic = $npc->npcCharacteristics
            ->contains('characteristic_id', $characteristicId);

        if ($alreadyHasCharacteristic) {
            $this->log->extractDataCharacteristicAlreadyAssigned($npcId, $characteristicKey);

            return;
        }

        NpcCharacteristic::create([
            'npc_id'            => $npcId,
            'characteristic_id' => $characteristicId,
        ]);

        $npc->unsetRelation('npcCharacteristics')->load('npcCharacteristics');
        $this->addedCharacteristics->push($dedupKey);

        $result->createdNpcCharacteristic();

        $this->log->extractDataAssignedCharacteristicToNpc($npcId, $characteristicKey, $parsedEvent->getRawEvent());
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->npcCache             = collect();
        $this->addedCharacteristics = collect();
    }
}
