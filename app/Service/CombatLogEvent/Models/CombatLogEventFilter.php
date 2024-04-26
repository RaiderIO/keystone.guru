<?php

namespace App\Service\CombatLogEvent\Models;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use Carbon\Carbon;
use Codeart\OpensearchLaravel\Search\Query;
use Codeart\OpensearchLaravel\Search\SearchQueries\BoolQuery;
use Codeart\OpensearchLaravel\Search\SearchQueries\Must;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\MatchOne;
use Codeart\OpensearchLaravel\Search\SearchQueries\Types\Range;
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

    private ?Carbon $dateStart = null;

    private ?Carbon $dateEnd = null;

    public function __construct(
        private readonly Dungeon $dungeon
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
    public function getDateStart(): ?Carbon
    {
        return $this->dateStart;
    }

    /**
     * @param Carbon|null $dateStart
     * @return CombatLogEventFilter
     */
    public function setDateStart(?Carbon $dateStart): CombatLogEventFilter
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * @return Carbon|null
     */
    public function getDateEnd(): ?Carbon
    {
        return $this->dateEnd;
    }

    /**
     * @param Carbon|null $dateEnd
     * @return CombatLogEventFilter
     */
    public function setDateEnd(?Carbon $dateEnd): CombatLogEventFilter
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }


    public function toArray(): array
    {
        return [
            'challenge_mode_id' => $this->dungeon->challenge_mode_id,
            'level_min'         => $this->levelMin,
            'level_max'         => $this->levelMax,
            'affix_groups'      => $this->affixGroups->map(function (AffixGroup $affixGroup) {
                return $affixGroup->getTextAttribute();
            })->toArray(),
            'affixes'           => $this->affixes->map(function (Affix $affix) {
                return __($affix->name, [], 'en_US');
            }),
            'dateStart'         => $this->dateStart?->toDateTimeString(),
            'dateEnd'           => $this->dateEnd?->toDateTimeString(),
        ];
    }

    public function toOpensearchQuery(array $must = []): array
    {
        $must[] = MatchOne::make('challenge_mode_id', $this->getDungeon()->challenge_mode_id);

        if ($this->levelMin !== null && $this->levelMax !== null) {
            $must[] = Range::make('level', [
                'gte' => $this->levelMin,
                'lte' => $this->levelMax,
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

    public static function fromArray(array $requestArray): CombatLogEventFilter
    {
        $combatLogEventFilter = new CombatLogEventFilter(
            dungeon: Dungeon::firstWhere('id', $requestArray['dungeon_id'])
        );

        if (isset($requestArray['level'])) {
            [$levelMin, $levelMax] = explode(';', $requestArray['level']);
            $combatLogEventFilter->setLevelMin((int)$levelMin)->setLevelMax((int)$levelMax);
        }

        return $combatLogEventFilter;
    }
}
