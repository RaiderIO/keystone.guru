<?php

namespace App\Service\CombatLogEvent;

use App\Models\CombatLog\CombatLogEvent;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use Codeart\OpensearchLaravel\Search\Query;
use Codeart\OpensearchLaravel\Search\SearchQueries\BoolQuery;
use Codeart\OpensearchLaravel\Search\SearchQueries\Must;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;
use Illuminate\Support\Collection;

class CombatLogEventService implements CombatLogEventServiceInterface
{
    public function __construct(
        private CombatLogEventServiceLoggingInterface $log
    ) {
    }

    /**
     * @return Collection<CombatLogEvent>
     */
    public function getCombatLogEvents(CombatLogEventFilter $filters): Collection
    {
        $result = collect();

        try {
            $this->log->getCombatLogEventsStart($filters->toArray());

            $result = CombatLogEvent::opensearch()
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

            $result = CombatLogEvent::openSearchResultToModels($result);
        } catch (\Exception $e) {
            $this->log->getCombatLogEventsException($e);
        } finally {
            $this->log->getCombatLogEventsEnd();
        }

        return $result;
    }
}
