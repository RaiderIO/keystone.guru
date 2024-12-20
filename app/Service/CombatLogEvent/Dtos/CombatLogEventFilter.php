<?php

namespace App\Service\CombatLogEvent\Dtos;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use App\Service\Season\SeasonServiceInterface;
use Codeart\OpensearchLaravel\Search\Query;
use Codeart\OpensearchLaravel\Search\SearchQueries\BoolQuery;
use Codeart\OpensearchLaravel\Search\SearchQueries\Filter;
use Codeart\OpensearchLaravel\Search\SearchQueries\Must;
use Codeart\OpensearchLaravel\Search\SearchQueries\Should;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\Range;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\Term;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class CombatLogEventFilter implements Arrayable
{
    private ?int $levelMin = null;

    private ?int $levelMax = null;

    /** @var Collection<AffixGroup> */
    private Collection $affixGroups;

    /** @var Collection<Affix> */
    private Collection $affixes;

    private ?int $weeklyAffixGroups = null;

    private ?int $durationMin = null;

    private ?int $durationMax = null;

    public function __construct(
        private readonly SeasonServiceInterface  $seasonService,
        private readonly Dungeon                 $dungeon,
        private readonly CombatLogEventEventType $eventType,
        private readonly CombatLogEventDataType $dataType
    ) {
        $this->affixGroups = collect();
        $this->affixes     = collect();
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function getEventType(): CombatLogEventEventType
    {
        return $this->eventType;
    }

    public function getDataType(): CombatLogEventDataType
    {
        return $this->dataType;
    }

    public function getLevelMin(): ?int
    {
        return $this->levelMin;
    }

    public function setLevelMin(?int $levelMin): CombatLogEventFilter
    {
        $this->levelMin = $levelMin;

        return $this;
    }

    public function getLevelMax(): ?int
    {
        return $this->levelMax;
    }

    public function setLevelMax(?int $levelMax): CombatLogEventFilter
    {
        $this->levelMax = $levelMax;

        return $this;
    }

    /**
     * @return Collection<AffixGroup>
     */
    public function getAffixGroups(): Collection
    {
        return $this->affixGroups;
    }

    /**
     * @param Collection<AffixGroup> $affixGroups
     * @return CombatLogEventFilter
     */
    public function setAffixGroups(Collection $affixGroups): CombatLogEventFilter
    {
        $this->affixGroups = $affixGroups;

        return $this;
    }

    /**
     * @return Collection<Affix>
     */
    public function getAffixes(): Collection
    {
        return $this->affixes;
    }

    /**
     * @param Collection<Affix> $affixes
     * @return CombatLogEventFilter
     */
    public function setAffixes(Collection $affixes): CombatLogEventFilter
    {
        $this->affixes = $affixes;

        return $this;
    }

    public function getWeeklyAffixGroups(): ?int
    {
        return $this->weeklyAffixGroups;
    }

    public function setWeeklyAffixGroups(?int $weeklyAffixGroups): CombatLogEventFilter
    {
        $this->weeklyAffixGroups = $weeklyAffixGroups;

        return $this;
    }

    public function getDurationMin(): ?int
    {
        return $this->durationMin;
    }

    public function setDurationMin(?int $durationMin): CombatLogEventFilter
    {
        $this->durationMin = $durationMin;

        return $this;
    }

    public function getDurationMax(): ?int
    {
        return $this->durationMax;
    }

    public function setDurationMax(?int $durationMax): CombatLogEventFilter
    {
        $this->durationMax = $durationMax;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'challenge_mode_id'   => $this->dungeon->challenge_mode_id,
            'event_type'          => $this->eventType->value,
            'data_type'           => $this->dataType,
            'level_min'           => $this->levelMin,
            'level_max'           => $this->levelMax,
            'affix_groups'        => $this->affixGroups->map(function (AffixGroup $affixGroup) {
                return $affixGroup->getTextAttribute();
            })->toArray(),
            'affixes'             => $this->affixes->map(function (Affix $affix) {
                return __($affix->name, [], 'en_US');
            }),
            'weekly_affix_groups' => $this->weeklyAffixGroups,
            'duration_min'        => $this->durationMin,
            'duration_max'        => $this->durationMax,
        ];
    }

    public function toOpensearchQuery(array $must = []): array
    {
        $dungeon = $this->getDungeon();

        $must[] = MatchOne::make('challenge_mode_id', $dungeon->challenge_mode_id);
        $must[] = MatchOne::make('event_type', $this->eventType->value);

//        /** @var Floor $firstFloor */
//        $firstFloor = $dungeon->floors->first();
//
//        dd($firstFloor);
//
//        $must[]     = BoolQuery::make([
//            Should::make([
//                BoolQuery::make([
//                    Must::make([
//                        MatchOne::make('ui_map_id', $firstFloor->ui_map_id),
//                        Range::make('pos_x', [
//                            'gte' => $firstFloor->ingame_min_x,
//                            'lte' => $firstFloor->ingame_max_x,
//                        ]),
//                    ]),
//                ]),
//            ]),
//        ]);

        if ($this->levelMin !== null && $this->levelMax !== null) {
            $must[] = Range::make('level', [
                'gte' => $this->levelMin,
                'lte' => $this->levelMax,
            ]);
        }

        if ($this->durationMin !== null && $this->durationMax !== null) {
            $must[] = Range::make('duration_ms', [
                'gte' => $this->durationMin * 60000,
                'lte' => $this->durationMax * 60000,
            ]);
        }

        if ($this->affixes->isNotEmpty()) {
            $must[] = BoolQuery::make([
                Should::make(
                    $this->affixes->map(function (Affix $affix) {
                        return MatchOne::make('affix_id', $affix->affix_id);
                    })->toArray()
                ),
            ]);
        }


        $affixGroups = $this->getAffixGroups();

        if ($this->weeklyAffixGroups !== null) {
            // Add an AffixGroup filter
            /** @var Collection<WeeklyAffixGroup> $weeklyAffixGroupsSinceStart */
            $weeklyAffixGroupsSinceStart = $this->seasonService->getWeeklyAffixGroupsSinceStart(
                $this->seasonService->getMostRecentSeasonForDungeon($dungeon),
                GameServerRegion::getUserOrDefaultRegion()
            );

            /** @var WeeklyAffixGroup $weeklyAffixGroup */
            $weeklyAffixGroup = $weeklyAffixGroupsSinceStart->firstWhere(function (WeeklyAffixGroup $weeklyAffixGroup) {
                return $weeklyAffixGroup->week === $this->weeklyAffixGroups;
            });
            $affixGroups->push($weeklyAffixGroup->affixGroup);

            // Add a date range filter
            $must[] = Range::make('start', [
                'gte' => $weeklyAffixGroup->date->getTimestamp(),
                'lte' => $weeklyAffixGroup->date->addWeek()->getTimestamp(),
            ]);
        }

        if ($affixGroups->isNotEmpty()) {
            $must[] = BoolQuery::make([
                Should::make(
                    $affixGroups->map(function (AffixGroup $affixGroup) {
                        return BoolQuery::make([
                            Filter::make(
                                $affixGroup->affixes->map(function (Affix $affix) {
                                    return Term::make('affix_id', $affix->affix_id);
                                })->toArray()
                            ),
                        ]);
                    })->toArray()
                ),
            ]);
        }

        return [
            Query::make([
                BoolQuery::make([
                    Must::make(
                        $must,
                    ),
                ]),
            ]),
        ];
    }

    public static function fromArray(SeasonServiceInterface $seasonService, array $requestArray): CombatLogEventFilter
    {
        $combatLogEventFilter = new CombatLogEventFilter(
            seasonService: $seasonService,
            dungeon: Dungeon::firstWhere('id', $requestArray['dungeon_id']),
            eventType: CombatLogEventEventType::from($requestArray['event_type']),
            dataType: CombatLogEventDataType::from($requestArray['data_type'])
        );

        if (isset($requestArray['level'])) {
            [$levelMin, $levelMax] = explode(';', $requestArray['level']);
            $combatLogEventFilter->setLevelMin((int)$levelMin)->setLevelMax((int)$levelMax);
        }

        if (isset($requestArray['duration'])) {
            [$durationMin, $durationMax] = explode(';', $requestArray['duration']);
            $combatLogEventFilter->setDurationMin((int)$durationMin)->setDurationMax((int)$durationMax);
        }

        if (isset($requestArray['affixes'])) {
            $combatLogEventFilter->setAffixes(Affix::whereIn('id', $requestArray['affixes'])->get());
        }

        if (isset($requestArray['affix_groups'])) {
            $combatLogEventFilter->setAffixGroups(AffixGroup::whereIn('id', $requestArray['affix_groups'])->get());
        }

        if (isset($requestArray['weekly_affix_groups'])) {
            $combatLogEventFilter->setWeeklyAffixGroups($requestArray['weekly_affix_groups']);
        }

        return $combatLogEventFilter;
    }

    public static function fromHeatmapDataFilter(SeasonServiceInterface $seasonService, HeatmapDataFilter $heatmapDataFilter): CombatLogEventFilter
    {
        $combatLogEventFilter = new CombatLogEventFilter(
            $seasonService,
            $heatmapDataFilter->getDungeon(),
            $heatmapDataFilter->getEventType(),
            $heatmapDataFilter->getDataType()
        );

        $combatLogEventFilter->setLevelMin($heatmapDataFilter->getLevelMin());
        $combatLogEventFilter->setLevelMax($heatmapDataFilter->getLevelMax());
        $combatLogEventFilter->setAffixGroups($heatmapDataFilter->getAffixGroups());
        $combatLogEventFilter->setAffixes($heatmapDataFilter->getAffixes());
        $combatLogEventFilter->setWeeklyAffixGroups($heatmapDataFilter->getWeeklyAffixGroups());
        $combatLogEventFilter->setDurationMin($heatmapDataFilter->getDurationMin());
        $combatLogEventFilter->setDurationMax($heatmapDataFilter->getDurationMax());

        return $combatLogEventFilter;
    }
}
