<?php

namespace Database\Factories\Telemetry;

use App\Models\Telemetry\TelemetryMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelemetryMetric>
 */
class TelemetryMetricFactory extends Factory
{
    /**
     * Define the model's default state: a successful scheduled command run.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'measurement' => TelemetryMetric::MEASUREMENT_SCHEDULER,
            'name'        => 'page-views:prune',
            'tag'         => null,
            'value'       => $this->faker->randomFloat(2, 1, 60000),
            'success'     => true,
            'recorded_at' => now(),
        ];
    }
}
