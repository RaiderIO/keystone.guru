<?php

namespace App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\Models\MountableAreaIntersection;
use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\MountableArea;
use App\Models\SimulationCraft\SimulationCraftRaidBuffs;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RaidEventPull implements RaidEventOutputInterface, RaidEventPullInterface
{
    private int $pullIndex;

    private bool $bloodLust = false;

    private int $delay = 0;

    /** @var Collection<int, RaidEventPullEnemy> */
    private Collection $raidEventPullEnemies;

    public function __construct(
        private readonly CoordinatesServiceInterface      $coordinatesService,
        private readonly SimulationCraftRaidEventsOptions $options,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function calculateRaidEventPullEnemies(
        KillZone $killZone,
        array    $path,
    ): RaidEventPullInterface {
        // If bloodlust is enabled, and if this pull has bloodlust active on it..
        $this->bloodLust = $this->options->hasRaidBuff(SimulationCraftRaidBuffs::Bloodlust) &&
            in_array($killZone->id, explode(',', $this->options->simulate_bloodlust_per_pull));

        $this->pullIndex            = $killZone->index;
        $this->raidEventPullEnemies = collect();

        foreach ($killZone->getEnemies()->groupBy('npc_id') as $npcId => $enemies) {
            /** @var Collection<int, Enemy> $enemies */
            $enemyIndex = 1;
            foreach ($enemies as $enemy) {
                $this->addEnemy($enemy, $enemyIndex++);
            }
        }

        // Do not calculate a delay for an empty pull as it's impossible to determine a location for such a pull
        $this->delay = $this->raidEventPullEnemies->isEmpty() ? 0 : (int)round($this->calculateDelay($path));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateDelay(array $path): float
    {
        $pathCount = count($path);
        if ($pathCount < 2) {
            return 0;
        }

        // Find the index of the last same-floor segment so ranged compensation is applied exactly once there
        $lastSameFloorSegIdx = null;
        for ($i = $pathCount - 2; $i >= 0; $i--) {
            if ($path[$i]->getFloor()?->id === $path[$i + 1]->getFloor()?->id) {
                $lastSameFloorSegIdx = $i;
                break;
            }
        }

        $totalDelay = 0.0;
        for ($i = 0; $i < $pathCount - 1; $i++) {
            $from = $path[$i];
            $to   = $path[$i + 1];

            // Cross-floor transitions (floor-switch marker pairs) have 0 travel time
            if ($from->getFloor()?->id !== $to->getFloor()?->id) {
                continue;
            }

            $totalDelay += $this->calculateDelayBetweenPoints($from, $to, $i === $lastSameFloorSegIdx);
        }

        return $totalDelay;
    }

    public function calculateDelayBetweenPoints(LatLng $latLngA, LatLng $latLngB, bool $applyRangedCompensation = true): float
    {
        if ($latLngA->getFloor()?->id !== $latLngB->getFloor()?->id) {
            throw new InvalidArgumentException('Cannot calculate delay between two points if floor differs!');
        }

        [
            $mountFactorsAndSpeeds,
            $mountCasts,
        ] = $this->calculateMountedFactorAndMountCastsBetweenPoints(
            $latLngA,
            $latLngB,
        );

        $pointAIngameCoordinates = $this->coordinatesService->calculateIngameLocationForMapLocation($latLngA);
        $pointBIngameCoordinates = $this->coordinatesService->calculateIngameLocationForMapLocation($latLngB);

        $ingameDistanceToPointB = $this->coordinatesService->distanceBetweenPoints(
            $pointAIngameCoordinates->getX(),
            $pointBIngameCoordinates->getX(),
            $pointAIngameCoordinates->getY(),
            $pointBIngameCoordinates->getY(),
        );

        if ($applyRangedCompensation) {
            $ingameDistanceToPointB -= $this->options->ranged_pull_compensation_yards;
        }

        $delayMounted       = 0;
        $totalMountedFactor = 0;

        foreach ($mountFactorsAndSpeeds as $mountFactorAndSpeed) {
            /** @var array{factor: float, speed: int} $mountFactorAndSpeed */
            $totalMountedFactor += $mountFactorAndSpeed['factor'];
            $delayMounted += $this->calculateDelayForDistanceMounted(
                $ingameDistanceToPointB,
                $mountFactorAndSpeed['factor'],
                $mountFactorAndSpeed['speed'],
            );
        }

        $delayMountCasts = $this->calculateDelayForMountCasts($mountCasts);
        $delayOnFoot     = $this->calculateDelayForDistanceOnFoot(
            $ingameDistanceToPointB * (1 - $totalMountedFactor),
        );

        // If we used the mount, check if we are going to be quicker by not mounting (due to the $mountCasts taking time)
        if ($totalMountedFactor > 0) {
            $delayOnFootWithoutMounting = $this->calculateDelayForDistanceOnFoot(
                $ingameDistanceToPointB,
            );
            if ($delayOnFootWithoutMounting < $delayMounted + $delayOnFoot + $delayMountCasts) {
                $delayMountCasts = 0;
                $delayMounted    = 0;
                $delayOnFoot     = $delayOnFootWithoutMounting;
            }
        }

        return $delayMounted + $delayOnFoot + $delayMountCasts;
    }

    /**
     * {@inheritDoc}
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self
    {
        $this->raidEventPullEnemies->push(
            new RaidEventPullEnemy($this->options, $enemy, $enemyIndexInPull),
        );

        return $this;
    }

    /**
     * The mounted factor is a value between 0 and 1 that indicates how much of the way we can mount - where 0 means
     * that we cannot mount, and 1 means we can mount all the way through. The mountCasts value indicates how many times
     * the user was dismounted and needed to re-cast their mount spell, which takes some time.
     *
     * @return array{0: list<array{factor: float, speed: int}>, 1: int}
     */
    public function calculateMountedFactorAndMountCastsBetweenPoints(
        LatLng $latLngA,
        LatLng $latLngB,
    ): array {
        // 0% of the time on mounts, 0 mount casts
        if (!$this->options->use_mounts) {
            return [
                [],
                0,
            ];
        }

        /** @var MountableArea|null $startMountableArea The mountable area that we started in - can be null if not started inside a mountable area */
        $startMountableArea = null;

        // Construct a list of intersections from mountable areas.
        /** @var Collection<int, MountableAreaIntersection> $allMountableAreaIntersections */
        $allMountableAreaIntersections = collect();
        foreach ($latLngA->getFloor()->mountableAreas as $mountableArea) {
            // Determine from which mountable area the location started
            if ($startMountableArea === null && $mountableArea->contains($this->coordinatesService, $latLngA)) {
                $startMountableArea = $mountableArea;
            }

            $intersections = $mountableArea->getIntersections(
                $this->coordinatesService,
                $latLngA,
                $latLngB,
            );

            if (empty($intersections)) {
                continue;
            }

            /** @var Collection<int, MountableAreaIntersection> $mountableAreaIntersections */
            $mountableAreaIntersections = collect();
            foreach ($intersections as $intersection) {
                $mountableAreaIntersections->push(
                    new MountableAreaIntersection($mountableArea, new LatLng($intersection->getLat(), $intersection->getLng(), $latLngA->getFloor())),
                );
            }

            // Add them to the general list
            $allMountableAreaIntersections = $allMountableAreaIntersections->merge($mountableAreaIntersections);
        }

        // If we did NOT find any intersections, it means that we either stayed mounted (inside zone) or can't mount (no zone)
        if ($allMountableAreaIntersections->isEmpty()) {
            if ($startMountableArea !== null) {
                // We are mounted 100% of the way, with 1 cast to mount up
                return [
                    [
                        [
                            'factor' => 1,
                            'speed'  => $startMountableArea->getSpeedOrDefault(),
                        ],
                    ],
                    1,
                ];
            } else {
                // We are mounted 0% of the way and will keep walking
                return [
                    [],
                    0,
                ];
            }
        }

        // Now that we have a (randomly sorted) list of mountable areas and intersections, we need to sort the list and
        // then determine if an intersection causes a mount up, or a dismount
        /** @var Collection<int, MountableAreaIntersection> $allMountableAreaIntersections */
        $allMountableAreaIntersections = $allMountableAreaIntersections->sortBy(
            fn(MountableAreaIntersection $foundIntersection) => $this->coordinatesService->distanceBetweenPoints(
                $latLngA->getLng(),
                $foundIntersection->getLatLng()->getLng(),
                $latLngA->getLat(),
                $foundIntersection->getLatLng()->getLat(),
            ),
        )->values();

        $totalDistance = $this->coordinatesService->distanceBetweenPoints(
            $latLngA->getLng(),
            $latLngB->getLng(),
            $latLngA->getLat(),
            $latLngB->getLat(),
        );

        // If we are currently mounted (as in, we finished killing the previous pack, now going to the current pack)
        // Then we also initialize mountCasts to 1, since when we're done killing, we need to cast mount which is 1.5s
        $isMounted  = $startMountableArea !== null;
        $mountCasts = (int)$isMounted;

        // We start at where we're at now
        $previousLatLng                     = $latLngA;
        $allMountableAreaIntersectionsCount = $allMountableAreaIntersections->count();

        $factorsAndSpeeds = [];
        foreach ($allMountableAreaIntersections as $index => $mountableAreaIntersection) {
            $distanceBetweenLatLngs = $this->coordinatesService->distanceBetweenPoints(
                $previousLatLng->getLng(),
                $mountableAreaIntersection->getLatLng()->getLng(),
                $previousLatLng->getLat(),
                $mountableAreaIntersection->getLatLng()->getLat(),
            );

            // Add the distance to the appropriate totalDistance variable
            if ($isMounted) {
                $factorsAndSpeeds[] = [
                    'factor' => $distanceBetweenLatLngs / $totalDistance,
                    'speed'  => $mountableAreaIntersection->getMountableArea()->getSpeedOrDefault(),
                ];
            }
            // We were not mounted this intersection - mount up for the next one!
            // But only if we have another intersection after this
            elseif ($index !== $allMountableAreaIntersectionsCount - 1) {
                $mountCasts++;
            }

            // Since we encountered a new edge of the zone, we're now either mounting or dismounting
            // But not the last point - that's when we reach the enemy and start attacking.
            $isMounted = !$isMounted;

            // We are now at the last known intersection point since we just travelled there
            $previousLatLng = $mountableAreaIntersection->getLatLng();
        }

        // One last thing - if we are mounted at this point, we need to add another entry since we reached our destination
        // while mounted. We shouldn't forget to add this last travel distance as well
        if ($isMounted) {
            /** @var MountableAreaIntersection $lastMountableAreaIntersection */
            $lastMountableAreaIntersection = $allMountableAreaIntersections->last();

            $distanceToPack = $this->coordinatesService->distanceBetweenPoints(
                $latLngB->getLng(),
                $lastMountableAreaIntersection->getLatLng()->getLng(),
                $latLngB->getLat(),
                $lastMountableAreaIntersection->getLatLng()->getLat(),
            );

            $factorsAndSpeeds[] = [
                'factor' => $distanceToPack / $totalDistance,
                'speed'  => $lastMountableAreaIntersection->getMountableArea()->getSpeedOrDefault(),
            ];
        }

        return [
            $factorsAndSpeeds,
            $mountCasts,
        ];
    }

    public function calculateDelayForDistanceOnFoot(float $ingameDistance): float
    {
        return max(0, $ingameDistance) / config('keystoneguru.character.default_movement_speed_yards_second');
    }

    public function calculateDelayForMountCasts(int $mountCasts): float
    {
        return max(0, $mountCasts) * config('keystoneguru.character.mount_cast_time_seconds');
    }

    public function calculateDelayForDistanceMounted(float $ingameDistance, float $factor, int $speed): float
    {
        return max(0, $ingameDistance * $factor) / $speed;
    }

    public function toString(): string
    {
        $result = sprintf(
            'raid_events+=/pull,pull=%02d,bloodlust=%d,delay=%03d,%senemies=',
            $this->pullIndex,
            (int)$this->bloodLust,
            $this->delay,
            $this->options->thundering_clear_seconds !== null ?
                sprintf('mark_duration=%d,', $this->options->thundering_clear_seconds) : '',
        );

        $enemyStrings = [];
        foreach ($this->raidEventPullEnemies as $raidEventPullEnemy) {
            $enemyStrings[] = $raidEventPullEnemy->toString();
        }

        $result .= implode('|', $enemyStrings);

        return $result;
    }
}
