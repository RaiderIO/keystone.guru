<?php

namespace Tests\Fixtures\Traits;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesCombatLogEvent
{
    /**
     * @return Collection<CombatLogEvent>
     */
    public function createCombatLogEvents(Dungeon $dungeon, int $count): Collection
    {
        $result = collect();
        for ($i = 0; $i < $count; $i++) {
            /** @var Floor $randomFloor */
            $randomFloor = $dungeon->floors->where('facade', 0)->random(1)->first();

            $result->push($this->createCombatLogEvent(array_merge([
                'id' => random_int(1, 100000),
                'ui_map_id' => $randomFloor->ui_map_id,
            ], $this->getRandomCoordinates($randomFloor))));
        }

        return $result;
    }

    public function createGridAggregationResult(Dungeon $dungeon, int $rowCount): array
    {
        $result = collect();

        foreach ($dungeon->floors()->where('facade', false)->get() as $floor) {
            /** @var Floor $floor */
            $rows = [];

            for ($i = 0; $i < $rowCount; $i++) {
                $coordinates       = $this->getRandomCoordinates($floor);
                $coordinatesString = sprintf('%s,%s', $coordinates['pos_x'], $coordinates['pos_y']);
                // Just in case the coordinates already exist and it randomly fails the test
                if (isset($rows[$coordinatesString])) {
                    $i--;
                } else {
                    $rows[$coordinatesString] = random_int(1, 100);
                }
            }

            $result->put($floor->id, $rows);
        }

        return $result->toArray();
    }

    public function createCombatLogEvent(?array $attributes = null): CombatLogEvent
    {
        return new CombatLogEvent($attributes ?? $this->getCombatLogEventDefaultAttributes());
    }

    /**
     * @return int[]
     */
    public function getCombatLogEventDefaultAttributes(): array
    {
        return [
            'id'        => 123123,
            'ui_map_id' => random_int(1, 200),
            'pos_x'     => random_int(0, 100),
            'pos_y'     => random_int(0, 100),
        ];
    }

    /**
     * @param  Floor $floor
     * @return array
     */
    private function getRandomCoordinates(Floor $floor): array
    {
        $minX = (int)$floor->ingame_min_x;
        $maxX = (int)$floor->ingame_max_x;
        $minY = (int)$floor->ingame_min_y;
        $maxY = (int)$floor->ingame_max_y;

        return [
            // Add 1 so that we're always in between the bounds
            'pos_x' => random_int(min($minX, $maxX), max($minX, $maxX)),
            'pos_y' => random_int(min($minY, $maxY), max($minY, $maxY)),
        ];
    }
}
