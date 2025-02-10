<?php

namespace App\Service\CombatLogEvent\Dtos;

use App\Models\Affix;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use App\Service\Season\SeasonServiceInterface;
use Codeart\OpensearchLaravel\Search\Query;
use Codeart\OpensearchLaravel\Search\SearchQueries\BoolQuery;
use Codeart\OpensearchLaravel\Search\SearchQueries\Must;
use Codeart\OpensearchLaravel\Search\SearchQueries\Should;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\Range;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * This class is used as a filter to extract CombatLogEvents from Opensearch.
 */
class CombatLogEventFilter implements Arrayable
{
    private ?string $region          = null;
    private ?int    $keyLevelMin     = null;
    private ?int    $keyLevelMax     = null;
    private ?int    $itemLevelMin    = null;
    private ?int    $itemLevelMax    = null;
    private ?int    $playerDeathsMin = null;
    private ?int    $playerDeathsMax = null;

    /** @var Collection<Affix> */
    private Collection $affixes;

    /** @var Collection<CharacterClassSpecialization> */
    private Collection $specializations;

    /** @var Collection<CharacterClass> */
    private Collection $classes;
    private ?int       $periodMin          = null;
    private ?int       $periodMax          = null;
    private ?int       $durationMin        = null;
    private ?int       $durationMax        = null;
    private ?int       $minSamplesRequired = null;

    public function __construct(
        private readonly SeasonServiceInterface  $seasonService,
        private readonly Dungeon                 $dungeon,
        private readonly CombatLogEventEventType $eventType,
        private readonly CombatLogEventDataType  $dataType
    ) {
        $this->affixes         = collect();
        $this->specializations = collect();
        $this->classes         = collect();
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

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): CombatLogEventFilter
    {
        $this->region = $region;

        return $this;
    }

    public function getKeyLevelMin(): ?int
    {
        return $this->keyLevelMin;
    }

    public function setKeyLevelMin(?int $keyLevelMin): CombatLogEventFilter
    {
        $this->keyLevelMin = $keyLevelMin;

        return $this;
    }

    public function getKeyLevelMax(): ?int
    {
        return $this->keyLevelMax;
    }

    public function setKeyLevelMax(?int $keyLevelMax): CombatLogEventFilter
    {
        $this->keyLevelMax = $keyLevelMax;

        return $this;
    }

    public function getItemLevelMin(): ?int
    {
        return $this->itemLevelMin;
    }

    public function setItemLevelMin(?int $itemLevelMin): CombatLogEventFilter
    {
        $this->itemLevelMin = $itemLevelMin;

        return $this;
    }

    public function getItemLevelMax(): ?int
    {
        return $this->itemLevelMax;
    }

    public function setItemLevelMax(?int $itemLevelMax): CombatLogEventFilter
    {
        $this->itemLevelMax = $itemLevelMax;

        return $this;
    }

    public function getPlayerDeathsMin(): ?int
    {
        return $this->playerDeathsMin;
    }

    public function setPlayerDeathsMin(?int $playerDeathsMin): CombatLogEventFilter
    {
        $this->playerDeathsMin = $playerDeathsMin;

        return $this;
    }

    public function getPlayerDeathsMax(): ?int
    {
        return $this->playerDeathsMax;
    }

