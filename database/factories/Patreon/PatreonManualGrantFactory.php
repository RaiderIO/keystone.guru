<?php

namespace Database\Factories\Patreon;

use App\Models\Patreon\PatreonManualGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatreonManualGrant>
 */
class PatreonManualGrantFactory extends Factory
{
    /**
     * Define the model's default state: an active grant, issued by the seeded admin.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'granted_by_user_id' => 1,
            'reason'             => 'Compensation for a tier that never applied',
            'revoked_at'         => null,
            'revoked_by_user_id' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn(array $attributes) => [
            'revoked_at'         => now(),
            'revoked_by_user_id' => 1,
        ]);
    }
}
