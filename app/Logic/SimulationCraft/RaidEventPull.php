<?php

namespace App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\Models\MountableAreaIntersection;
use App\Logic\Utils\MathUtils;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\MountableArea;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Support\Collection;

class RaidEventPull implements RaidEventPullInterface, RaidEventOutputInterface
{
    /** @var SimulationCraftRaidEventsOptions */
    private SimulationCraftRaidEventsOptions $options;

    /** @var int */
    private int $pullIndex;

    /** @var bool */
    private bool $bloodLust = false;

    /** @var int */
    private int $delay = 0;

    /** @var Collection|RaidEventPullEnemy[] */
    private Collection $raidEventPullEnemies;

    /**
     * @param SimulationCraftRaidEventsOptions $options
     */
    public function __construct(SimulationCraftRaidEventsOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, array $previousKillLocation, Floor $previousKillFloor): RaidEventPullInterface
    {
        // If bloodlust is enabled, and if this pull has bloodlust active on it..
        $this->bloodLust = $this->options->bloodlust && in_array($killZone->id, explode(',', $this->options->simulate_bloodlust_per_pull));

        $this->pullIndex            = $killZone->index;
        $this->raidEventPullEnemies = collect();

        foreach ($killZone->getEnemies()->groupBy('npc_id') as $npcId => $enemies) {
            /** @var Collection|Enemy[] $enemies */
            $enemyIndex = 1;
            foreach ($enemies as $enemy) {
                $this->addEnemy($enemy, $enemyIndex++);
            }
        }

        // Do not calculate a delay for an empty pull as it's impossible to determine a location for such a pull
        $this->delay = $this->raidEventPullEnemies->isEmpty() ? 0 : $this->calculateDelay($killZone, $previousKillLocation, $previousKillFloor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function calculateDelay(KillZone $killZone, array $previousKillLocation, Floor $previousKillFloor): float
    {
        // Convert the location of the pack to in-game location, and then determine the delay according to floor x-y
        $killLocation = $killZone->getKillLocation();
        // No enemies killed, no location, no pull, no delay
        if ($killLocation === null) {
            return 0;
        }

        $floor = $killZone->getDominantFloor();

        // On the same floor it's easy - just calculate the distance between and then subtract any mounted speed
        if ($previousKillFloor->id === $floor->id) {
            $result = $this->calculateDelayBetweenPoints($floor, $previousKillLocation, $killLocation);
        } else {
            // Different floors are a bit tricky - we need to find the closest floor switch marker, calculate the distance to that
            // and then from that floor marker on the other side, calculate the distance to the pull. Add all up and you got the delay you're looking for
            $result = $this->calculateDelayBetweenPointsOnDifferentFloors($previousKillFloor, $floor, $previousKillLocation, $killLocation);
        }

        return $result;
    }

    /**
     * @param Floor $floor
     * @param array{lat: float, lng: float} $pointA
     * @param array{lat: float, lng: float} $pointB
     * @return float
     */
    public function calculateDelayBetweenPoints(Floor $floor, array $pointA, array $pointB): float
    {
        [$mountFactorsAndSpeeds, $mountCasts] = $this->calculateMountedFactorAndMountCastsBetweenPoints(
            $floor,
            $pointA,
            $pointB
        );

        $pointAIngameCoordinates = $floor->calculateIngameLocationForMapLocation($pointA['lat'], $pointA['lng']);
        $pointBIngameCoordinates = $floor->calculateIngameLocationForMapLocation($pointB['lat'], $pointB['lng']);

        $ingameDistanceToPointB = MathUtils::distanceBetweenPoints(
                $pointAIngameCoordinates['x'], $pointBIngameCoordinates['x'],
                $pointAIngameCoordinates['y'], $pointBIngameCoordinates['y']
            ) - $this->options->ranged_pull_compensation_yards;

        $delayMounted       = 0;
        $totalMountedFactor = 0;

        foreach ($mountFactorsAndSpeeds as $mountFactorAndSpeed) {
            if (!is_array($mountFactorAndSpeed)) {
                dd($mountFactorsAndSpeeds);
            }
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

    /**
     * @param Floor $pointAFloor
     * @param Floor $pointBFloor
     * @param array{lat: float, lng: float} $pointA
     * @param array{lat: float, lng: float} $pointB
     * @return float
     */
    public function calculateDelayBetweenPointsOnDifferentFloors(Floor $pointAFloor, Floor $pointBFloor, array $pointA, array $pointB): float
    {
        return $this->calculateDistanceBetweenPointAndClosestFloorSwitchMarker($pointAFloor, $pointBFloor, $pointA) +
            $this->calculateDistanceBetweenPointAndClosestFloorSwitchMarker($pointBFloor, $pointAFloor, $pointB);
    }


    /**
     * @param Floor $floor
     * @param Floor $targetFloor
     * @param array{lat: float, lng: float} $point
     * @return float
     */
    private function calculateDistanceBetweenPointAndClosestFloorSwitchMarker(Floor $floor, Floor $targetFloor, array $point): float
    {
        $previousKillFloorClosestDungeonFloorSwitchMarker = $floor->findClosestFloorSwitchMarker(
            $point['lat'],
            $point['lng'],
            $targetFloor->id
        );

        if ($previousKillFloorClosestDungeonFloorSwitchMarker !== null) {
            // Now that we know the location of the floor switch marker, we can
            $result = $this->calculateDelayBetweenPoints($floor, $point, [
                'lat' => $previousKillFloorClosestDungeonFloorSwitchMarker->lat,
                'lng' => $previousKillFloorClosestDungeonFloorSwitchMarker->lng,
            ]);
        } else {
            // @TODO #1621
            logger()->warning(sprintf('There is no floor switch marker from %d to %d!', $floor->id, $targetFloor->id));
            $result = 20;
        }

        return $result;
    }

    /**
     * @inheritDoc
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
     * @param Floor $floor
     * @param array{lat: float, lng: float} $pointA
     * @param array{lat: float, lng: float} $pointB
     * @return array{array{factor: float, speed: int}, mountCasts: int}
     */
    public function calculateMountedFactorAndMountCastsBetweenPoints(
        Floor $floor,
        array $pointA,
        array $pointB): array
    {
        // 0% of the time on mounts, 0 mount casts
        if (!$this->options->use_mounts) {
            return [[], 0];
        }

        /** @var MountableArea|null $startMountableArea The mountable area that we started in - can be null if not started inside a mountable area */
        $startMountableArea = null;

        // Construct a list of intersections from mountable areas.
        /** @var MountableAreaIntersection[]|Collection $allMountableAreaIntersections */
        $allMountableAreaIntersections = collect();
        foreach ($floor->mountableareas as $mountableArea) {
            // Determine from which mountable area the location started
            if ($startMountableArea === null && $mountableArea->contains($pointA)) {
                $startMountableArea = $mountableArea;
            }

            $intersections = $mountableArea->getIntersections(
                $pointA,
                $pointB
            );

            if (empty($intersections)) {
                continue;
            }

            /** @var MountableAreaIntersection[]|Collection $mountableAreaIntersections */
            $mountableAreaIntersections = collect();
            foreach ($intersections as $intersection) {
                $mountableAreaIntersections->push(new MountableAreaIntersection($mountableArea, $intersection['lat'], $intersection['lng']));
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
        /** @var MountableAreaIntersection[]|Collection $allMountableAreaIntersections */
        $allMountableAreaIntersections = $allMountableAreaIntersections->sortBy(function (MountableAreaIntersection $foundIntersection) use ($pointA) {
            return MathUtils::distanceBetweenPoints(
                $pointA['lng'], $foundIntersection->getLng(),
                $pointA['lat'], $foundIntersection->getLat(),
            );
        })->values();

        $totalDistance = MathUtils::distanceBetweenPoints(
            $pointA['lng'], $pointB['lng'],
            $pointA['lat'], $pointB['lat'],
        );

        // If we are currently mounted (as in, we finished killing the previous pack, now going to the current pack)
        // Then we also initialize mountCasts to 1, since when we're done killing, we need to cast mount which is 1.5s
        $isMounted  = $startMountableArea !== null;
        $mountCasts = (int)$isMounted;

        // We start at where we're at now
        $previousLatLng                     = $pointA;
        $allMountableAreaIntersectionsCount = $allMountableAreaIntersections->count();

        $factorsAndSpeeds = [];
        foreach ($allMountableAreaIntersections as $index => $mountableAreaIntersection) {
            $distanceBetweenLatLngs = MathUtils::distanceBetweenPoints(
                $previousLatLng['lng'], $mountableAreaIntersection->getLng(),
                $previousLatLng['lat'], $mountableAreaIntersection->getLat(),
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
            $previousLatLng = ['lat' => $mountableAreaIntersection->getLat(), 'lng' => $mountableAreaIntersection->getLng()];
        }

        // One last thing - if we are mounted at this point, we need to add another entry since we reached our destination
        // while mounted. We shouldn't forget to add this last travel distance as well
        if ($isMounted) {
            /** @var MountableAreaIntersection $lastMountableAreaIntersection */
            $lastMountableAreaIntersection = $allMountableAreaIntersections->last();

            $distanceToPack = MathUtils::distanceBetweenPoints(
                $pointB['lng'], $lastMountableAreaIntersection->getLng(),
                $pointB['lat'], $lastMountableAreaIntersection->getLat(),
            );

            $factorsAndSpeeds[] = [
                'factor' => $distanceToPack / $totalDistance,
                'speed'  => $lastMountableAreaIntersection->getMountableArea()->getSpeedOrDefault(),
            ];
        }

        return [$factorsAndSpeeds, $mountCasts];
    }

    /**
     * @param float $ingameDistance
     * @return float
     */
    public function calculateDelayForDistanceOnFoot(float $ingameDistance): float
    {
        return max(0, $ingameDistance) / config('keystoneguru.character.default_movement_speed_yards_second');
    }

    /**
     * @param int $mountCasts
     * @return float
     */
    public function calculateDelayForMountCasts(int $mountCasts): float
    {
        return (max(0, $mountCasts) * config('keystoneguru.character.mount_cast_time_seconds'));
    }

    /**
     * @param float $ingameDistance
     * @param float $factor
     * @param int $speed
     * @return float
     */
    public function calculateDelayForDistanceMounted(float $ingameDistance, float $factor, int $speed): float
    {
        return max(0, $ingameDistance * $factor) / $speed;
    }


    /**
     * @return string
     */
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
