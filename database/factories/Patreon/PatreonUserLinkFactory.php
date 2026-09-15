<?php

namespace Database\Factories\Patreon;

use App\Models\Patreon\PatreonUserLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatreonUserLink>
 */
class PatreonUserLinkFactory extends Factory
{
    protected $model = PatreonUserLink::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'email'         => $this->faker->unique()->safeEmail(),
            'scope'         => 'identity identity[email] identity.memberships campaigns',
            'access_token'  => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'version'       => 2,
            'expires_at'    => now()->addMonth(),
        ];
    }

    /** A link whose benefits were handed out through the admin pages rather than paid for. */
    public function manuallyGranted(): self
    {
        return $this->state(fn(array $attributes) => [
            'refresh_token' => PatreonUserLink::PERMANENT_TOKEN,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
