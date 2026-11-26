<?php

namespace App\Logic\SimulationCraft;

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
    private const float SHARED_POINT_FRACTION_EPSILON = 1e-9;

    private int $pullIndex;

    private bool $bloodLust = false;

    private int $delay = 0;

    private float $travelYards = 0.0;

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

        $enemiesByName = $killZone->getEnemies()->groupBy(
            static fn(Enemy $enemy): string => RaidEventPullEnemy::getNpcSlug($enemy->npc),
        );
        foreach ($enemiesByName as $enemies) {
            /** @var Collection<int, Enemy> $enemies */
            $enemyIndex = 1;
            foreach ($enemies as $enemy) {
                $this->addEnemy($enemy, $enemyIndex++);
            }
        }

        // Do not calculate a delay for an empty pull as it's impossible to determine a location for such a pull
        $this->delay       = $this->raidEventPullEnemies->isEmpty() ? 0 : (int)round($this->calculateDelay($path));
        $this->travelYards = $this->raidEventPullEnemies->isEmpty() ? 0.0 : $this->calculateTravelYards($path);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateDelay(array $path): float
    {
        $stretches = array_values(array_filter(
            $this->splitIntoSameFloorStretches($path),
            static fn(array $stretch): bool => count($stretch) >= 2,
        ));

        $lastStretchIndex = count($stretches) - 1;
        $totalDelay       = 0.0;
        foreach ($stretches as $index => $stretch) {
            // Cross-floor transitions (floor-switch marker pairs) have 0 travel time, and the ranged compensation is
            // applied exactly once: on the stretch that walks up to the pull
            $totalDelay += $this->calculateDelayAlongStretch($stretch, $index === $lastStretchIndex);
        }

        return $totalDelay;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateDelayAlongStretch(array $stretch, bool $applyRangedCompensation = true): float
    {
        $this->assertStretchOnOneFloor($stretch);

        if (count($stretch) < 2) {
            return 0;
        }

        [
            $mountFactorsAndSpeeds,
            $mountCasts,
        ] = $this->calculateMountedFactorAndMountCastsAlongStretch($stretch);

        $ingameDistance = array_sum($this->calculateIngameSegmentLengths($stretch));

        if ($applyRangedCompensation) {
            $ingameDistance = max(0, $ingameDistance - $this->options->ranged_pull_compensation_yards);
        }

        $delayMounted       = 0;
        $totalMountedFactor = 0;

        foreach ($mountFactorsAndSpeeds as $mountFactorAndSpeed) {
            /** @var array{factor: float, speed: int} $mountFactorAndSpeed */
            $totalMountedFactor += $mountFactorAndSpeed['factor'];
            $delayMounted += $this->calculateDelayForDistanceMounted(
                $ingameDistance,
                $mountFactorAndSpeed['factor'],
                $mountFactorAndSpeed['speed'],
            );
        }

        $delayMountCasts = $this->calculateDelayForMountCasts($mountCasts);
        $delayOnFoot     = $this->calculateDelayForDistanceOnFoot(
            $ingameDistance * (1 - $totalMountedFactor),
        );

        // If we used the mount, check if we are going to be quicker by not mounting (due to the $mountCasts taking time)
        if ($totalMountedFactor > 0) {
            $delayOnFootWithoutMounting = $this->calculateDelayForDistanceOnFoot(
                $ingameDistance,
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
     * The whole stretch is treated as one journey: a mountable area entered on one segment and left on a later one
     * is one ride with one mount cast, no matter how many segments the stretch has.
     *
     * {@inheritDoc}
     */
    public function calculateMountedFactorAndMountCastsAlongStretch(array $stretch): array
    {
        // 0% of the time on mounts, 0 mount casts
        if (!$this->options->use_mounts || count($stretch) < 2) {
            return [
                [],
                0,
            ];
        }

        $this->assertStretchOnOneFloor($stretch);

        $floor = $stretch[0]->getFloor();

        /** @var MountableArea|null $startMountableArea The mountable area that we started in - can be null if not started inside a mountable area */
        $startMountableArea = null;
        foreach ($floor->mountableAreas as $mountableArea) {
            if ($mountableArea->contains($this->coordinatesService, $stretch[0])) {
                $startMountableArea = $mountableArea;
                break;
            }
        }

        $segmentLengths = $this->calculateIngameSegmentLengths($stretch);
        $totalDistance  = array_sum($segmentLengths);

        // Every crossing of a mountable area's edge, as a distance along the stretch
        /** @var list<array{position: float, mountableArea: MountableArea}> $crossings */
        $crossings               = [];
        $segmentStartPosition    = 0.0;
        $stretchLastSegmentIndex = count($stretch) - 2;
        for ($i = 0; $i <= $stretchLastSegmentIndex; $i++) {
            $latLngA        = $stretch[$i];
            $latLngB        = $stretch[$i + 1];
            $segmentMapSize = $this->coordinatesService->distance($latLngA, $latLngB);

            if ($segmentMapSize > 0) {
                foreach ($floor->mountableAreas as $mountableArea) {
                    foreach ($mountableArea->getIntersections($this->coordinatesService, $latLngA, $latLngB) as $intersection) {
                        $fractionAlongSegment = $this->coordinatesService->distance($latLngA, $intersection) / $segmentMapSize;

                        // Crossings on a point two segments share are decided by probeCrossingAtSharedPoint()
                        if (($i > 0 && $fractionAlongSegment < self::SHARED_POINT_FRACTION_EPSILON) ||
                            ($i < $stretchLastSegmentIndex && $fractionAlongSegment > 1 - self::SHARED_POINT_FRACTION_EPSILON)) {
                            continue;
                        }

                        $crossings[] = [
                            'position'      => $segmentStartPosition + ($fractionAlongSegment * $segmentLengths[$i]),
                            'mountableArea' => $mountableArea,
                        ];
                    }
                }
            }

            $segmentStartPosition += $segmentLengths[$i];

            if ($i < $stretchLastSegmentIndex) {
                foreach ($floor->mountableAreas as $mountableArea) {
                    if ($this->probeCrossingAtSharedPoint($mountableArea, $stretch[$i], $stretch[$i + 1], $stretch[$i + 2])) {
                        $crossings[] = [
                            'position'      => $segmentStartPosition,
                            'mountableArea' => $mountableArea,
                        ];
                    }
                }
            }
        }

        // If we did NOT find any intersections, it means that we either stayed mounted (inside zone) or can't mount (no zone)
        if (empty($crossings)) {
            if ($startMountableArea !== null && $totalDistance > 0) {
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

        usort($crossings, static fn(array $a, array $b): int => $a['position'] <=> $b['position']);

        // If we are currently mounted (as in, we finished killing the previous pack, now going to the current pack)
        // Then we also initialize mountCasts to 1, since when we're done killing, we need to cast mount which is 1.5s
        $isMounted  = $startMountableArea !== null;
        $mountCasts = (int)$isMounted;

        // We start at where we're at now
        $previousPosition = 0.0;
        $crossingsCount   = count($crossings);

        $factorsAndSpeeds = [];
        foreach ($crossings as $index => $crossing) {
            // Add the distance to the appropriate totalDistance variable
            if ($isMounted) {
                $factorsAndSpeeds[] = [
                    'factor' => ($crossing['position'] - $previousPosition) / $totalDistance,
                    'speed'  => $crossing['mountableArea']->getSpeedOrDefault(),
                ];
            }
            // We were not mounted this intersection - mount up for the next one!
            // But only if we have another intersection after this
            elseif ($index !== $crossingsCount - 1) {
                $mountCasts++;
            }

            // Since we encountered a new edge of the zone, we're now either mounting or dismounting
            // But not the last point - that's when we reach the enemy and start attacking.
            $isMounted = !$isMounted;

            // We are now at the last known intersection point since we just travelled there
            $previousPosition = $crossing['position'];
        }

        // One last thing - if we are mounted at this point, we need to add another entry since we reached our destination
        // while mounted. We shouldn't forget to add this last travel distance as well
        if ($isMounted) {
            $factorsAndSpeeds[] = [
                'factor' => ($totalDistance - $previousPosition) / $totalDistance,
                'speed'  => $crossings[$crossingsCount - 1]['mountableArea']->getSpeedOrDefault(),
            ];
        }

        return [
            $factorsAndSpeeds,
            $mountCasts,
        ];
    }

    public function getPullIndex(): int
    {
        return $this->pullIndex;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function getTravelYards(): float
    {
        return $this->travelYards;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTravelYards(array $path): float
    {
        $result = 0.0;
        foreach ($this->splitIntoSameFloorStretches($path) as $stretch) {
            $result += array_sum($this->calculateIngameSegmentLengths($stretch));
        }

        return $result;
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
            'raid_events+=/pull,pull=%02d,bloodlust=%d,delay=%03d,enemies=',
            $this->pullIndex,
            (int)$this->bloodLust,
            $this->delay,
        );

        $enemyStrings = [];
        foreach ($this->raidEventPullEnemies as $raidEventPullEnemy) {
            $enemyStrings[] = $raidEventPullEnemy->toString();
        }

        $result .= implode('|', $enemyStrings);

        return $result;
    }

    /**
     * @param LatLng[] $path
     *
     * @return list<list<LatLng>> Runs of consecutive points on the same floor, in path order.
     */
    private function splitIntoSameFloorStretches(array $path): array
    {
        $stretches = [];
        $current   = [];
        foreach (array_values($path) as $latLng) {
            if (!empty($current) && end($current)->getFloor()?->id !== $latLng->getFloor()?->id) {
                $stretches[] = $current;
                $current     = [];
            }
            $current[] = $latLng;
        }

        if (!empty($current)) {
            $stretches[] = $current;
        }

        return $stretches;
    }

    /**
     * @param list<LatLng> $stretch
     *
     * @return list<float> The in-game length of every segment of the stretch.
     */
    private function calculateIngameSegmentLengths(array $stretch): array
    {
        $ingameLocations = array_map(
            fn(LatLng $latLng) => $this->coordinatesService->calculateIngameLocationForMapLocation($latLng),
            $stretch,
        );

        $segmentLengths = [];
        for ($i = 0; $i < count($ingameLocations) - 1; $i++) {
            $segmentLengths[] = $this->coordinatesService->distanceBetweenPoints(
                $ingameLocations[$i]->getX(),
                $ingameLocations[$i + 1]->getX(),
                $ingameLocations[$i]->getY(),
                $ingameLocations[$i + 1]->getY(),
            );
        }

        return $segmentLengths;
    }

    /**
     * A path that touches a mountable area's edge at a shared point and turns back never crossed it. Compare which side
     * of the area the path is on just before and just after the shared point instead of trusting the intersections there.
     */
    private function probeCrossingAtSharedPoint(
        MountableArea $mountableArea,
        LatLng        $previous,
        LatLng        $shared,
        LatLng        $next,
    ): bool {
        $epsilon = self::SHARED_POINT_FRACTION_EPSILON;
        $before  = new LatLng(
            $shared->getLat() + (($previous->getLat() - $shared->getLat()) * $epsilon),
            $shared->getLng() + (($previous->getLng() - $shared->getLng()) * $epsilon),
            $shared->getFloor(),
        );
        $after = new LatLng(
            $shared->getLat() + (($next->getLat() - $shared->getLat()) * $epsilon),
            $shared->getLng() + (($next->getLng() - $shared->getLng()) * $epsilon),
            $shared->getFloor(),
        );

        return $mountableArea->contains($this->coordinatesService, $before) !==
            $mountableArea->contains($this->coordinatesService, $after);
    }

    /**
     * @param LatLng[] $stretch
     */
    private function assertStretchOnOneFloor(array $stretch): void
    {
        $floorId = reset($stretch) === false ? null : reset($stretch)->getFloor()?->id;
        foreach ($stretch as $latLng) {
            if ($latLng->getFloor()?->id !== $floorId) {
                throw new InvalidArgumentException('Cannot calculate delay along a stretch whose points are on different floors!');
            }
        }
    }
}
