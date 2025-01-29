<?php

namespace App\Service\RaiderIO\Dtos;


use App\Models\Affix;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Season;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class HeatmapDataFilter implements Arrayable
{
    private ?int $levelMin = null;

    private ?int $levelMax = null;

    /** @var Collection<Affix> */
    private Collection $includeAffixIds;

    private ?int $minPeriod = null;

    private ?int $maxPeriod = null;

    private ?int $timerFractionMin = null;

    private ?int $timerFractionMax = null;

    public function __construct(
        private readonly Dungeon                 $dungeon,
        private readonly CombatLogEventEventType $eventType,
        private readonly CombatLogEventDataType  $dataType
    ) {
        $this->includeAffixIds = collect();
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
     * @return Collection<Affix>
     */
    public function getIncludeAffixIds(): Collection
    {
        return $this->includeAffixIds;
    }

    /**
     * @param Collection<Affix> $includeAffixIds
     * @return HeatmapDataFilter
     */
    public function setIncludeAffixIds(Collection $includeAffixIds): HeatmapDataFilter
    {
        $this->includeAffixIds = $includeAffixIds;

        return $this;
    }

    public function getMinPeriod(): ?int
    {
        return $this->minPeriod;
    }

    public function setMinPeriod(?int $minPeriod): void
    {
        $this->minPeriod = $minPeriod;
    }

    public function getMaxPeriod(): ?int
    {
        return $this->maxPeriod;
    }

    public function setMaxPeriod(?int $maxPeriod): void
    {
        $this->maxPeriod = $maxPeriod;
    }

    public function getTimerFractionMin(): ?int
    {
        return $this->timerFractionMin;
    }

    public function setTimerFractionMin(?int $timerFractionMin): HeatmapDataFilter
    {
        $this->timerFractionMin = $timerFractionMin;

        return $this;
    }

    public function getTimerFractionMax(): ?int
    {
        return $this->timerFractionMax;
    }

    public function setTimerFractionMax(?int $timerFractionMAx): HeatmapDataFilter
    {
        $this->timerFractionMax = $timerFractionMAx;

        return $this;
    }

    public function toArray(Season $mostRecentSeason = null): array
    {
        $result = [
            'challengeModeId' => $this->dungeon->challenge_mode_id,
            'type'            => $this->getEventType()->value,
            // @TODO Raider.io API does not support this
            // 'eventType'        => $this->getEventType()->value,
            // 'dataType'         => $this->getDataType()->value,
        ];

        if ($this->getLevelMin() !== null) {
            $result['minMythicLevel'] = $this->getLevelMin();
        }

        if ($this->getLevelMax() !== null) {
            $result['maxMythicLevel'] = $this->getLevelMax();
        }

        if ($this->getTimerFractionMin() !== null) {
            $result['minTimerFraction'] = $this->getTimerFractionMin();
        }

        if ($this->getTimerFractionMax() !== null) {
            $result['maxTimerFraction'] = $this->getTimerFractionMax();
        }

        if ($this->getIncludeAffixIds()->isNotEmpty()) {
            $result['includeAffixIds'] = implode(',', $this->getIncludeAffixIds()->map(fn(Affix $affix) => $affix->affix_id)->toArray());
        }

        if ($this->getMinPeriod() !== null && $this->getMaxPeriod() !== null) {
            $result['minPeriod'] = $this->getMinPeriod();
            $result['maxPeriod'] = $this->getMaxPeriod();
        }

        return $result;
    }


    public static function fromArray(array $requestArray): HeatmapDataFilter
    {
        $heatmapDataFilter = new HeatmapDataFilter(
            dungeon: Dungeon::firstWhere('id', $requestArray['dungeonId']),
            eventType: CombatLogEventEventType::from($requestArray['type']),
            dataType: CombatLogEventDataType::from($requestArray['dataType'])
        );

        if (isset($requestArray['minMythicLevel'])) {
            $heatmapDataFilter->setLevelMin((int)$requestArray['minMythicLevel']);
        }

        if (isset($requestArray['maxMythicLevel'])) {
            $heatmapDataFilter->setLevelMax((int)$requestArray['maxMythicLevel']);
        }

        if (isset($requestArray['minTimerFraction'])) {
            $heatmapDataFilter->setTimerFractionMin($requestArray['minTimerFraction']);
        }

        if (isset($requestArray['maxTimerFraction'])) {
            $heatmapDataFilter->setTimerFractionMax($requestArray['maxTimerFraction']);
        }

        if (isset($requestArray['includeAffixIds'])) {
            $heatmapDataFilter->setIncludeAffixIds(Affix::whereIn('affix_id', $requestArray['includeAffixIds'])->get());
        }

        if (isset($requestArray['minPeriod']) && (int)$requestArray['minPeriod'] > 0) {
            $heatmapDataFilter->setMinPeriod($requestArray['minPeriod']);
        }

        if (isset($requestArray['maxPeriod']) && (int)$requestArray['maxPeriod'] > 0) {
            $heatmapDataFilter->setMaxPeriod($requestArray['maxPeriod']);
        }

        return $heatmapDataFilter;
    }

//    public static function fromCombatLogEventFilter(CombatLogEventFilter $combatLogEventFilter): HeatmapDataFilter
//    {
//        $heatmapDataFilter = new HeatmapDataFilter($combatLogEventFilter->getDungeon(), $combatLogEventFilter->getEventType());
//        $heatmapDataFilter->setLevelMin($combatLogEventFilter->getLevelMin());
//        $heatmapDataFilter->setLevelMax($combatLogEventFilter->getLevelMax());
//        $heatmapDataFilter->setAffixes($combatLogEventFilter->getAffixes());
//        $heatmapDataFilter->setDurationMin($combatLogEventFilter->getDurationMin());
//        $heatmapDataFilter->setDurationMax($combatLogEventFilter->getDurationMax());
//
//        return $heatmapDataFilter;
//    }
}
