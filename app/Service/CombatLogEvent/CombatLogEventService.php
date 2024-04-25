<?php

namespace App\Service\CombatLogEvent;

use App\Models\CombatLog\CombatLogEvent;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use App\Service\CombatLogEvent\Models\CombatLogEventGeotileGridResult;
use App\Service\CombatLogEvent\Models\CombatLogEventSearchResult;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Codeart\OpensearchLaravel\Aggregations\Aggregation;
use Codeart\OpensearchLaravel\Aggregations\Types\GeotileGrid;
use Codeart\OpensearchLaravel\Aggregations\Types\Terms;
use Codeart\OpensearchLaravel\Search\Query;
use Codeart\OpensearchLaravel\Search\SearchQueries\BoolQuery;
use Codeart\OpensearchLaravel\Search\SearchQueries\Must;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;

class CombatLogEventService implements CombatLogEventServiceInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface           $coordinatesService,
        private readonly CombatLogEventServiceLoggingInterface $log
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

        return new CombatLogEventSearchResult($this->coordinatesService, $filters, $combatLogEvents, 10);
    }

    public function getGeotileGridAggregation(CombatLogEventFilter $filters): ?CombatLogEventGeotileGridResult
    {
        $result = null;

        try {
            $this->log->getGeotileGridAggregationStart($filters->toArray());

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
                ->aggregations([
                    Aggregation::make(
                        name: "per-floor",
                        aggregationType: Terms::make(field: 'ui_map_id'),
                        aggregation: Aggregation::make(
                            name: 'grid',
                            aggregationType: GeotileGrid::make('pos', 7)
                        )),
                ])
                ->size(0)
                ->get();

            $result = new CombatLogEventGeotileGridResult(
                $this->coordinatesService,
                $filters,
                $combatLogEvents['aggregations']['per-floor']['buckets'],
                10 // @TODO fix this
            );
        } catch (\Exception $e) {
            $this->log->getGeotileGridAggregationException($e);
        } finally {
            $this->log->getGeotileGridAggregationEnd();
        }

        return $result;
    }
}
