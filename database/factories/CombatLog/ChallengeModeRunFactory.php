<?php

namespace Database\Factories\CombatLog;

use App\Models\CombatLog\ChallengeModeRun;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ChallengeModeRun>
 */
class ChallengeModeRunFactory extends Factory
{
    protected $model = ChallengeModeRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dungeon_id'       => 1,
            'dungeon_route_id' => 1,
            'level'            => $this->faker->numberBetween(2, 20),
            'success'          => true,
            'total_time_ms'    => $this->faker->numberBetween(600000, 2400000),
            'duplicate'        => false,
            // ChallengeModeRun has $timestamps = false, so created_at is never filled in automatically
            'created_at' => Carbon::now(),
        ];
    }

    public function failed(): self
    {
        return $this->state(['success' => false]);
    }

    public function duplicate(): self
    {
        return $this->state(['duplicate' => true]);
    }

    public function createdAt(Carbon $createdAt): self
    {
        return $this->state(['created_at' => $createdAt]);
    }
}
