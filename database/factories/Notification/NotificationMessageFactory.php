<?php

namespace Database\Factories\Notification;

use App\Models\Notification\Enums\NotificationAudience;
use App\Models\Notification\Enums\NotificationType;
use App\Models\Notification\NotificationMessage;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationMessage>
 */
class NotificationMessageFactory extends Factory
{
    protected $model = NotificationMessage::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'type'                 => NotificationType::AdminCustom,
            'audience'             => NotificationAudience::User,
            'body'                 => $this->faker->sentence(),
            'params'               => null,
            'url'                  => null,
            'game_version_id'      => null,
            'bypasses_preferences' => false,
            'created_by'           => 1, // seeded admin user
            'dedupe_key'           => null,
            'expires_at'           => null,
        ];
    }

    public function globalAudience(): self
    {
        return $this->state(fn(array $attributes) => [
            'audience' => NotificationAudience::Global,
        ]);
    }

    public function forGameVersion(int $gameVersionId): self
    {
        return $this->state(fn(array $attributes) => [
            'game_version_id' => $gameVersionId,
        ]);
    }

    public function expiresAt(?DateTimeInterface $expiresAt): self
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => $expiresAt,
        ]);
    }

    public function dedupeKey(string $dedupeKey): self
    {
        return $this->state(fn(array $attributes) => [
            'dedupe_key' => $dedupeKey,
        ]);
    }
}
