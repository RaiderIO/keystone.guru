<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Each test drops the slug column and re-runs the backfill over the whole users table, so the created users'
 * names carry a prefix no seeded user shares.
 */
#[Group('User')]
#[Group('AddSlugToUsersTableMigration')]
final class AddSlugToUsersTableTest extends PublicTestCase
{
    #[Test]
    public function up_givenUsersSharingABaseSlug_givesTheOldestUserThePlainSlug(): void
    {
        // Arrange
        $users = $this->createUsers(['Slugbackfill Foo', 'slugbackfill-foo-2', 'SLUGBACKFILL  FOO!']);

        try {
            // Act
            $this->rerunMigration();

            // Assert
            $this->assertSame(
                ['slugbackfill-foo', 'slugbackfill-foo-2', 'slugbackfill-foo-3'],
                $this->slugsOf($users),
            );
        } finally {
            $this->cleanUp($users);
        }
    }

    #[Test]
    public function up_givenNamesEqualOnlyUnderTheColumnCollation_suffixesTheNewerUser(): void
    {
        // Arrange
        $users = $this->createUsers(['Slugbackfill Café', 'slugbackfill cafe', 'Slugbackfill Straße', 'slugbackfill strasse']);

        try {
            // Act
            $this->rerunMigration();

            // Assert
            $this->assertSame(
                ['slugbackfill-café', 'slugbackfill-cafe-2', 'slugbackfill-straße', 'slugbackfill-strasse-2'],
                $this->slugsOf($users),
            );
        } finally {
            $this->cleanUp($users);
        }
    }

    #[Test]
    public function up_givenANameWithoutSlugCharacters_fallsBackToAUserSlug(): void
    {
        // Arrange
        $users = $this->createUsers(['!!!', '???']);

        try {
            // Act
            $this->rerunMigration();

            // Assert
            [$firstSlug, $secondSlug] = $this->slugsOf($users);
            $this->assertMatchesRegularExpression('/^user(-\d+)?$/', $firstSlug);
            $this->assertMatchesRegularExpression('/^user-\d+$/', $secondSlug);
            $this->assertNotSame($firstSlug, $secondSlug);
        } finally {
            $this->cleanUp($users);
        }
    }

    #[Test]
    public function up_givenTheSeededUsers_givesEveryUserAUniqueSlug(): void
    {
        // Arrange
        $users = $this->createUsers(['Slugbackfill Bar', 'Slugbackfill Bar']);

        try {
            // Act
            $this->rerunMigration();

            // Assert
            $this->assertSame(0, User::query()->whereNull('slug')->count());
            $this->assertSame(
                User::query()->count(),
                User::query()->distinct()->count('slug'),
            );
        } finally {
            $this->cleanUp($users);
        }
    }

    /**
     * @param  list<string> $names
     * @return list<User>
     */
    private function createUsers(array $names): array
    {
        return array_map(static fn(string $name) => User::factory()->create(['name' => $name]), $names);
    }

    private function rerunMigration(): void
    {
        $migration = require base_path('database/migrations/2026_09_24_120000_add_slug_to_users_table.php');

        try {
            $migration->down();
            $migration->up();
        } finally {
            if (!Schema::hasColumn('users', 'slug')) {
                (require base_path('database/migrations/2026_09_24_120000_add_slug_to_users_table.php'))->up();
            }
        }
    }

    /**
     * @param  list<User>        $users
     * @return list<string|null>
     */
    private function slugsOf(array $users): array
    {
        return array_map(
            static fn(User $user) => User::query()->whereKey($user->id)->value('slug'),
            $users,
        );
    }

    /**
     * @param list<User> $users
     */
    private function cleanUp(array $users): void
    {
        User::query()->whereKey(array_map(static fn(User $user) => $user->id, $users))->delete();
    }
}
