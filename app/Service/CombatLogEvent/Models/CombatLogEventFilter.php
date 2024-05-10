<?php

namespace App\Service\CombatLogEvent\Models;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use Carbon\Carbon;
use Codeart\OpensearchLaravel\Search\Query;
use Codeart\OpensearchLaravel\Search\SearchQueries\BoolQuery;
use Codeart\OpensearchLaravel\Search\SearchQueries\Filter;
use Codeart\OpensearchLaravel\Search\SearchQueries\Must;
use Codeart\OpensearchLaravel\Search\SearchQueries\Should;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\Range;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\Term;
use Illuminate\Support\Collection;
use RectorPrefix202402\Illuminate\Contracts\Support\Arrayable;

class CombatLogEventFilter implements Arrayable
{
    private ?int $levelMin = null;

    private ?int $levelMax = null;

    /** @var Collection<AffixGroup> */
    private Collection $affixGroups;

    /** @var Collection<Affix> */
    private Collection $affixes;

    private ?Carbon $dateFrom = null;

    private ?Carbon $dateTo = null;

    private ?int $durationMin = null;

    private ?int $durationMax = null;

    public function __construct(
        private readonly Dungeon $dungeon,
        private readonly string $eventType
    ) {
        $this->affixGroups = collect();
        $this->affixes     = collect();
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    /**
     * @return int|null
     */
    public function getLevelMin(): ?int
    {
        return $this->levelMin;
    }

    /**
     * @param int|null $levelMin
     * @return CombatLogEventFilter
     */
    public function setLevelMin(?int $levelMin): CombatLogEventFilter
    {
        $this->levelMin = $levelMin;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLevelMax(): ?int
    {
        return $this->levelMax;
    }

    /**
     * @param int|null $levelMax
     * @return CombatLogEventFilter
     */
    public function setLevelMax(?int $levelMax): CombatLogEventFilter
    {
        $this->levelMax = $levelMax;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAffixGroups(): Collection
    {
        return $this->affixGroups;
    }

    /**
     * @param Collection $affixGroups
     * @return CombatLogEventFilter
     */
    public function setAffixGroups(Collection $affixGroups): CombatLogEventFilter
    {
        $this->affixGroups = $affixGroups;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAffixes(): Collection
    {
        return $this->affixes;
    }

    /**
     * @param Collection $affixes
     * @return CombatLogEventFilter
     */
    public function setAffixes(Collection $affixes): CombatLogEventFilter
    {
        $this->affixes = $affixes;

        return $this;
    }

    /**
     * @return Carbon|null
     */
    public function getDateFrom(): ?Carbon
    {
        return $this->dateFrom;
    }

    /**
     * @param Carbon|null $dateFrom
     * @return CombatLogEventFilter
     */
    public function setDateFrom(?Carbon $dateFrom): CombatLogEventFilter
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    /**
     * @return Carbon|null
     */
    public function getDateTo(): ?Carbon
    {
        return $this->dateTo;
    }

    /**
     * @param Carbon|null $dateTo
     * @return CombatLogEventFilter
     */
    public function setDateTo(?Carbon $dateTo): CombatLogEventFilter
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDurationMin(): ?int
    {
        return $this->durationMin;
    }

    /**
     * @param int|null $durationMin
     * @return CombatLogEventFilter
     */
    public function setDurationMin(?int $durationMin): CombatLogEventFilter
    {
        $this->durationMin = $durationMin;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDurationMax(): ?int
    {
        return $this->durationMax;
    }

    /**
     * @param int|null $durationMax
     * @return CombatLogEventFilter
     */
    public function setDurationMax(?int $durationMax): CombatLogEventFilter
    {
        $this->durationMax = $durationMax;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'challenge_mode_id' => $this->dungeon->challenge_mode_id,
            'event_type'        => $this->eventType,
            'level_min'         => $this->levelMin,
            'level_max'         => $this->levelMax,
            'affix_groups'      => $this->affixGroups->map(function (AffixGroup $affixGroup) {
                return $affixGroup->getTextAttribute();
            })->toArray(),
            'affixes'           => $this->affixes->map(function (Affix $affix) {
                return __($affix->name, [], 'en_US');
            }),
            'date_start'        => $this->dateFrom?->toDateTimeString(),
            'date_end'          => $this->dateTo?->toDateTimeString(),
            'duration_min'      => $this->durationMin,
            'duration_max'      => $this->durationMax,
        ];
    }

    public function toOpensearchQuery(array $must = []): array
    {
        $must[] = MatchOne::make('challenge_mode_id', $this->getDungeon()->challenge_mode_id);
        $must[] = MatchOne::make('event_type', $this->eventType);

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

        if ($this->affixGroups->isNotEmpty()) {
            $must[] = BoolQuery::make([
                Should::make(
                    $this->affixGroups->map(function (AffixGroup $affixGroup) {
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

        if ($this->dateTo !== null || $this->dateFrom !== null) {
            $params = [];
            if ($this->dateFrom !== null) {
                $params['gte'] = $this->dateFrom->getTimestamp();
            }
            if ($this->dateTo !== null) {
                $params['lte'] = $this->dateTo->getTimestamp();
            }

            $must[] = Range::make('start', $params);
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

    public static function fromArray(array $requestArray): CombatLogEventFilter
    {
        $combatLogEventFilter = new CombatLogEventFilter(
            dungeon: Dungeon::firstWhere('id', $requestArray['dungeon_id']),
            eventType: $requestArray['event_type']
        );

        if (isset($requestArray['level'])) {
            [$levelMin, $levelMax] = explode(';', $requestArray['level']);
            $combatLogEventFilter->setLevelMin((int)$levelMin)->setLevelMax((int)$levelMax);
        }

        if (isset($requestArray['duration'])) {
            [$durationMin, $durationMax] = explode(';', $requestArray['duration']);
            $combatLogEventFilter->setdurationMin((int)$durationMin)->setdurationMax((int)$durationMax);
        }

        if (isset($requestArray['affixes'])) {
            $combatLogEventFilter->setAffixes(Affix::whereIn('id', $requestArray['affixes'])->get());
        }

        if (isset($requestArray['affix_groups'])) {
            $combatLogEventFilter->setAffixGroups(AffixGroup::whereIn('id', $requestArray['affix_groups'])->get());
        }

        if (isset($requestArray['date_range_from'])) {
            $combatLogEventFilter->setDateFrom(Carbon::createFromFormat('Y-m-d', $requestArray['date_range_from']));
        }

        if (isset($requestArray['date_range_to'])) {
            $combatLogEventFilter->setDateTo(Carbon::createFromFormat('Y-m-d', $requestArray['date_range_to']));
        }

        return $combatLogEventFilter;
    }
}
