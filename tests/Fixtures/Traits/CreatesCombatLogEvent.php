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

            $result->push($this->createCombatLogEvent([
                'id'        => rand(1, 100000),
                'ui_map_id' => $randomFloor->ui_map_id,
                // Add 1 so that we're always in between the bounds
                'pos_x'     => rand((int)$randomFloor->ingame_min_x + 1, (int)$randomFloor->ingame_max_x),
                'pos_y'     => rand((int)$randomFloor->ingame_min_y + 1, (int)$randomFloor->ingame_max_y),
            ]));
        }

        return $result;
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
            'ui_map_id' => rand(1, 200),
            'pos_x'     => rand(0, 100),
            'pos_y'     => rand(0, 100),
        ];
    }
}
