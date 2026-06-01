<?php

namespace App\Service\CombatLog\DataExtractors\SpellDataCollectors;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class SummonedNpcCollector implements SpellDataCollectorInterface
{
    /** @var Collection<int, int> */
    private Collection $summonedNpcs;

    public function __construct(
        private readonly SpellDataExtractorLoggingInterface $log,
    ) {
        $this->summonedNpcs = collect();
    }

    public function beforeCollect(string $combatLogFilePath): void
    {
    }

    /**
     * If this event is a Summon, records the summoned NPC ID and returns true.
     * Returns false for all other event types.
     */
    public function processSummon(CombatLogEvent $event): bool
    {
        if (!($event->getSuffix() instanceof Summon)) {
            return false;
        }

        $guid  = $event->getGenericData()->getDestGuid();
        $npcId = $guid instanceof Creature ? $guid->getId() : null;

        if ($npcId !== null && $this->summonedNpcs->search($npcId) === false) {
            $this->summonedNpcs->push($npcId);
            $this->log->isSummonedNpcNpcWasSummoned($npcId, $event->getGenericData()->getDestName());
        }

        return true;
    }

    public function isSummoned(int $npcId): bool
    {
        return $this->summonedNpcs->search($npcId) !== false;
    }

    public function afterCollect(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->summonedNpcs = collect();
    }
}
