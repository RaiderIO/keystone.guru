<?php

namespace App\Service\RaiderIO\Dtos;


use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HeatmapDataFilter implements Arrayable
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
        private readonly string  $eventType,
        private readonly string  $dataType
    ) {
        $this->affixGroups = collect();
        $this->affixes     = collect();
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function getLevelMin(): ?int
    {
        return $this->levelMin;
    }

    public function setLevelMin(?int $levelMin): HeatmapDataFilter
    {
        $this->levelMin = $levelMin;

        return $this;
    }

    public function getLevelMax(): ?int
    {
        return $this->levelMax;
    }

    public function setLevelMax(?int $levelMax): HeatmapDataFilter
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
     * @return HeatmapDataFilter
     */
    public function setAffixGroups(Collection $affixGroups): HeatmapDataFilter
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
     * @return HeatmapDataFilter
     */
    public function setAffixes(Collection $affixes): HeatmapDataFilter
    {
        $this->affixes = $affixes;

        return $this;
    }

    public function getDateFrom(): ?Carbon
    {
        return $this->dateFrom;
    }

    public function setDateFrom(?Carbon $dateFrom): HeatmapDataFilter
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function getDateTo(): ?Carbon
    {
        return $this->dateTo;
    }

    public function setDateTo(?Carbon $dateTo): HeatmapDataFilter
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    public function getDurationMin(): ?int
    {
        return $this->durationMin;
    }

    public function setDurationMin(?int $durationMin): HeatmapDataFilter
    {
        $this->durationMin = $durationMin;

        return $this;
    }

    public function getDurationMax(): ?int
    {
        return $this->durationMax;
    }

    public function setDurationMax(?int $durationMax): HeatmapDataFilter
    {
        $this->durationMax = $durationMax;

        return $this;
    }

    public function toArray(): array
    {
        $result = [
            'challenge_mode_id' => $this->dungeon->challenge_mode_id,
            'event_type'        => $this->getEventType(),
            'data_type'         => $this->getDataType(),
        ];

        if ($this->getLevelMin() !== null) {
            $result['level_min'] = $this->getLevelMin();
        }

        if ($this->getLevelMax() !== null) {
            $result['level_max'] = $this->getLevelMax();
        }

        if ($this->getDurationMin() !== null) {
            $result['duration_min'] = $this->getDurationMin();
        }

        if ($this->getDurationMax() !== null) {
            $result['duration_max'] = $this->getDurationMax();
        }

        if ($this->getAffixes()->isNotEmpty()) {
            $result['affix_ids'] = $this->getAffixes()->map(fn(Affix $affix) => $affix->affix_id)->toArray();
        }

        if ($this->getAffixGroups()->isNotEmpty()) {
            $result['affix_groups'] = $this->getAffixGroups()->map(fn(AffixGroup $affixGroup) => [
                $affixGroup->affixes->map(fn(Affix $affix) => $affix->affix_id)->toArray(),
            ])->toArray();
        }

        // @TODO Add date ranges (going to get reworked anyway)

        return $result;
    }


    public static function fromArray(array $requestArray): HeatmapDataFilter
    {
        $heatmapDataFilter = new HeatmapDataFilter(
            dungeon: Dungeon::firstWhere('id', $requestArray['dungeon_id']),
            eventType: $requestArray['event_type'],
            dataType: $requestArray['data_type']
        );

        if (isset($requestArray['level'])) {
            [$levelMin, $levelMax] = explode(';', $requestArray['level']);
            $heatmapDataFilter->setLevelMin((int)$levelMin)->setLevelMax((int)$levelMax);
        }

        if (isset($requestArray['duration'])) {
            [$durationMin, $durationMax] = explode(';', $requestArray['duration']);
            $heatmapDataFilter->setDurationMin((int)$durationMin)->setDurationMax((int)$durationMax);
        }

        if (isset($requestArray['affixes'])) {
            $heatmapDataFilter->setAffixes(Affix::whereIn('id', $requestArray['affixes'])->get());
        }

        if (isset($requestArray['affix_groups'])) {
            $heatmapDataFilter->setAffixGroups(AffixGroup::whereIn('id', $requestArray['affix_groups'])->get());
        }

        if (isset($requestArray['date_range_from'])) {
            $heatmapDataFilter->setDateFrom(Carbon::createFromFormat('Y-m-d', $requestArray['date_range_from']));
        }

        if (isset($requestArray['date_range_to'])) {
            $heatmapDataFilter->setDateTo(Carbon::createFromFormat('Y-m-d', $requestArray['date_range_to']));
        }

        return $heatmapDataFilter;
    }

//    public static function fromCombatLogEventFilter(CombatLogEventFilter $combatLogEventFilter): HeatmapDataFilter
//    {
//        $heatmapDataFilter = new HeatmapDataFilter($combatLogEventFilter->getDungeon(), $combatLogEventFilter->getEventType());
//        $heatmapDataFilter->setLevelMin($combatLogEventFilter->getLevelMin());
//        $heatmapDataFilter->setLevelMax($combatLogEventFilter->getLevelMax());
//        $heatmapDataFilter->setAffixGroups($combatLogEventFilter->getAffixGroups());
//        $heatmapDataFilter->setAffixes($combatLogEventFilter->getAffixes());
//        $heatmapDataFilter->setDateFrom($combatLogEventFilter->getDateFrom());
//        $heatmapDataFilter->setDateTo($combatLogEventFilter->getDateTo());
//        $heatmapDataFilter->setDurationMin($combatLogEventFilter->getDurationMin());
//        $heatmapDataFilter->setDurationMax($combatLogEventFilter->getDurationMax());
//
//        return $heatmapDataFilter;
//    }
}