    public function setPlayerDeathsMax(?int $playerDeathsMax): CombatLogEventFilter
    {
        $this->playerDeathsMax = $playerDeathsMax;

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

    /**
     * @return Collection<CharacterClassSpecialization>
     */
    public function getSpecializations(): Collection
    {
        return $this->specializations;
    }

    /**
     * @param Collection<CharacterClassSpecialization> $specializations
     * @return CombatLogEventFilter
     */
    public function setSpecializations(Collection $specializations): CombatLogEventFilter
    {
        $this->specializations = $specializations;

        return $this;
    }

    /**
     * @return Collection<CharacterClass>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    /**
     * @param Collection<CharacterClass> $classes
     * @return CombatLogEventFilter
     */
    public function setClasses(Collection $classes): CombatLogEventFilter
    {
        $this->classes = $classes;

        return $this;
    }

    public function getPeriodMin(): ?int
    {
        return $this->periodMin;
    }

    public function setPeriodMin(?int $periodMin): void
    {
        $this->periodMin = $periodMin;
    }

    public function getPeriodMax(): ?int
    {
        return $this->periodMax;
    }

    public function setPeriodMax(?int $periodMax): void
    {
        $this->periodMax = $periodMax;
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

    /**
     * @return int|null
     */
    public function getMinSamplesRequired(): ?int
    {
        return $this->minSamplesRequired;
    }

    /**
     * @param int|null $minSamplesRequired
     * @return CombatLogEventFilter
     */
    public function setMinSamplesRequired(?int $minSamplesRequired): CombatLogEventFilter
    {
        $this->minSamplesRequired = $minSamplesRequired;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'challenge_mode_id'    => $this->dungeon->challenge_mode_id,
            'event_type'           => $this->eventType->value,
            'data_type'            => $this->dataType,
            'region'               => $this->region,
            'key_level_min'        => $this->keyLevelMin,
            'key_level_max'        => $this->keyLevelMax,
            'item_level_min'       => $this->itemLevelMin,
            'item_level_max'       => $this->itemLevelMax,
            'player_deaths_min'    => $this->playerDeathsMin,
            'player_deaths_max'    => $this->playerDeathsMax,
            'min_samples_required' => $this->minSamplesRequired,
            'affixes'              => $this->affixes->map(function (Affix $affix) {
                return __($affix->name, [], 'en_US');
            }),
            'specializations'      => $this->specializations->map(function (CharacterClassSpecialization $characterClassSpecialization) {
                return __($characterClassSpecialization->name, [], 'en_US');
            }),
            'classes'      => $this->classes->map(function (CharacterClass $characterClass) {
                return __($characterClass->name, [], 'en_US');
            }),
            'period_min'           => $this->periodMin,
            'period_max'           => $this->periodMax,
            'duration_min'         => $this->durationMin,
            'duration_max'         => $this->durationMax,
        ]);
    }

