<?php

namespace Database\Factories\Patreon;

use App\Models\Patreon\PatreonSyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatreonSyncRun>
 */
class PatreonSyncRunFactory extends Factory
{
    protected $model = PatreonSyncRun::class;

    /**
     * Define the model's default state - a healthy run that fetched the whole campaign.
     */
    public function definition(): array
    {
        $membersFetched = $this->faker->numberBetween(50, 500);

        return [
            'started_at'               => now()->subMinutes(2),
            'finished_at'              => now(),
            'pages_fetched'            => (int)ceil($membersFetched / 100),
            'members_fetched'          => $membersFetched,
            'truncated'                => false,
            'members_applied'          => $membersFetched,
            'members_not_linked'       => 0,
            'members_unknown_benefits' => 0,
            'members_unknown_tiers'    => 0,
            'members_failed'           => 0,
            'successful'               => true,
            'failure_reason'           => null,
        ];
    }

    /** A run whose member fetch gave up part way through - the failure mode of #4373. */
    public function truncated(): self
    {
        return $this->state(fn(array $attributes) => [
            'truncated'      => true,
            'successful'     => false,
            'failure_reason' => 'Unable to load the campaign members',
        ]);
    }
}
