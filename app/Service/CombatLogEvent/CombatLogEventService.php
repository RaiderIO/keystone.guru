<?php

namespace App\Service\CombatLogEvent;

use App\Models\CombatLog\CombatLogEvent;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use App\Service\CombatLogEvent\Models\CombatLogEventSearchResult;
use Codeart\OpensearchLaravel\Search\Query;
use Codeart\OpensearchLaravel\Search\SearchQueries\BoolQuery;
use Codeart\OpensearchLaravel\Search\SearchQueries\Must;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;

class CombatLogEventService implements CombatLogEventServiceInterface
{
    public function __construct(
        private CombatLogEventServiceLoggingInterface $log
    ) {
    }

    /**
     * @return CombatLogEventSearchResult
     */
    public function getCombatLogEvents(CombatLogEventFilter $filters): CombatLogEventSearchResult
    {
        $combatLogEvents = collect();

        try {
            $this->log->getCombatLogEventsStart($filters->toArray());

            $combatLogEvents = CombatLogEvent::opensearch()
                ->builder()
                ->search([
                    Query::make([
                        BoolQuery::make([
                            Must::make([
                                MatchOne::make('challenge_mode_id', $filters->getDungeon()->challenge_mode_id),
                            ]),
                        ]),
                    ]),
                ])
                ->get();

            $combatLogEvents = CombatLogEvent::openSearchResultToModels($combatLogEvents);
        } catch (\Exception $e) {
            $this->log->getCombatLogEventsException($e);
        } finally {
            $this->log->getCombatLogEventsEnd();
        }

        return new CombatLogEventSearchResult($combatLogEvents, 10);
    }
}
