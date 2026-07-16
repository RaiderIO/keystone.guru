<?php

namespace Database\Factories;

use App\Models\BannedIpAddress;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BannedIpAddress>
 */
class BannedIpAddressFactory extends Factory
{
    protected $model = BannedIpAddress::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'ip_address' => $this->faker->ipv4(),
            'reason'     => $this->faker->sentence(),
            'expires_at' => null,
            'created_by' => null,
        ];
    }

    public function expired(): self
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function expiresAt(DateTimeInterface $expiresAt): self
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => $expiresAt,
        ]);
    }
}
