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
    private Collection $affixes;

    private Collection $weeklyAffixGroups;

    private ?int $durationMin = null;

    private ?int $durationMax = null;

    public function __construct(
        private readonly Dungeon                 $dungeon,
        private readonly CombatLogEventEventType $eventType,
        private readonly CombatLogEventDataType  $dataType
    ) {
        $this->affixes           = collect();
        $this->weeklyAffixGroups = collect();
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

    public function getWeeklyAffixGroups(): Collection
    {
        return $this->weeklyAffixGroups;
    }

    public function setWeeklyAffixGroups(Collection $weeklyAffixGroups): HeatmapDataFilter
    {
        $this->weeklyAffixGroups = $weeklyAffixGroups;

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

        if ($this->getDurationMin() !== null) {
            $result['minTimerFraction'] = ($this->getDurationMin() * 60) / $this->dungeon->currentMappingVersion->timer_max_seconds;
        }

        if ($this->getDurationMax() !== null) {
            $result['maxTimerFraction'] = ($this->getDurationMax() * 60) / $this->dungeon->currentMappingVersion->timer_max_seconds;
        }

        if ($this->getAffixes()->isNotEmpty()) {
            $result['includeAffixIds'] = implode(',', $this->getAffixes()->map(fn(Affix $affix) => $affix->affix_id)->toArray());
        }

        if ($this->getWeeklyAffixGroups()->isNotEmpty()) {
            $gameServerRegion = GameServerRegion::getUserOrDefaultRegion();

            $periodStart = $mostRecentSeason !== null ? $gameServerRegion->getKeystoneLeaderboardPeriod(
                $mostRecentSeason->start
            ) : 0;

            $result['minPeriod'] = $periodStart + $this->getWeeklyAffixGroups()->min();
            $result['maxPeriod'] = $periodStart + $this->getWeeklyAffixGroups()->max();
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

        if (isset($requestArray['duration'])) {
            [$durationMin, $durationMax] = explode(';', $requestArray['duration']);
            $heatmapDataFilter->setDurationMin((int)$durationMin)->setDurationMax((int)$durationMax);
        }

        if (isset($requestArray['affixes'])) {
            $heatmapDataFilter->setAffixes(Affix::whereIn('id', $requestArray['affixes'])->get());
        }

        if (isset($requestArray['weekly_affix_groups'])) {
            $heatmapDataFilter->setWeeklyAffixGroups(collect($requestArray['weekly_affix_groups']));
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
