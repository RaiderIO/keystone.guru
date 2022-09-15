<?php

namespace App\Logic\SimulationCraft;

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
     * @return int
     */
    public function getPullIndex(): int
    {
        return $this->pullIndex;
    }

    /**
     * @return bool
     */
    public function isBloodLust(): bool
    {
        return $this->bloodLust;
    }

    /**
     * @param bool $bloodLust
     * @return RaidEventPull
     */
    public function setBloodLust(bool $bloodLust): RaidEventPull
    {
        $this->bloodLust = $bloodLust;
        return $this;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     * @return RaidEventPull
     */
    public function setDelay(int $delay): RaidEventPull
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getRaidEventPullEnemies(): Collection
    {
        return $this->raidEventPullEnemies;
    }

    /**
     * @inheritDoc
     */
    public function calculateRaidEventPullEnemies(KillZone $killZone, array $previousKillLocation, Floor $previousKillFloor): RaidEventPullInterface
    {
        $this->pullIndex            = $killZone->index;
        $this->raidEventPullEnemies = collect();

        foreach ($killZone->enemies->groupBy('npc_id') as $npcId => $enemies) {
            /** @var Collection|Enemy[] $enemies */
            $enemyIndex = 1;
            foreach ($enemies as $enemy) {
                $this->addEnemy($enemy, $enemyIndex++);
            }
        }

        $this->delay = $this->calculateDelay($killZone, $previousKillLocation, $previousKillFloor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function calculateDelay(KillZone $killZone, array $previousKillLocation, Floor $previousKillFloor): float
    {
        // Convert the location of the pack to in-game location, and then determine the delay according to floor x-y
        $killLocation = $killZone->getKillLocation();
        $floor        = $killZone->getDominantFloor();

        $ingameCoordinatesPreviousKillLocation = $previousKillFloor->calculateIngameLocationForMapLocation($previousKillLocation['lat'], $previousKillLocation['lng']);
        $ingameCoordinatesKillLocation         = $floor->calculateIngameLocationForMapLocation($killLocation['lat'], $killLocation['lng']);

        // On the same floor it's easy - just calculate the distance between and then subtract any mounted speed
        if ($previousKillFloor->id === $floor->id) {
            $ingameDistanceToNewKillZone = MathUtils::distanceBetweenPoints(
                    $ingameCoordinatesPreviousKillLocation['x'], $ingameCoordinatesKillLocation['x'],
                    $ingameCoordinatesPreviousKillLocation['y'], $ingameCoordinatesKillLocation['y']
                ) - $this->options->ranged_pull_compensation_yards;

            [$mountFactor, $mountCasts] = $this->calculateMountedFactorAndMountCastsBetweenPoints(
                $floor,
                $previousKillLocation,
                $killLocation
            );

            $delayMounted = $this->calculateDelayForDistanceMounted(
                $ingameDistanceToNewKillZone * $mountFactor
            );
            $delayOnFoot  = $this->calculateDelayForDistanceOnFoot(
                $ingameDistanceToNewKillZone * (1 - $mountFactor)
            );

            // If we utilized the mount, check if we are going to be quicker by not mounting (due to the $mountCasts taking time)
            if ($mountFactor > 0) {
                $delayOnFootWithoutMounting = $this->calculateDelayForDistanceOnFoot(
                    $ingameDistanceToNewKillZone
                );
                if ($delayOnFootWithoutMounting < $delayMounted + $delayOnFoot) {
                    $mountCasts   = 0;
                    $delayMounted = 0;
                    $delayOnFoot  = $delayOnFootWithoutMounting;
                }
            }

            // Calculate the final result
            $result = $delayMounted + $delayOnFoot + $this->calculateDelayForMountCasts($mountCasts);
        } else {
            // Different floors are a bit tricky - we need to find the closest floor switch marker, calculate the distance to that
            // and then from that floor marker on the other side, calculate the distance to the pull. Add all up and you got the delay you're looking for
            $totalIngameDistance =
                $this->calculateDistanceBetweenKillLocationAndClosestFloorSwitchMarker($previousKillLocation, $ingameCoordinatesPreviousKillLocation, $previousKillFloor, $floor) +
                $this->calculateDistanceBetweenKillLocationAndClosestFloorSwitchMarker($killLocation, $ingameCoordinatesKillLocation, $floor, $previousKillFloor);

            $result = $this->calculateDelayForDistanceOnFoot($totalIngameDistance - $this->options->ranged_pull_compensation_yards);
        }

        return $result;
    }


    /**
     * @param array $killLocation
     * @param array $ingameCoordinatesKillLocation
     * @param Floor $floor
     * @param Floor $targetFloor
     * @return float
     */
    private function calculateDistanceBetweenKillLocationAndClosestFloorSwitchMarker(array $killLocation, array $ingameCoordinatesKillLocation, Floor $floor, Floor $targetFloor): float
    {
        $result = 0;

        $previousKillFloorClosestDungeonFloorSwitchMarker = $floor->findClosestFloorSwitchMarker(
            $killLocation['lat'],
            $killLocation['lng'],
            $targetFloor->id
        );

        if ($previousKillFloorClosestDungeonFloorSwitchMarker !== null) {
            // Calculate the in-game coordinates for the floor switch marker
            $ingameCoordinatesDungeonFloorSwitchMarker = $floor->calculateIngameLocationForMapLocation(
                $previousKillFloorClosestDungeonFloorSwitchMarker->lat,
                $previousKillFloorClosestDungeonFloorSwitchMarker->lng
            );

            // From the previous kill location to the floor switch
            $result = MathUtils::distanceBetweenPoints(
                $ingameCoordinatesKillLocation['x'], $ingameCoordinatesDungeonFloorSwitchMarker['x'],
                $ingameCoordinatesKillLocation['y'], $ingameCoordinatesDungeonFloorSwitchMarker['y']
            );
        } else {
            dd(sprintf('There is no floor switch marker from %d to %d!', $floor->id, $targetFloor->id));
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
     * @param Floor $floor
     * @param array $previousKillLocation
     * @param array $killLocation
     * @return array{mountedFactor: float, mountCasts: int}
     */
    public function calculateMountedFactorAndMountCastsBetweenPoints(
        Floor $floor,
        array $previousKillLocation,
        array $killLocation): array
    {
        // 0% of the time on mounts, 0 mount casts
        if (!$this->options->use_mounts) {
            return [0, 0];
        }

        /** @var MountableArea|null $startMountableArea The mountable area that we started in - can be null if not started inside a mountable area */
        $startMountableArea = null;

        // Construct a list of intersections from mountable areas.
        $foundIntersections = collect();
        foreach ($floor->mountableareas as $mountableArea) {
            // Determine from which mountable area the location started
            if ($startMountableArea === null && $mountableArea->contains($previousKillLocation)) {
                $startMountableArea = $mountableArea;
            }

            $mountableAreaIntersections = $mountableArea->getIntersections(
                $previousKillLocation,
                $killLocation
            );

            // If we found some intersections, add them to the general list
            if (!empty($mountableAreaIntersections)) {
                $foundIntersections = $foundIntersections->merge($mountableAreaIntersections);
            }
        }

        // If we did NOT find any intersections, it means that we either stayed mounted (inside zone) or can't mount (no zone)
        if ($foundIntersections->isEmpty()) {
            if ($startMountableArea !== null) {
                // We are mounted 100% of the way, with 1 cast to mount up
                return [100, 1];
            } else {
                // We are mounted 0% of the way and will keep walking
                return [0, 0];
            }
        }

        // Now that we have a (randomly sorted) list of mountable areas and intersections, we need to sort the list and
        // then determine if an intersection causes a mount up, or a dismount
        $foundIntersections = $foundIntersections->sortBy(function (array $foundIntersection) use ($previousKillLocation) {
            return MathUtils::distanceBetweenPoints(
                $previousKillLocation['lng'], $foundIntersection['lng'],
                $previousKillLocation['lat'], $foundIntersection['lat'],
            );
        })->values();

        // Compile a list of all locations we know of, and then determine how much %-age is mounted, and how much %-age is on-foot
        $allLatLngs    = collect($foundIntersections)->merge([$killLocation]);
        $totalDistance = MathUtils::distanceBetweenPoints(
            $previousKillLocation['lng'], $killLocation['lng'],
            $previousKillLocation['lat'], $killLocation['lat'],
        );

        $totalDistanceOnFoot = $totalDistanceMounted = 0;
        // If we are currently mounted (as in, we finished killing the previous pack, now going to the current pack)
        // Then we also initialize mountCasts to 1, since when we're done killing, we need to cast mount which is 1.5s
        $isMounted  = $startMountableArea !== null;
        $mountCasts = (int)$isMounted;

        // We start at where we're at now
        $previousLatLng  = $previousKillLocation;
        $allLatLngsCount = $allLatLngs->count();

        foreach ($allLatLngs as $index => $latLng) {
            $distanceBetweenLatLngs = MathUtils::distanceBetweenPoints(
                $previousLatLng['lng'], $latLng['lng'],
                $previousLatLng['lat'], $latLng['lat'],
            );

            // Add the distance to the appropriate totalDistance variable
            if ($isMounted) {
                $totalDistanceMounted += $distanceBetweenLatLngs;
            } else {
                $totalDistanceOnFoot += $distanceBetweenLatLngs;
            }

            // Since we encountered a new edge of the zone, we're now either mounting or dismounting
            // But not the last point - that's when we reach the enemy and start attacking.
            $isMounted = !$isMounted;
            if ($isMounted && $index !== $allLatLngsCount - 1) {
                $mountCasts++;
            }
        }

        return [$totalDistanceMounted / $totalDistance, $mountCasts];
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
     * @param float $ingameDistance
     * @return float
     */
    public function calculateDelayForDistanceMounted(float $ingameDistance): float
    {
        return max(0, $ingameDistance) / config('keystoneguru.character.mounted_movement_speed_yards_second');
    }

    /**
     * @param int $mountCasts
     * @return float
     */
    public function calculateDelayForMountCasts(int $mountCasts): float
    {
        return max(0, $mountCasts) / config('keystoneguru.character.mount_cast_time_seconds');
    }


    /**
     * @return string
     */
    public function toString(): string
    {
        $result = sprintf(
            'raid_events+=/pull,pull=%02d,bloodlust=%d,delay=%03d,enemies=',
            $this->pullIndex,
            (int)$this->bloodLust,
            $this->delay
        );

        $enemyStrings = [];
        foreach ($this->raidEventPullEnemies as $raidEventPullEnemy) {
            $enemyStrings[] = $raidEventPullEnemy->toString();
        }

        $result .= implode('|', $enemyStrings);
        return $result;
    }
}
