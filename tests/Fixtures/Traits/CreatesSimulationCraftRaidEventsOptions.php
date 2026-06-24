<?php

namespace Tests\Fixtures\Traits;

use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesSimulationCraftRaidEventsOptions
{
    /**
     * @param array<string, mixed>|null $attributes
     */
    public function createSimulationCraftRaidEventsOptions(?array $attributes = null): SimulationCraftRaidEventsOptions
    {
        return new SimulationCraftRaidEventsOptions($attributes ?? $this->getSimulationCraftRaidEventsOptionsDefaultAttributes());
    }

    /**
     * @return array<string, int>
     */
    public function getSimulationCraftRaidEventsOptionsDefaultAttributes(): array
    {
        return [
            'id' => 123123,
        ];
    }
}
