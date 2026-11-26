<?php

namespace App\Service\SimulationCraft;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Service\SimulationCraft\Dtos\TravelDelayCalibrationSummary;
use App\Service\SimulationCraft\Dtos\TravelDelayTransition;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface TravelDelayCalibrationServiceInterface
{
    /**
     * Builds the route the Auto Route Creator makes of this combat log route, without persisting it, and compares the
     * travel delay SimulationCraft is given towards every pull with the time the group actually took to get there.
     *
     * @return Collection<int, TravelDelayTransition> One transition per pull after the first, in pull order.
     */
    public function measureCombatLogRoute(
        CombatLogRouteRequestDto $combatLogRoute,
        int                      $rangedPullCompensationYards,
        bool                     $useMounts,
    ): Collection;

    /**
     * @param array<int, array{seconds: int, yards: float}> $predictionsByPullIndex Pull index => predicted delay and path length.
     * @param array<int, array{start: Carbon, end: Carbon}> $pullTimingsByPullIndex Pull index => first engage and last death.
     * @param Collection<int, Carbon>                       $playerDeaths           When players died during the run.
     *
     * @return Collection<int, TravelDelayTransition> One transition per pull that has a timed pull right before it.
     */
    public function createTransitions(
        string     $dungeonKey,
        array      $predictionsByPullIndex,
        array      $pullTimingsByPullIndex,
        Collection $playerDeaths,
    ): Collection;

    /**
     * @param Collection<int, TravelDelayTransition> $transitions
     */
    public function summarize(Collection $transitions): TravelDelayCalibrationSummary;
}