    public function toOpensearchQuery(array $must = []): array
    {
        $dungeon = $this->getDungeon();

        $must[] = MatchOne::make('challenge_mode_id', $dungeon->challenge_mode_id);
        $must[] = MatchOne::make('event_type', $this->eventType->value);
        // These are raider.io region IDs
        if ($this->region !== GameServerRegion::WORLD) {
            $must[] = MatchOne::make('region_id', match ($this->region) {
                GameServerRegion::EUROPE => 3,
                GameServerRegion::AMERICAS => 2,
                GameServerRegion::CHINA => 6,
                GameServerRegion::KOREA => 4,
                GameServerRegion::TAIWAN => 5,
                default => 2, // US
            });
        }

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

        if ($this->keyLevelMin !== null && $this->keyLevelMax !== null) {
            $must[] = Range::make('level', [
                'gte' => $this->keyLevelMin,
                'lte' => $this->keyLevelMax,
            ]);
        }

        if ($this->itemLevelMin !== null && $this->itemLevelMax !== null) {
            $must[] = Range::make('average_item_level', [
                'gte' => $this->keyLevelMin,
                'lte' => $this->keyLevelMax,
            ]);
        }

        if ($this->playerDeathsMin !== null && $this->playerDeathsMax !== null) {
            $must[] = Range::make('num_deaths', [
                'gte' => $this->playerDeathsMin,
                'lte' => $this->playerDeathsMax,
            ]);
        }

        // @TODO Implement minSamplesRequired

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

        // @TODO Add nested query for specializations and classes - it's nested in the character data
//        if ($this->specializations->isNotEmpty()) {
//            $must[] = BoolQuery::make([
//                Should::make(
//                    $this->specializations->map(function (CharacterClassSpecialization $characterClassSpecialization) {
//                        return MatchOne::make('spec_id', $characterClassSpecialization->specialization_id);
//                    })->toArray()
//                ),
//            ]);
//        }

        // Add an AffixGroup filter
        $mostRecentSeason = $this->seasonService->getMostRecentSeasonForDungeon($dungeon);

        if ($mostRecentSeason !== null) {
            /** @var Collection<WeeklyAffixGroup> $weeklyAffixGroupsSinceStart */
            $weeklyAffixGroupsSinceStart = $this->seasonService->getWeeklyAffixGroupsSinceStart(
                $mostRecentSeason,
                GameServerRegion::getUserOrDefaultRegion()
            );

            /** @var WeeklyAffixGroup $minWeeklyAffixGroup */
            $minWeeklyAffixGroup = $weeklyAffixGroupsSinceStart->firstWhere(function (WeeklyAffixGroup $weeklyAffixGroup) use ($mostRecentSeason) {
                return $weeklyAffixGroup->week === $this->getPeriodMin() - $mostRecentSeason?->start_period;
            });
            /** @var WeeklyAffixGroup $maxWeeklyAffixGroup */
            $maxWeeklyAffixGroup = $weeklyAffixGroupsSinceStart->firstWhere(function (WeeklyAffixGroup $weeklyAffixGroup) use ($mostRecentSeason) {
                return $weeklyAffixGroup->week === $this->getPeriodMax() - $mostRecentSeason?->start_period;
            });

            // Add a date range filter
            $must[] = Range::make('start', [
                'gte' => $minWeeklyAffixGroup->date->getTimestamp(),
                'lte' => $maxWeeklyAffixGroup->date->addWeek()->getTimestamp(),
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

    public static function fromHeatmapDataFilter(SeasonServiceInterface $seasonService, HeatmapDataFilter $heatmapDataFilter): CombatLogEventFilter
    {
        $combatLogEventFilter = new CombatLogEventFilter(
            $seasonService,
            $heatmapDataFilter->getDungeon(),
            $heatmapDataFilter->getEventType(),
            $heatmapDataFilter->getDataType()
        );

        $combatLogEventFilter->setRegion($heatmapDataFilter->getRegion());
        $combatLogEventFilter->setKeyLevelMin($heatmapDataFilter->getKeyLevelMin());
        $combatLogEventFilter->setKeyLevelMax($heatmapDataFilter->getKeyLevelMax());
        $combatLogEventFilter->setItemLevelMin($heatmapDataFilter->getItemLevelMin());
        $combatLogEventFilter->setItemLevelMax($heatmapDataFilter->getItemLevelMax());
        $combatLogEventFilter->setPlayerDeathsMin($heatmapDataFilter->getPlayerDeathsMin());
        $combatLogEventFilter->setPlayerDeathsMax($heatmapDataFilter->getPlayerDeathsMax());
        $combatLogEventFilter->setMinSamplesRequired($heatmapDataFilter->getMinSamplesRequired());
        $combatLogEventFilter->setAffixes($heatmapDataFilter->getIncludeAffixIds());
        $combatLogEventFilter->setSpecializations($heatmapDataFilter->getIncludeSpecIds());
        $combatLogEventFilter->setClasses($heatmapDataFilter->getIncludeClassIds());
        $combatLogEventFilter->setPeriodMin($heatmapDataFilter->getMinPeriod());
        $combatLogEventFilter->setPeriodMax($heatmapDataFilter->getMaxPeriod());

        $timerSeconds = $heatmapDataFilter->getDungeon()->currentMappingVersion->timer_max_seconds;
        $combatLogEventFilter->setDurationMin(($heatmapDataFilter->getTimerFractionMin() * 60) / $timerSeconds);
        $combatLogEventFilter->setDurationMax(($heatmapDataFilter->getTimerFractionMax() * 60) / $timerSeconds);

        return $combatLogEventFilter;
    }
}
