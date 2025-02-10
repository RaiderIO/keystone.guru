<?php

namespace App\Service\RaiderIO\Dtos;


use App\Models\Affix;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Season;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * Class that is used to represent the filter that is received from the browser.
 */
class HeatmapDataFilter implements Arrayable
{
    private ?string $region          = null;
    private ?int    $keyLevelMin     = null;
    private ?int    $keyLevelMax     = null;
    private ?int    $itemLevelMin    = null;
    private ?int    $itemLevelMax    = null;
    private ?int    $playerDeathsMin = null;
    private ?int    $playerDeathsMax = null;
    /** @var Collection<Affix> */
    private Collection $includeAffixIds;
    /** @var Collection<CharacterClassSpecialization> */
    private Collection $includeSpecIds;
    /** @var Collection<CharacterClass> */
    private Collection $includeClassIds;
    private ?int       $minPeriod          = null;
    private ?int       $maxPeriod          = null;
    private ?int       $timerFractionMin   = null;
    private ?int       $timerFractionMax   = null;
    private ?int       $minSamplesRequired = null;

    public function __construct(
        private readonly Dungeon                 $dungeon,
        private readonly CombatLogEventEventType $eventType,
        private readonly CombatLogEventDataType  $dataType
    ) {
        $this->includeAffixIds = collect();
        $this->includeSpecIds  = collect();
        $this->includeClassIds = collect();
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

    public function setRegion(?string $region): HeatmapDataFilter
    {
        $this->region = $region;

        return $this;
    }

    public function getKeyLevelMin(): ?int
    {
        return $this->keyLevelMin;
    }

    public function setKeyLevelMin(?int $keyLevelMin): HeatmapDataFilter
    {
        $this->keyLevelMin = $keyLevelMin;

        return $this;
    }

    public function getKeyLevelMax(): ?int
    {
        return $this->keyLevelMax;
    }

    public function setKeyLevelMax(?int $keyLevelMax): HeatmapDataFilter
    {
        $this->keyLevelMax = $keyLevelMax;

        return $this;
    }

    public function getPlayerDeathsMax(): ?int
    {
        return $this->playerDeathsMax;
    }

    public function setPlayerDeathsMax(?int $playerDeathsMax): HeatmapDataFilter
    {
        $this->playerDeathsMax = $playerDeathsMax;

        return $this;
    }

    public function getPlayerDeathsMin(): ?int
    {
        return $this->playerDeathsMin;
    }

    public function setPlayerDeathsMin(?int $playerDeathsMin): HeatmapDataFilter
    {
        $this->playerDeathsMin = $playerDeathsMin;

        return $this;
    }

    public function getItemLevelMax(): ?int
    {
        return $this->itemLevelMax;
    }

    public function setItemLevelMax(?int $itemLevelMax): HeatmapDataFilter
    {
        $this->itemLevelMax = $itemLevelMax;

        return $this;
    }

    public function getItemLevelMin(): ?int
    {
        return $this->itemLevelMin;
    }

    public function setItemLevelMin(?int $itemLevelMin): HeatmapDataFilter
    {
        $this->itemLevelMin = $itemLevelMin;

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

    /**
     * @return Collection<CharacterClassSpecialization>
     */
    public function getIncludeSpecIds(): Collection
    {
        return $this->includeSpecIds;
    }

    /**
     * @param Collection<CharacterClassSpecialization> $includeSpecIds
     * @return HeatmapDataFilter
     */
    public function setIncludeSpecIds(Collection $includeSpecIds): HeatmapDataFilter
    {
        $this->includeSpecIds = $includeSpecIds;

        return $this;
    }

    /**
     * @return Collection<CharacterClass>
     */
    public function getIncludeClassIds(): Collection
    {
        return $this->includeClassIds;
    }

    /**
     * @param Collection<CharacterClass> $includeClassIds
     * @return HeatmapDataFilter
     */
    public function setIncludeClassIds(Collection $includeClassIds): HeatmapDataFilter
    {
        $this->includeClassIds = $includeClassIds;

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

    /**
     * @return int|null
     */
    public function getMinSamplesRequired(): ?int
    {
        return $this->minSamplesRequired;
    }

    /**
     * @param int|null $minSamplesRequired
     * @return HeatmapDataFilter
     */
    public function setMinSamplesRequired(?int $minSamplesRequired): HeatmapDataFilter
    {
        $this->minSamplesRequired = $minSamplesRequired;

        return $this;
    }

    /**
     * Converts the filter into an array that will be passed to the Raider.io API in the URL
     *
     * @param Season|null $mostRecentSeason
     * @return array
     */
    public function toArray(Season $mostRecentSeason = null): array
    {
        $result = [
            'challengeModeId' => $this->dungeon->challenge_mode_id,
            'type'            => $this->getEventType()->value,
            'dataType'        => $this->getDataType()->value,
        ];

        if ($this->getRegion() !== GameServerRegion::WORLD) {
            $result['region'] = $this->getRegion();
        }
        $result['minMythicLevel']           = $this->getKeyLevelMin();
        $result['maxMythicLevel']           = $this->getKeyLevelMax();
        $result['minItemLevel']             = $this->getItemLevelMin();
        $result['maxItemLevel']             = $this->getItemLevelMax();
        $result['minPlayerDeaths']          = $this->getPlayerDeathsMin();
        $result['maxPlayerDeaths']          = $this->getPlayerDeathsMax();
        $result['minTimerFraction']         = $this->getTimerFractionMin();
        $result['maxTimerFraction']         = $this->getTimerFractionMax();
        $result['minRequiredSamplesFactor'] = $this->getMinSamplesRequired() / 100000;

        if ($this->getIncludeAffixIds()->isNotEmpty()) {
            $result['includeAffixIds'] = implode(',', $this->getIncludeAffixIds()->map(
                fn(Affix $affix) => $affix->affix_id
            )->toArray());
        }

        if ($this->getIncludeSpecIds()->isNotEmpty()) {
            $result['includeSpecIds'] = implode(',', $this->getIncludeSpecIds()->map(
                fn(CharacterClassSpecialization $characterClassSpecialization) => $characterClassSpecialization->specialization_id
            )->toArray());
        }

        if ($this->getIncludeClassIds()->isNotEmpty()) {
            $result['includeClassIds'] = implode(',', $this->getIncludeClassIds()->map(
                fn(CharacterClass $characterClass) => $characterClass->class_id
            )->toArray());
        }

        if ($this->getMinPeriod() !== null && $this->getMaxPeriod() !== null) {
            $result['minPeriod'] = $this->getMinPeriod();
            $result['maxPeriod'] = $this->getMaxPeriod();
        }

        return array_filter($result);
    }


    public static function fromArray(array $requestArray): HeatmapDataFilter
    {
        $heatmapDataFilter = new HeatmapDataFilter(
            dungeon: Dungeon::firstWhere('id', $requestArray['dungeonId']),
            eventType: CombatLogEventEventType::from($requestArray['type'] ?? CombatLogEventEventType::NpcDeath->value),
            dataType: CombatLogEventDataType::from($requestArray['dataType'] ?? CombatLogEventDataType::PlayerPosition->value)
        );

        $heatmapDataFilter->setRegion($requestArray['region'] ?? null);
        $heatmapDataFilter->setKeyLevelMin(isset($requestArray['minMythicLevel']) ? (int)$requestArray['minMythicLevel'] : null);
        $heatmapDataFilter->setKeyLevelMax(isset($requestArray['maxMythicLevel']) ? (int)$requestArray['maxMythicLevel'] : null);
        $heatmapDataFilter->setItemLevelMin(isset($requestArray['minItemLevel']) ? (int)$requestArray['minItemLevel'] : null);
        $heatmapDataFilter->setItemLevelMax(isset($requestArray['maxItemLevel']) ? (int)$requestArray['maxItemLevel'] : null);
        $heatmapDataFilter->setPlayerDeathsMin(isset($requestArray['minPlayerDeaths']) ? (int)$requestArray['minPlayerDeaths'] : null);
        $heatmapDataFilter->setPlayerDeathsMax(isset($requestArray['maxPlayerDeaths']) ? (int)$requestArray['maxPlayerDeaths'] : null);
        $heatmapDataFilter->setTimerFractionMin($requestArray['minTimerFraction'] ?? null);
        $heatmapDataFilter->setTimerFractionMax($requestArray['maxTimerFraction'] ?? null);
        $heatmapDataFilter->setMinSamplesRequired($requestArray['minSamplesRequired'] ?? null);

        $heatmapDataFilter->setIncludeAffixIds(isset($requestArray['includeAffixIds']) ?
            Affix::whereIn('affix_id', $requestArray['includeAffixIds'])->get() : collect());

        $heatmapDataFilter->setIncludeSpecIds(isset($requestArray['includeSpecIds']) ?
            CharacterClassSpecialization::whereIn('specialization_id', $requestArray['includeSpecIds'])->get() : collect());

        $heatmapDataFilter->setIncludeClassIds(isset($requestArray['includeClassIds']) ?
            CharacterClass::whereIn('class_id', $requestArray['includeClassIds'])->get() : collect());

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
