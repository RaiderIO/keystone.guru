<?php

namespace Tests\Unit\Traits;

use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesSimulationCraftRaidEventsOptions
{
    /**
     * @param array|null $attributes
     * @return SimulationCraftRaidEventsOptions
     */
    public function createSimulationCraftRaidEventsOptions(?array $attributes = null): SimulationCraftRaidEventsOptions
    {
        return new SimulationCraftRaidEventsOptions($attributes ?? $this->getSimulationCraftRaidEventsOptionsDefaultAttributes());
    }

    /**
     * @return int[]
     */
    public function getSimulationCraftRaidEventsOptionsDefaultAttributes(): array
    {
        return [
            'id' => 123123,
        ];
    }
}
