<?php

namespace App\Logic\SimulationCraft;

use App\Logic\Utils\MathUtils;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\KillZone;
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
                $this->addEnemy($enemy, $enemyIndex);
                $enemyIndex++;
            }
        }

        // Convert the location of the pack to in-game location, and then determine the delay according to floor x-y
        $killLocation = $killZone->getKillLocation();
        $floor        = $killZone->getDominantFloor();

        $ingameCoordinatesPreviousKillLocation = $previousKillFloor->calculateIngameLocationForMapLocation($previousKillLocation['lat'], $previousKillLocation['lng']);
        $ingameCoordinatesKillLocation         = $floor->calculateIngameLocationForMapLocation($killLocation['lat'], $killLocation['lng']);

        // On the same floor it's easy - just calculate the distance between
        if ($previousKillFloor->id === $floor->id) {
            $ingameDistanceToNewKillZone = MathUtils::distanceBetweenPoints(
                $ingameCoordinatesPreviousKillLocation['x'], $ingameCoordinatesKillLocation['x'],
                $ingameCoordinatesPreviousKillLocation['y'], $ingameCoordinatesKillLocation['y']
            );

            $this->delay = $this->calculateDelayForDistance($ingameDistanceToNewKillZone);
        } else {
            // Different floors are a bit tricky - we need to find the closest floor switch marker, calculate the distance to that
            // and then from that floor marker on the other side, calculate the distance to the pull. Add all up and you got the delay you're looking for
            $totalIngameDistance =
                $this->calculateDistanceBetweenKillLocationAndClosestFloorSwitchMarker($previousKillLocation, $ingameCoordinatesPreviousKillLocation, $previousKillFloor, $floor) +
                $this->calculateDistanceBetweenKillLocationAndClosestFloorSwitchMarker($killLocation, $ingameCoordinatesKillLocation, $floor, $previousKillFloor);

            $this->delay = $this->calculateDelayForDistance($totalIngameDistance);
        }

        return $this;
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
     * @param float $ingameDistance
     * @return float
     */
    public function calculateDelayForDistance(float $ingameDistance): float
    {
        // https://wowpedia.fandom.com/wiki/Movement
        return $ingameDistance / 7;
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
