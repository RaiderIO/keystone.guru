<?php

namespace App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\Models\MountableAreaIntersection;
use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\MountableArea;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RaidEventPull implements RaidEventOutputInterface, RaidEventPullInterface
{
    private int $pullIndex;

    private bool $bloodLust = false;

    private int $delay = 0;

    /** @var Collection<RaidEventPullEnemy> */
    private Collection $raidEventPullEnemies;

    public function __construct(private readonly CoordinatesServiceInterface $coordinatesService, private readonly SimulationCraftRaidEventsOptions $options)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, LatLng $previousKillLocation): RaidEventPullInterface
    {
        // If bloodlust is enabled, and if this pull has bloodlust active on it..
        $this->bloodLust = $this->options->bloodlust && in_array($killZone->id, explode(',', $this->options->simulate_bloodlust_per_pull));

        $this->pullIndex            = $killZone->index;
        $this->raidEventPullEnemies = collect();

        foreach ($killZone->getEnemies()->groupBy('npc_id') as $npcId => $enemies) {
            /** @var Collection<Enemy> $enemies */
            $enemyIndex = 1;
            foreach ($enemies as $enemy) {
                $this->addEnemy($enemy, $enemyIndex++);
            }
        }

        // Do not calculate a delay for an empty pull as it's impossible to determine a location for such a pull
        $this->delay = $this->raidEventPullEnemies->isEmpty() ? 0 : $this->calculateDelay($killZone, $previousKillLocation);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateDelay(KillZone $killZone, LatLng $previousKillLocation): float
    {
        // Convert the location of the pack to in-game location, and then determine the delay according to floor x-y
        $killLocation = $killZone->getKillLocation();
        // No enemies killed, no location, no pull, no delay
        if ($killLocation === null) {
            return 0;
        }

        // On the same floor it's easy - just calculate the distance between and then subtract any mounted speed
        if ($previousKillLocation->getFloor()->id === $killLocation->getFloor()->id) {
            $result = $this->calculateDelayBetweenPoints($previousKillLocation, $killLocation);
        } else {
            // Different floors are a bit tricky - we need to find the closest floor switch marker, calculate the distance to that
            // and then from that floor marker on the other side, calculate the distance to the pull. Add all up and you got the delay you're looking for
            $result = $this->calculateDelayBetweenPointsOnDifferentFloors($previousKillLocation, $killLocation);
        }

        return $result;
    }

    public function calculateDelayBetweenPoints(LatLng $latLngA, LatLng $latLngB): float
    {
        if ($latLngA->getFloor()?->id !== $latLngB->getFloor()?->id) {
            throw new InvalidArgumentException('Cannot calculate delay between two points if floor differs!');
        }

        [$mountFactorsAndSpeeds, $mountCasts] = $this->calculateMountedFactorAndMountCastsBetweenPoints(
            $latLngA,
            $latLngB
        );

        $pointAIngameCoordinates = $this->coordinatesService->calculateIngameLocationForMapLocation($latLngA);
        $pointBIngameCoordinates = $this->coordinatesService->calculateIngameLocationForMapLocation($latLngB);

        $ingameDistanceToPointB = $this->coordinatesService->distanceBetweenPoints(
                $pointAIngameCoordinates->getX(), $pointBIngameCoordinates->getX(),
                $pointAIngameCoordinates->getY(), $pointBIngameCoordinates->getY()
            ) - $this->options->ranged_pull_compensation_yards;

        $delayMounted       = 0;
        $totalMountedFactor = 0;

        foreach ($mountFactorsAndSpeeds as $mountFactorAndSpeed) {
            /** @var $mountFactorAndSpeed array{factor: float, speed: int} */
            $totalMountedFactor += $mountFactorAndSpeed['factor'];
            $delayMounted       += $this->calculateDelayForDistanceMounted(
                $ingameDistanceToPointB,
                $mountFactorAndSpeed['factor'],
                $mountFactorAndSpeed['speed']
            );
        }

        $delayMountCasts = $this->calculateDelayForMountCasts($mountCasts);
        $delayOnFoot     = $this->calculateDelayForDistanceOnFoot(
            $ingameDistanceToPointB * (1 - $totalMountedFactor)
        );

        // If we utilized the mount, check if we are going to be quicker by not mounting (due to the $mountCasts taking time)
        if ($totalMountedFactor > 0) {
            $delayOnFootWithoutMounting = $this->calculateDelayForDistanceOnFoot(
                $ingameDistanceToPointB
            );
            if ($delayOnFootWithoutMounting < $delayMounted + $delayOnFoot + $delayMountCasts) {
                $delayMountCasts = 0;
                $delayMounted    = 0;
                $delayOnFoot     = $delayOnFootWithoutMounting;
            }
        }

        // Calculate the final result
        return $delayMounted + $delayOnFoot + $delayMountCasts;
    }

    public function calculateDelayBetweenPointsOnDifferentFloors(LatLng $latLngA, LatLng $latLngB): float
    {
        return $this->calculateDistanceBetweenPointAndClosestFloorSwitchMarker($latLngA, $latLngB) +
            $this->calculateDistanceBetweenPointAndClosestFloorSwitchMarker($latLngA, $latLngB);
    }

    private function calculateDistanceBetweenPointAndClosestFloorSwitchMarker(LatLng $latLngA, LatLng $latLngB): float
    {
        $previousKillFloorClosestDungeonFloorSwitchMarker = $latLngA->getFloor()->findClosestFloorSwitchMarker(
            $this->coordinatesService,
            $latLngA,
            $latLngB->getFloor()->id
        );

        if ($previousKillFloorClosestDungeonFloorSwitchMarker !== null) {
            // Now that we know the location of the floor switch marker, we can
            $result = $this->calculateDelayBetweenPoints($latLngA, $previousKillFloorClosestDungeonFloorSwitchMarker->getLatLng());
        } else {
            // @TODO #1621
            logger()->warning(sprintf('There is no floor switch marker from %d to %d!', $latLngA->getFloor()->id, $latLngB->getFloor()->id));
            $result = 20;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function addEnemy(Enemy $enemy, int $enemyIndexInPull): self
    {
        $this->raidEventPullEnemies->push(
            (new RaidEventPullEnemy($this->options, $enemy, $enemyIndexInPull))
        );

        return $this;
    }

    /**
     * The mounted factor is a value between 0 and 1 which indicates how much of the way we can mount - where 0 means
     * that we cannot mount, and 1 means we can mount all the way through. The mountCasts value indicates how many times
     * the user was dismounted and needed to re-cast their mount spell, which takes some time.
     *
     * @return array{array{factor: float, speed: int}, mountCasts: int}
     */
    public function calculateMountedFactorAndMountCastsBetweenPoints(
        LatLng $latLngA, LatLng $latLngB
    ): array {
        // 0% of the time on mounts, 0 mount casts
        if (!$this->options->use_mounts) {
            return [[], 0];
        }

        /** @var MountableArea|null $startMountableArea The mountable area that we started in - can be null if not started inside a mountable area */
        $startMountableArea = null;

        // Construct a list of intersections from mountable areas.
        /** @var Collection<MountableAreaIntersection> $allMountableAreaIntersections */
        $allMountableAreaIntersections = collect();
        foreach ($latLngA->getFloor()->mountableareas as $mountableArea) {
            // Determine from which mountable area the location started
            if ($startMountableArea === null && $mountableArea->contains($this->coordinatesService, $latLngA)) {
                $startMountableArea = $mountableArea;
            }

            $intersections = $mountableArea->getIntersections(
                $this->coordinatesService,
                $latLngA,
                $latLngB
            );

            if (empty($intersections)) {
                continue;
            }

            /** @var Collection<MountableAreaIntersection> $mountableAreaIntersections */
            $mountableAreaIntersections = collect();
            foreach ($intersections as $intersection) {
                $mountableAreaIntersections->push(
                    new MountableAreaIntersection($mountableArea, new LatLng($intersection['lat'], $intersection['lng'], $latLngA->getFloor()))
                );
            }

            // Add them to the general list
            $allMountableAreaIntersections = $allMountableAreaIntersections->merge($mountableAreaIntersections);
        }

        // If we did NOT find any intersections, it means that we either stayed mounted (inside zone) or can't mount (no zone)
        if ($allMountableAreaIntersections->isEmpty()) {
            if ($startMountableArea !== null) {
                // We are mounted 100% of the way, with 1 cast to mount up
                return [[['factor' => 1, 'speed' => $startMountableArea->getSpeedOrDefault()]], 1];
            } else {
                // We are mounted 0% of the way and will keep walking
                return [[], 0];
            }
        }

        // Now that we have a (randomly sorted) list of mountable areas and intersections, we need to sort the list and
        // then determine if an intersection causes a mount up, or a dismount
        /** @var Collection<MountableAreaIntersection> $allMountableAreaIntersections */
        $allMountableAreaIntersections = $allMountableAreaIntersections->sortBy(
            fn(MountableAreaIntersection $foundIntersection) => $this->coordinatesService->distanceBetweenPoints(
                $latLngA->getLng(), $foundIntersection->getLatLng()->getLng(),
                $latLngA->getLat(), $foundIntersection->getLatLng()->getLat(),
            ))->values();

        $totalDistance = $this->coordinatesService->distanceBetweenPoints(
            $latLngA->getLng(), $latLngB->getLng(),
            $latLngA->getLat(), $latLngB->getLat(),
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
                $previousLatLng->getLng(), $mountableAreaIntersection->getLatLng()->getLng(),
                $previousLatLng->getLat(), $mountableAreaIntersection->getLatLng()->getLat(),
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
            else if ($index !== $allMountableAreaIntersectionsCount - 1) {
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
                $latLngB->getLng(), $lastMountableAreaIntersection->getLatLng()->getLng(),
                $latLngB->getLat(), $lastMountableAreaIntersection->getLatLng()->getLat(),
            );

            $factorsAndSpeeds[] = [
                'factor' => $distanceToPack / $totalDistance,
                'speed'  => $lastMountableAreaIntersection->getMountableArea()->getSpeedOrDefault(),
            ];
        }

        return [$factorsAndSpeeds, $mountCasts];
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
                sprintf('mark_duration=%d,', $this->options->thundering_clear_seconds) : ''
        );

        $enemyStrings = [];
        foreach ($this->raidEventPullEnemies as $raidEventPullEnemy) {
            $enemyStrings[] = $raidEventPullEnemy->toString();
        }

        $result .= implode('|', $enemyStrings);

        return $result;
    }
}
