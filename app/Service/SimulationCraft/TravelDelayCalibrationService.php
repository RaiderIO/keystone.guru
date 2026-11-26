<?php

namespace App\Service\SimulationCraft;

use App\Dto\Request\CombatLog\Route\CombatLogRoutePlayerDeathRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Logic\SimulationCraft\RaidEventPull;
use App\Logic\SimulationCraft\RaidEventsCollection;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\CombatLog\Builders\CombatLogRouteDungeonRouteBuilder;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\SimulationCraft\Dtos\TravelDelayCalibrationSummary;
use App\Service\SimulationCraft\Dtos\TravelDelayTransition;
use App\Service\SimulationCraft\Enums\TravelDelayExclusion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class TravelDelayCalibrationService implements TravelDelayCalibrationServiceInterface
{
    public function __construct(
        private CoordinatesServiceInterface               $coordinatesService,
        private KillZonePathServiceInterface              $killZonePathService,
        private SeasonServiceInterface                    $seasonService,
        private SeasonAffixGroupServiceInterface          $seasonAffixGroupService,
        private DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        private DungeonRouteAffixGroupRepositoryInterface $dungeonRouteAffixGroupRepository,
        private KillZoneRepositoryInterface               $killZoneRepository,
        private KillZoneEnemyRepositoryInterface          $killZoneEnemyRepository,
        private KillZoneSpellRepositoryInterface          $killZoneSpellRepository,
        private EnemyRepositoryInterface                  $enemyRepository,
        private NpcRepositoryInterface                    $npcRepository,
        private SpellRepositoryInterface                  $spellRepository,
        private FloorRepositoryInterface                  $floorRepository,
        private DungeonRepositoryInterface                $dungeonRepository,
    ) {
    }

    /**
     * The route is persisted inside a transaction that is always rolled back: the kill zone paths are read back from
     * the database, so the Stub repositories the other throwaway builds use would leave them without any kill zones.
     *
     * {@inheritDoc}
     */
    public function measureCombatLogRoute(
        CombatLogRouteRequestDto $combatLogRoute,
        int                      $rangedPullCompensationYards,
        bool                     $useMounts,
    ): Collection {
        DB::beginTransaction();

        try {
            $dungeonRoute = new CombatLogRouteDungeonRouteBuilder(
                $this->seasonService,
                $this->seasonAffixGroupService,
                $this->coordinatesService,
                $this->dungeonRouteRepository,
                $this->dungeonRouteAffixGroupRepository,
                $this->killZoneRepository,
                $this->killZoneEnemyRepository,
                $this->killZoneSpellRepository,
                $this->enemyRepository,
                $this->npcRepository,
                $this->spellRepository,
                $this->floorRepository,
                $this->dungeonRepository,
                $combatLogRoute,
                -1,
            )->build();

            $options = new SimulationCraftRaidEventsOptions([
                'key_level'                      => $combatLogRoute->challengeMode->level ?? 2,
                'affix'                          => '',
                'raid_buffs_mask'                => 0,
                'hp_percent'                     => 100,
                'simulate_bloodlust_per_pull'    => '',
                'ranged_pull_compensation_yards' => $rangedPullCompensationYards,
                'use_mounts'                     => $useMounts,
            ]);
            $options->setRelation('dungeonRoute', $dungeonRoute);

            $raidEventPulls = new RaidEventsCollection($this->coordinatesService, $this->killZonePathService, $options)
                ->calculateRaidEvents()
                ->getRaidEventPulls();

            /** @var array<int, array{seconds: int, yards: float}> $predictionsByPullIndex */
            $predictionsByPullIndex = $raidEventPulls
                ->mapWithKeys(static fn(RaidEventPull $raidEventPull): array => [
                    $raidEventPull->getPullIndex() => [
                        'seconds' => $raidEventPull->getDelay(),
                        'yards'   => $raidEventPull->getTravelYards(),
                    ],
                ])
                ->all();

            /** @var array<int, int> $pullIndexByEnemyId */
            $pullIndexByEnemyId = [];
            foreach ($dungeonRoute->killZones as $killZone) {
                foreach ($killZone->getEnemies() as $enemy) {
                    $pullIndexByEnemyId[$enemy->id] ??= $killZone->index;
                }
            }

            return $this->createTransitions(
                $dungeonRoute->dungeon->key,
                $predictionsByPullIndex,
                $this->getPullTimingsByPullIndex($combatLogRoute, $pullIndexByEnemyId),
                collect($combatLogRoute->playerDeaths ?? [])
                    ->map(static fn(CombatLogRoutePlayerDeathRequestDto $playerDeath): Carbon => $playerDeath->getDiedAt())
                    ->values(),
            );
        } finally {
            DB::rollBack();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createTransitions(
        string     $dungeonKey,
        array      $predictionsByPullIndex,
        array      $pullTimingsByPullIndex,
        Collection $playerDeaths,
    ): Collection {
        $result = collect();

        ksort($pullTimingsByPullIndex);
        foreach ($pullTimingsByPullIndex as $pullIndex => $pullTiming) {
            $previousPullTiming = $pullTimingsByPullIndex[$pullIndex - 1] ?? null;
            if ($previousPullTiming === null || !isset($predictionsByPullIndex[$pullIndex])) {
                continue;
            }

            $observedSeconds = ($pullTiming['start']->getTimestampMs() - $previousPullTiming['end']->getTimestampMs()) / 1000;

            $exclusion = null;
            if ($observedSeconds < 0) {
                $exclusion = TravelDelayExclusion::Overlap;
            } elseif ($playerDeaths->contains(
                static fn(Carbon $diedAt): bool => $diedAt->between($previousPullTiming['start'], $pullTiming['start']),
            )) {
                $exclusion = TravelDelayExclusion::PlayerDeath;
            }

            $result->push(new TravelDelayTransition(
                $dungeonKey,
                $pullIndex,
                $predictionsByPullIndex[$pullIndex]['seconds'],
                $observedSeconds,
                $exclusion,
                $predictionsByPullIndex[$pullIndex]['yards'],
                $previousPullTiming['end'],
                $pullTiming['start'],
            ));
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function summarize(Collection $transitions): TravelDelayCalibrationSummary
    {
        $included = $transitions->filter(static fn(TravelDelayTransition $transition): bool => $transition->isIncluded())->values();

        /** @var list<float> $predicted */
        $predicted = $included->map(static fn(TravelDelayTransition $transition): float => (float)$transition->predictedSeconds)->all();
        /** @var list<float> $observed */
        $observed = $included->map(static fn(TravelDelayTransition $transition): float => $transition->observedSeconds)->all();

        $ratios = $included
            ->filter(static fn(TravelDelayTransition $transition): bool => $transition->predictedSeconds > 0)
            ->map(static fn(TravelDelayTransition $transition): float => $transition->observedSeconds / $transition->predictedSeconds)
            ->values()
            ->all();

        [$slope, $intercept] = $this->fitLeastSquares($predicted, $observed);

        return new TravelDelayCalibrationSummary(
            $included->count(),
            $transitions->count() - $included->count(),
            $this->median($predicted) ?? 0.0,
            $this->median($observed) ?? 0.0,
            array_sum($predicted),
            array_sum($observed),
            $this->median($ratios),
            $slope,
            $intercept,
        );
    }

    /**
     * @param array<int, int> $pullIndexByEnemyId
     *
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    private function getPullTimingsByPullIndex(CombatLogRouteRequestDto $combatLogRoute, array $pullIndexByEnemyId): array
    {
        /** @var array<int, array{start: Carbon|null, end: Carbon|null}> $result */
        $result = [];
        foreach ($combatLogRoute->npcs ?? [] as $npc) {
            $resolvedEnemy = $npc->getResolvedEnemy();
            $pullIndex     = $resolvedEnemy === null ? null : ($pullIndexByEnemyId[$resolvedEnemy->id] ?? null);
            if ($pullIndex === null || $npc->engagedAt === null || $npc->diedAt === null) {
                continue;
            }

            $timing = $result[$pullIndex] ?? ['start' => null, 'end' => null];
            if ($timing['start'] === null || $npc->getEngagedAt()->isBefore($timing['start'])) {
                $timing['start'] = $npc->getEngagedAt();
            }
            if ($timing['end'] === null || $npc->getDiedAt()->isAfter($timing['end'])) {
                $timing['end'] = $npc->getDiedAt();
            }
            $result[$pullIndex] = $timing;
        }

        /** @var array<int, array{start: Carbon, end: Carbon}> $result */
        return $result;
    }

    /**
     * @param list<float> $values
     */
    private function median(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }

        sort($values);
        $count  = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 0 ? ($values[$middle - 1] + $values[$middle]) / 2 : $values[$middle];
    }

    /**
     * @param list<float> $x
     * @param list<float> $y
     *
     * @return array{0: float|null, 1: float|null} The slope and intercept of y = slope * x + intercept.
     */
    private function fitLeastSquares(array $x, array $y): array
    {
        $count = count($x);
        if ($count < 2) {
            return [null, null];
        }

        $meanX = array_sum($x) / $count;
        $meanY = array_sum($y) / $count;

        $covariance = 0.0;
        $varianceX  = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $covariance += ($x[$i] - $meanX) * ($y[$i] - $meanY);
            $varianceX += ($x[$i] - $meanX) ** 2;
        }

        if ($varianceX == 0.0) {
            return [null, null];
        }

        $slope = $covariance / $varianceX;

        return [$slope, $meanY - ($slope * $meanX)];
    }
}
