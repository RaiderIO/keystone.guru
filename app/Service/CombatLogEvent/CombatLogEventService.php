<?php /** @noinspection PhpClassCanBeReadonlyInspection */

namespace App\Service\CombatLogEvent;

use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\CombatLogEvent\Dtos\CombatLogEventSearchResult;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Carbon\CarbonPeriod;
use Codeart\OpensearchLaravel\Aggregations\Aggregation;
use Codeart\OpensearchLaravel\Aggregations\Types\Cardinality;
use Codeart\OpensearchLaravel\Aggregations\Types\Composite;
use Codeart\OpensearchLaravel\Aggregations\Types\Maximum;
use Codeart\OpensearchLaravel\Aggregations\Types\Minimum;
use Codeart\OpensearchLaravel\Aggregations\Types\Nested;
use Codeart\OpensearchLaravel\Aggregations\Types\Terms;
use Codeart\OpensearchLaravel\Exceptions\OpenSearchCreateException;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;
use Exception;
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
        } catch (Exception $e) {
            $this->log->getCombatLogEventsException($e);
        } finally {
            $this->log->getCombatLogEventsEnd();
        }

        return new CombatLogEventSearchResult($this->coordinatesService, $filters, $combatLogEvents, 10);
    }

    /**
     * @throws Exception
     */
    public function getGridAggregation(CombatLogEventFilter $filters): ?CombatLogEventGridAggregationResult
    {
        // <editor-fold desc="OS Query Player Position" defaultState="collapsed">
//        POST /combat_log_events/_search
//        {
//            "size": 0,
//          "query": {
//            "bool": {
//                "must": [
//                {
//                    "match": {
//                    "challenge_mode_id": 525
//                  }
//                }
//              ]
//            }
//          },
//          "aggs": {
//            "heatmap": {
//                "composite": {
//                    "size": 10000,
//                "sources": [
//                  {
//                      "pos_grid_x": {
//                      "terms": {
//                          "field": "pos_grid_x"
//                      }
//                    }
//                  },
//                  {
//                      "pos_grid_y": {
//                      "terms": {
//                          "field": "pos_grid_y"
//                      }
//                    }
//                  }
//                ]
//              }
//            }
//          }
//        }
        // </editor-fold>

        // <editor-fold desc="OS Query Enemy Position" defaultState="collapsed">
//        POST /combat_log_events/_search
//        {
//          "size": 0,
//          "query": {
//            "bool": {
//                "must": [
//                {
//                    "match": {
//                    "ui_map_id": 1491
//                  }
//                },
//                {
//                    "match": {
//                    "challenge_mode_id": 370
//                  }
//                },
//                {
//                    "match": {
//                    "event_type": "npc_death"
//                  }
//                }
//              ]
//            }
//          },
//          "aggs": {
//            "nested_heatmap": {
//                "nested": {
//                    "path": "context"
//              },
//              "aggs": {
//                    "heatmap": {
//                        "composite": {
//                            "size": 10000,
//                    "sources": [
//                      {
//                          "pos_grid_x": {
//                          "terms": { "field": "context.pos_enemy_grid_x" }
//                        }
//                      },
//                      {
//                          "pos_grid_y": {
//                          "terms": { "field": "context.pos_enemy_grid_y" }
//                        }
//                      }
//                    ]
//                  }
//                }
//              }
//            }
//          }
//        }
        // </editor-fold>

        $result = null;

        try {
            $this->log->getGeotileGridAggregationStart($filters->toArray());

            $gridResult = [];

            // #2641
            $dataType = $filters->getDataType();
            if ($filters->getEventType() === CombatLogEventEventType::PlayerDeath) {
                $dataType = CombatLogEventDataType::PlayerPosition;
            }

            // Repeat this query for each floor
            foreach ($filters->getDungeon()->floors()->where('facade', false)->get() as $floor) {
                /** @var Floor $floor */
                $filterQuery = $filters->toOpensearchQuery([
                    MatchOne::make('ui_map_id', $floor->ui_map_id),
                ]);

                $openSearchBuilder = CombatLogEvent::opensearch()
                    ->builder()
                    ->search($filterQuery)
                    ->size(0);

                $buckets = [];
                if ($dataType === CombatLogEventDataType::PlayerPosition) {
                    $openSearchBuilder->aggregations([
                        Aggregation::make(
                            name: 'heatmap',
                            aggregationType: Composite::make([
                                'pos_grid_x' => Terms::make('pos_grid_x', null),
                                'pos_grid_y' => Terms::make('pos_grid_y', null),
                            ], 10000),
                        ),
                    ]);

                    $searchResult = $openSearchBuilder->get();

                    $buckets = $searchResult['aggregations']['heatmap']['buckets'];
                } else if ($dataType === CombatLogEventDataType::EnemyPosition) {
                    $openSearchBuilder->aggregations([
                        Aggregation::make(
                            name: 'nested_heatmap',
                            aggregationType: Nested::make('context'),
                            aggregation: Aggregation::make(
                                name: 'heatmap',
                                aggregationType: Composite::make([
                                    'pos_grid_x' => Terms::make('context.pos_enemy_grid_x', null),
                                    'pos_grid_y' => Terms::make('context.pos_enemy_grid_y', null),
                                ], 10000),
                            ),
                        ),
                    ]);

                    $searchResult = $openSearchBuilder->get();

                    $buckets = $searchResult['aggregations']['nested_heatmap']['heatmap']['buckets'];
                }

                $gridResult[$floor->id] = array_combine(
                    array_map(fn($bucket) => sprintf('%s,%s', $bucket['key']['pos_grid_x'], $bucket['key']['pos_grid_y']), $buckets),
                    array_column($buckets, 'doc_count')
                );
            }

            // Request the amount of affected runs
            $runCount = $this->getRunCount($filters);

            $result = new CombatLogEventGridAggregationResult(
                $this->coordinatesService,
                $filters,
                $gridResult,
                $runCount
            );
        } catch (Exception $e) {
            $this->log->getGeotileGridAggregationException($e);

            throw $e;
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
//                "run_count": {
//                    "cardinality": {
//                        "field": "run_id"
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
        } catch (Exception $e) {
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
//              "size": 0,
//              "aggs": {
//                "dungeon": {
//                  "terms": {
//                    "field": "challenge_mode_id",
//                    "size": 10000
//                  },
//                  "aggs": {
//                    "run_count": {
//                      "cardinality": {
//                        "field": "run_id"
//                      }
//                    }
//                  }
//                }
//              }
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            $this->log->getAvailableDateRangeException($e);
        }

        return $result;
    }

    /**
     * @param Season                  $season
     * @param CombatLogEventEventType $type
     * @param int                     $runCount
     * @param int                     $eventsPerRun
     * @return Collection
     * @throws OpenSearchCreateException
     */
    public function generateCombatLogEvents(
        Season                  $season,
        CombatLogEventEventType $type,
        int                     $runCount = 1,
        int                     $eventsPerRun = 5,
        ?Dungeon                $dungeon = null
    ): Collection {
        // 24 weeks, 24 hours
        $now               = Carbon::now();
        $seasonLengthWeeks = 24;
        $seasonLengthHours = ($seasonLengthWeeks * 7) * 24;

        $result = true;

        for ($i = 0; $i < $runCount; $i++) {
            $combatLogEventAttributes = collect();

            /** @var Dungeon $dungeon */
            $dungeon = $dungeon ?? $season->dungeons->random();
            // Cannot load directly on the relation - need to fix
            $currentMappingVersion = $dungeon->getCurrentMappingVersion()
                ->load('enemies');

            $runId         = rand(1000, 100000000);
            $keystoneRunId = rand(1000, 100000000);
            $loggedRunId   = rand(1000, 100000000);
            $runStart      = $season->start->copy()->addHours(rand(0, $seasonLengthHours));
            $runPeriod     = $season->start_period + rand(0, $seasonLengthWeeks);
            // RaiderIO regions
            $regions       = array_values(GameServerRegion::ALL);
            $regionId      = match ($regions[array_rand($regions)]) {
                GameServerRegion::EUROPE => 3,
                GameServerRegion::AMERICAS => 2,
                GameServerRegion::CHINA => 6,
                GameServerRegion::KOREA => 4,
                GameServerRegion::TAIWAN => 5,
                default => 2, // US
            };
            $runDurationMs = rand(600, $currentMappingVersion->timer_max_seconds) * 1000;
            $timerFraction = $runDurationMs / ($currentMappingVersion->timer_max_seconds * 1000);

            $success = $currentMappingVersion->timer_max_seconds > ($runDurationMs / 1000);
            $start = $runStart->toDateTimeString();
            $end   = $runStart->addMilliseconds($runDurationMs)->toDateTimeString();

            /** @var AffixGroup $affixGroup */
            $affixGroup = $season->affixGroups->random();
            $affixIds = json_encode($affixGroup->affixes->pluck('affix_id')->toArray());

            $level            = rand($season->key_level_min, $season->key_level_max);
            $averageItemLevel = rand($season->item_level_min, $season->item_level_max);

            $dateTime   = $now->toDateTimeString();
            for ($j = 0; $j < $eventsPerRun; $j++) {
                /** @var Enemy $enemy */
                $enemy = $currentMappingVersion->enemies->random();
                // Not ideal but I can't get the relation to load properly whenever the run is generated
                $enemy->load(['floor']);

                // We place all events exactly on a random enemy in the dungeon so that it appears something happened at this enemy,
                // instead of randomly somewhere on the map, which may be way out of dungeon bounds.
                $enemyIngameXY     = $this->coordinatesService->calculateIngameLocationForMapLocation($enemy->getLatLng());
                $enemyGridIngameXY = $this->coordinatesService->calculateGridLocationForIngameLocation(
                    $enemyIngameXY,
                    config('keystoneguru.heatmap.service.data.player.size_x'),
                    config('keystoneguru.heatmap.service.data.player.size_y')
                );

                // @TODO This uses old format - needs to use new format IF you were to use this again, right now this function appears not to be used
                // See #2632
                $attributes = [
                    'run_id'             => $runId,
                    'keystone_run_id'    => $keystoneRunId,
                    'logged_run_id'      => $loggedRunId,
                    'period'             => $runPeriod,
                    'season'             => sprintf('season-%s-%d', $season->expansion->shortname, $season->index),
                    'region_id'          => $regionId,
                    'realm_type'         => 'generated',
                    'wow_instance_id'    => $dungeon->instance_id,
                    'challenge_mode_id'  => $dungeon->challenge_mode_id,
                    'level'              => $level,
                    'affix_ids'          => $affixIds,
                    'success'            => $success,
                    'start'              => $start,
                    'end'                => $end,
                    'duration_ms'        => $runDurationMs,
                    'par_time_ms'        => $runDurationMs,
                    'timer_fraction'     => $timerFraction,
                    'num_deaths'         => 0,
                    'ui_map_id'          => $enemyIngameXY->getFloor()->ui_map_id,
                    'pos_x'              => $enemyIngameXY->getX(2),
                    'pos_y'              => $enemyIngameXY->getY(2),
                    'pos_grid_x'         => $enemyGridIngameXY->getX(2),
                    'pos_grid_y'         => $enemyGridIngameXY->getY(2),
                    'num_members'        => 5,
                    'average_item_level' => $averageItemLevel,
                    'event_type'         => $type,
                    'characters'         => '[]',
                    'created_at'         => $dateTime,
                    'updated_at'         => $dateTime,
                ];

                if ($type === CombatLogEventEventType::NpcDeath) {
                    $attributes['context'] = json_encode([
                        '@timestamp'       => $now->unix(),
                        'pos_enemy_x'      => $enemyIngameXY->getX(2),
                        'pos_enemy_y'      => $enemyIngameXY->getY(2),
                        'pos_enemy_grid_x' => $enemyGridIngameXY->getX(2),
                        'pos_enemy_grid_y' => $enemyGridIngameXY->getY(2),
                    ]);
                } else if ($type === CombatLogEventEventType::PlayerDeath) {
                    $attributes['context'] = json_encode([
                        '@timestamp' => $now->unix(),
                        'npc_id'     => $enemy->npc_id,
                    ]);
                } else if ($type === CombatLogEventEventType::PlayerSpell) {
                    $attributes['context'] = json_encode([
                        '@timestamp' => $now->unix(),
                        'spell_id'   => rand(10000, 1000000),
                    ]);
                }

                $combatLogEventAttributes->push($attributes);
            }

            CombatLogEvent::insert($combatLogEventAttributes->toArray());

            $result = CombatLogEvent::orderByDesc('id')->take($eventsPerRun)->get();

            // Insert into OS
            CombatLogEvent::opensearch()
                ->documents()
                ->create($result->pluck('id')->toArray());

            if(($i + 1) % 100 === 0) {
                echo sprintf('Inserted run %d/%d', $i + 1, $runCount) . PHP_EOL;
            }
        }

        return $result;
    }
}
