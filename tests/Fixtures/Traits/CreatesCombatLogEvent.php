<?php

namespace Tests\Fixtures\Traits;

use App\Models\CombatLog\CombatLogEvent;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesCombatLogEvent
{
    public function createCombatLogEvent(?array $attributes = null): CombatLogEvent
    {
        return new CombatLogEvent($attributes ?? $this->getCombatLogEventDefaultAttributes());
    }

    /**
     * @return Collection<CombatLogEvent>
     */
    public function createCombatLogEvents(int $count): Collection
    {
        $result = collect();
        for ($i = 0; $i < $count; $i++) {
            $result->push($this->createCombatLogEvent());
        }

        return $result;
    }

    /**
     * @return int[]
     */
    public function getCombatLogEventDefaultAttributes(): array
    {
        return [
            'id'    => 123123,
            'pos_x' => rand(0, 100),
            'pos_y' => rand(0, 100),
        ];
    }
}
