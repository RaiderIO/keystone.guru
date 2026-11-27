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
    /** @var Collection<int, BaseResultEvent> */
    protected Collection $resultEvents;

    /** @var Collection<int, CombatLogParserInterface> */
    private readonly Collection $filters;

    public function __construct()
    {
        $this->resultEvents = collect();
        $this->filters      = collect();
    }

    protected function addFilter(CombatLogParserInterface $combatLogParser): void
    {
        $this->filters->push($combatLogParser);
    }

    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        $result = false;

        foreach ($this->filters as $filter) {
            $result = $filter->parse($combatLogEvent, $lineNr) || $result;
        }

        return $result;
    }

    /**
     * @return Collection<int, BaseResultEvent>
     */
    public function getResultEvents(): Collection
    {
        return self::sortResultEvents($this->resultEvents);
    }

    /**
     * Orders result events the way getResultEvents() does, so result events from elsewhere can be merged into a
     * filter's own without changing where any of them lands.
     *
     * @param  Collection<int, BaseResultEvent> $resultEvents
     * @return Collection<int, BaseResultEvent>
     */
    public static function sortResultEvents(Collection $resultEvents): Collection
    {
        return $resultEvents->sortBy(static function (BaseResultEvent $baseResultEvent) {
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
