<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use Illuminate\Support\Collection;

abstract class BaseCombatLogFilter implements CombatLogParserInterface
{
    /** @var Collection|BaseResultEvent[] */
    protected Collection $resultEvents;

    /** @var Collection|CombatLogParserInterface[] */
    private Collection $filters;

    public function __construct()
    {
        $this->resultEvents = collect();
        $this->filters      = collect();

    }

    /**
     * @return void
     */
    protected function addFilter(CombatLogParserInterface $combatLogParser)
    {
        $this->filters->push($combatLogParser);
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @param int       $lineNr
     *
     * @return bool
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        $result = false;

        foreach ($this->filters as $filter) {
            $result = $filter->parse($combatLogEvent, $lineNr) || $result;
        }

        return $result;
    }

    /**
     * @return Collection|BaseResultEvent[]
     */
    public function getResultEvents(): Collection
    {
        return $this->resultEvents->sortBy(function (BaseResultEvent $baseResultEvent) {
            // Add some CONSISTENT (not necessarily accurate) numbers so that events with the same timestamp are sorted
            // consistently instead of "randomly" causing all kinds of issues
            $addition  = 0;
            $baseEvent = $baseResultEvent->getBaseEvent();
            if ($baseEvent instanceof AdvancedCombatLogEvent) {
                $guid = $baseEvent->getAdvancedData()->getInfoGuid();
                if ($guid instanceof Creature) {
                    // Ensure that the addition doesn't go higher than 1
                    $addition = min(0.99999, ($guid->getId() + hexdec($guid->getSpawnUID())) / 10000000000000);
                }
            }

            return $baseResultEvent->getBaseEvent()->getTimestamp()->getTimestampMs() + $addition;
        });
    }
}
