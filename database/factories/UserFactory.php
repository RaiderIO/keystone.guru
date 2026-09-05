<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            // This was fake()->unique()->safeEmail(), which is unique only within a single faker
            // instance while safeEmail() draws from a small firstname.lastname@example.tld pool. The
            // test database is seeded once and kept, so users left behind by earlier runs accumulate
            // in it and eventually collide with a fresh one - a users_email_unique violation in a
            // test that has nothing to do with users. The random suffix makes the address unique
            // against the database rather than within the run, which is what actually matters here.
            'email'          => sprintf('%s_%s@example.com', fake()->userName(), Str::random(8)),
            'password'       => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Both are NOT NULL with no database default, so leaving them out only works while the
            // connection is non-strict - the strict `migrate` connection rejects the insert (#4498).
            // They are real data rather than a flag, so they belong here and not in a column default.
            'public_key' => User::generateRandomPublicKey(),
            'echo_color' => randomHexColor(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
