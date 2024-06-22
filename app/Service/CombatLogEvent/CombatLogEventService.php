<?php

namespace App\Service\CombatLogEvent;

use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Season;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use App\Service\CombatLogEvent\Models\CombatLogEventGridAggregationResult;
use App\Service\CombatLogEvent\Models\CombatLogEventSearchResult;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Carbon\CarbonPeriod;
use Codeart\OpensearchLaravel\Aggregations\Aggregation;
use Codeart\OpensearchLaravel\Aggregations\Types\Cardinality;
use Codeart\OpensearchLaravel\Aggregations\Types\Maximum;
use Codeart\OpensearchLaravel\Aggregations\Types\Minimum;
use Codeart\OpensearchLaravel\Aggregations\Types\ScriptedMetric;
use Codeart\OpensearchLaravel\Aggregations\Types\Terms;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
                ->search($filters->toOpensearchQuery())
                ->get();

            $combatLogEvents = CombatLogEvent::openSearchResultToModels($combatLogEvents);
        } catch (\Exception $e) {
            $this->log->getCombatLogEventsException($e);
        } finally {
            $this->log->getCombatLogEventsEnd();
        }

        return new CombatLogEventSearchResult($this->coordinatesService, $filters, $combatLogEvents, 10);
    }

    public function getGridAggregation(CombatLogEventFilter $filters): ?CombatLogEventGridAggregationResult
    {
        // <editor-fold desc="OS Query" defaultState="collapsed">
//        {
//            "size": 0,
//            "query": {
//                    "bool": {
//                        "must": [{
//                            "match": {
//                                "ui_map_id": 2082
//                            }
//                        }, {
//                            "match": {
//                                "challenge_mode_id": 406
//                            }
//                        }, {
//                            "range": {
//                                "level": {
//                                    "gte": 13,
//                                    "lte": 20
//                                }
//                            }
//                        }, {
//                            "bool": {
//                                "should": [{
//                                    "bool": {
//                                        "filter": [{
//                                            "term": {
//                                                "affix_id": 10
//                                                    }
//                                                }, {
//                                            "term": {
//                                                "affix_id": 136
//                                                    }
//                                                }, {
//                                            "term": {
//                                                "affix_id": 8
//                                                    }
//                                                }
//                                            ]
//                                        }
//                                    }, {
//                                    "bool": {
//                                        "must": [{
//                                            "term": {
//                                                "affix_id": 9
//                                                    }
//                                                }, {
//                                            "term": {
//                                                "affix_id": 134
//                                                    }
//                                                }, {
//                                            "term": {
//                                                "affix_id": 11
//                                                    }
//                                                }
//                                            ]
//                                        }
//                                    }
//                                ]
//                            }
//                        }
//                    ]
//                }
//            },
//            "aggs": {
//                    "run_count": {
//                        "cardinality": {
//                            "field": "run_id"
//                    }
//                }
//            }
//        }
        // </editor-fold>

        $result = null;

        try {
            $this->log->getGeotileGridAggregationStart($filters->toArray());

            $gridResult = [];

            // Repeat this query for each floor
            foreach ($filters->getDungeon()->floors()->where('facade', false)->get() as $floor) {
                /** @var Floor $floor */
                $filterQuery = $filters->toOpensearchQuery([
                    MatchOne::make('ui_map_id', $floor->ui_map_id),
                ]);


//                dd(json_encode(CombatLogEvent::opensearch()
//                    ->builder()
//                    ->search($filterQuery)
//                    ->toArray()));

                $searchResult = CombatLogEvent::opensearch()
                    ->builder()
                    ->search($filterQuery)
                    ->aggregations([
                        Aggregation::make(
                            name: 'heatmap',
                            aggregationType: ScriptedMetric::make(
                                mapScript: strtr('
                                   int sizeX = :sizeX;
                                   int sizeY = :sizeY;

                                   float minX = :minXf;
                                   float minY = :minYf;
                                   float maxX = :maxXf;
                                   float maxY = :maxYf;

                                   float width = maxX - minX;
                                   float height = maxY - minY;
                                   float stepX = width / sizeX;
                                   float stepY = height / sizeY;

                                   // @TODO #2422
                                   // if( (doc[\'pos_x\'].value < minX) || (doc[\'pos_x\'].value > maxX) ||
                                   //     (doc[\'pos_y\'].value < minY) || (doc[\'pos_y\'].value > maxY)) {
                                   //   return;
                                   // }

                                   int gx = ((doc[\'pos_x\'].value - minX) / width * sizeX).intValue();
                                   int gy = ((doc[\'pos_y\'].value - minY) / height * sizeY).intValue();

                                   // @TODO #2422
                                   if( gx < 0 || gx >= sizeX || gy < 0 || gy >= sizeY ) {
                                     return;
                                   }
                                   String key = ((gx * stepX) + minX).toString() + \',\' + ((gy * stepY) + minY).toString();
                                   if (state.map.containsKey(key)) {
                                     state.map[key] += 1;
                                   } else {
                                     state.map[key] = 1;
                                   }
                                 ', [
                                     // @TODO #2419
                                    ':sizeX' => config('keystoneguru.heatmap.service.data.raw.sizeX'),
                                    ':sizeY' => config('keystoneguru.heatmap.service.data.raw.sizeY'),
                                    ':minX'  => $floor->ingame_min_x,
                                    ':minY'  => $floor->ingame_min_y,
                                    ':maxX'  => $floor->ingame_max_x,
                                    ':maxY'  => $floor->ingame_max_y,
                                ]),
                                combineScript: 'return state.map',
                                reduceScript: '
                                   Map result = [:];
                                   for (state in states) {
                                     for (entry in state.entrySet()) {
                                       if (result.containsKey(entry.getKey())) {
                                         result[entry.getKey()] += entry.getValue();
                                       } else {
                                         result[entry.getKey()] = entry.getValue();
                                       }
                                     }
                                   }
                                   return result;
                                 ',
                                initScript: 'state.map = [:]'
                            )
                        ),
                    ])
                    ->size(0)
                    ->get();

                $gridResult[$floor->id] = $searchResult['aggregations']['heatmap']['value'];
            }

            // Request the amount of affected runs
            $runCount = $this->getRunCount($filters);

            $result = new CombatLogEventGridAggregationResult(
                $this->coordinatesService,
                $filters,
                $gridResult,
                $runCount
            );
        } catch (\Exception $e) {
            $this->log->getGeotileGridAggregationException($e);
        } finally {
            $this->log->getGeotileGridAggregationEnd();
        }

        return $result;
    }

    public function getRunCount(CombatLogEventFilter $filters): int
    {
        // <editor-fold desc="OS Query" defaultState="collapsed">
//        POST /combat_log_events/_search
//        {
//            "size": 0,
//            "aggs": {
//            "run_count": {
//                "cardinality": {
//                    "field": "run_id"
//                    }
//                }
//            }
//        }
        // </editor-fold>

        $result = 0;
        try {
            $runCountSearchResult = CombatLogEvent::opensearch()
                ->builder()
                ->search($filters->toOpensearchQuery())
                ->aggregations([
                    Aggregation::make(
                        name: "run_count",
                        aggregationType: Cardinality::make('run_id')
                    ),
                ])
                ->size(0)
                ->get();

            $result = $runCountSearchResult['aggregations']['run_count']['value'];
            $this->log->getRunCountResult($result);
        } catch (\Exception $e) {
            $this->log->getRunCountException($e);
        }

        return $result;
    }

    /**
     * @return Collection<int>
     */
    public function getRunCountPerDungeon(): Collection
    {
        $result = collect();
        try {
            // <editor-fold desc="OS Query" defaultState="collapsed">
//            POST /combat_log_events/_search
//            {
//                "size": 0,
//                "aggs": {
//                "dungeon": {
//                    "terms": {
//                        "field": "challenge_mode_id",
//                            "size": 10000
//                        },
//                        "aggs": {
//                        "run_count": {
//                            "cardinality": {
//                                "field": "run_id"
//                                }
//                            }
//                        }
//                    }
//                }
//            }
            // </editor-fold>

            /** @var Collection<Dungeon> $allDungeons */
            $allDungeons = Dungeon::whereNotNull('challenge_mode_id')
                ->get()
                ->keyBy('challenge_mode_id');

            $searchResult = CombatLogEvent::opensearch()
                ->builder()
                ->aggregations([
                    Aggregation::make(
                        name: "dungeons",
                        aggregationType: Terms::make('challenge_mode_id', 10000),
                        aggregation: Aggregation::make(
                            name: "run_count",
                            aggregationType: Cardinality::make('run_id')
                        ),
                    ),
                ])
                ->size(0)
                ->get();

            foreach ($searchResult['aggregations']['dungeons']['buckets'] as $bucket) {
                $challengeModeId = $bucket['key'];
                if ($allDungeons->has($challengeModeId)) {
                    /** @var Dungeon $dungeon */
                    $dungeon = $allDungeons->get($challengeModeId);

                    $result->put($dungeon->id, $bucket['run_count']['value']);
                }
            }

            $this->log->getRunCountPerDungeonResult($result->toArray());
        } catch (\Exception $e) {
            $this->log->getRunCountPerDungeonException($e);
        }

        return $result;
    }

    public function getAvailableDateRange(CombatLogEventFilter $filters): ?CarbonPeriod
    {
        // <editor-fold desc="OS Query" defaultState="collapsed">
//        POST/combat_log_events/_search
//        {
//                "query": {
//                    "bool": {
//                        "must": [{
//                            "match": {
//                                "challenge_mode_id": 399
//                            }
//                        }
//                    ]
//                }
//            },
//            "aggs": {
//                    "min_date": {
//                        "min": {
//                            "field": "start"
//                    }
//                },
//                "max_date": {
//                        "max": {
//                            "field": "start"
//                    }
//                }
//            }
//        }
        // </editor-fold>

        $result = null;
        try {
            $runCountSearchResult = CombatLogEvent::opensearch()
                ->builder()
                ->search($filters->toOpensearchQuery())
                ->aggregations([
                    Aggregation::make(
                        name: "min_date",
                        aggregationType: Minimum::make('start')
                    ),
                    Aggregation::make(
                        name: "max_date",
                        aggregationType: Maximum::make('start')
                    ),
                ])
                ->size(0)
                ->get();

            $result = new CarbonPeriod(
                Carbon::createFromTimestamp((int)$runCountSearchResult['aggregations']['min_date']['value_as_string'])->toDate(),
                Carbon::createFromTimestamp((int)$runCountSearchResult['aggregations']['max_date']['value_as_string'])->toDate()
            );
            $this->log->getAvailableDateRangeResult($result->start->getTimestamp(), $result->end->getTimestamp());
        } catch (\Exception $e) {
            $this->log->getAvailableDateRangeException($e);
        }

        return $result;
    }

    public function generateCombatLogEvents(Season $season, string $type, int $count = 1, int $eventsPerRun = 5): Collection
    {
        $combatLogEventAttributes = collect();

        // 24 weeks, 24 hours
        $now               = Carbon::now();
        $seasonLengthHours = 24 * 7 * 24;

        $runId         = null;
        $runStart      = null;
        $runDurationMs = null;
        $affixGroup    = null;
        $level         = null;
        for ($i = 0; $i < $count; $i++) {
            /** @var Dungeon $dungeon */
            $dungeon = $season->dungeons->random();
            // Cannot load directly on the relation - need to fix
            $dungeon = $dungeon->load('currentMappingVersion');

            if ($i % $eventsPerRun === 0) {
                $dungeon->currentMappingVersion->load('enemies');

                $runId         = sprintf('Generated run ID %d', rand(1000, 1000000));
                $runStart      = $season->start->copy()->addHours(rand(0, $seasonLengthHours));
                $runDurationMs = rand(600, $dungeon->currentMappingVersion->timer_max_seconds) * 1000;

                /** @var AffixGroup $affixGroup */
                $affixGroup = $season->affixGroups->random();

                $level = rand($season->key_level_min, $season->key_level_max);
            }

            /** @var Enemy $enemy */
            $enemy = $dungeon->currentMappingVersion->enemies->random();
            // Not ideal but I can't get the relation to load properly whenever the run is generated
            $enemy->load(['floor']);
            // We place all events exactly on a random enemy in the dungeon so that it appears something happened at this enemy,
            // instead of randomly somewhere on the map, which may be way out of dungeon bounds.
            $enemyIngameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($enemy->getLatLng());

            $combatLogEventAttributes->push([
                '@timestamp'        => $now->unix(),
                'run_id'            => $runId,
                'challenge_mode_id' => $dungeon->challenge_mode_id,
                'level'             => $level,
                'affix_ids'         => json_encode($affixGroup->affixes->pluck('affix_id')->toArray()),
                'ui_map_id'         => $enemyIngameXY->getFloor()->ui_map_id,
                'pos_x'             => round($enemyIngameXY->getX(), 2),
                'pos_y'             => round($enemyIngameXY->getY(), 2),
                'event_type'        => $type,
                'start'             => $runStart->toDateTimeString(),
                'end'               => $runStart->addMilliseconds($runDurationMs)->toDateTimeString(),
                'duration_ms'       => $runDurationMs,
                'success'           => $dungeon->currentMappingVersion->timer_max_seconds > ($runDurationMs / 1000),
            ]);
        }

        CombatLogEvent::insert($combatLogEventAttributes->toArray());

        $result = CombatLogEvent::orderByDesc('id')->take($count)->get();

        // Insert into OS
        CombatLogEvent::opensearch()
            ->documents()
            ->create($combatLogEventAttributes->pluck('id')->toArray());

        return $result;
    }
}
