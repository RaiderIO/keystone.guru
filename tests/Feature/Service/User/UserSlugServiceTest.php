<?php

namespace Tests\Feature\Service\User;

use App\Models\User;
use App\Service\User\UserSlugServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('User')]
final class UserSlugServiceTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('generateBaseSlug_dataProvider')]
    public function generateBaseSlug_givenName_returnsUrlSafeSlug(string $name, string $expectedSlug): void
    {
        // Arrange
        $userSlugService = app(UserSlugServiceInterface::class);

        // Act
        $slug = $userSlugService->generateBaseSlug($name);

        // Assert
        $this->assertSame($expectedSlug, $slug);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function generateBaseSlug_dataProvider(): array
    {
        return [
            'ascii'                      => ['Wotuu', 'wotuu'],
            'battletag'                  => ['Wotuu#1234', 'wotuu-1234'],
            'spaces and dots'            => ['Foo Bar.', 'foo-bar'],
            'repeated separators'        => ['a -- b__c', 'a-b__c'],
            'url-breaking characters'    => ['a/b?c%d&e', 'a-b-c-d-e'],
            'cyrillic'                   => ['Вотуу', 'вотуу'],
            'chinese'                    => ['张三', '张三'],
            'only symbols'               => ['#!?', 'user'],
            'empty'                      => ['', 'user'],
            'decomposed accent is NFC'   => ["Jose\u{0301}", "jos\u{00E9}"],
            'precomposed accent'         => ["Jos\u{00E9}", "jos\u{00E9}"],
            'cut at 48 characters'       => [str_repeat('a', 60), str_repeat('a', 48)],
            'no trailing dash after cut' => [sprintf('%s b', str_repeat('a', 47)), str_repeat('a', 47)],
        ];
    }

    #[Test]
    public function findAvailableSlug_givenCollidingNames_returnsSuffixedSlug(): void
    {
        // Arrange
        $userSlugService = app(UserSlugServiceInterface::class);
        $number          = random_int(100000, 999999);
        $existingUser    = null;

        try {
            $existingUser = User::factory()->create(['name' => sprintf('woe2 %d', $number)]);

            // Act
            $slug = $userSlugService->findAvailableSlug(sprintf('woe2#%d', $number));

            // Assert
            $this->assertSame(sprintf('woe2-%d', $number), $existingUser->slug);
            $this->assertSame(sprintf('woe2-%d-2', $number), $slug);
        } finally {
            $existingUser?->delete();
        }
    }

    #[Test]
    public function findAvailableSlug_givenSuffixAlsoTaken_returnsNextFreeSuffix(): void
    {
        // Arrange
        $userSlugService = app(UserSlugServiceInterface::class);
        $number          = random_int(100000, 999999);
        $users           = collect();

        try {
            $users->push(User::factory()->create(['name' => sprintf('woe2 %d', $number)]));
            $users->push(User::factory()->create(['name' => sprintf('woe2-%d-2', $number)]));

            // Act
            $slug = $userSlugService->findAvailableSlug(sprintf('woe2#%d', $number));

            // Assert
            $this->assertSame(sprintf('woe2-%d-3', $number), $slug);
        } finally {
            $users->each(static fn(User $user) => $user->delete());
        }
    }

    #[Test]
    public function findAvailableSlug_givenTheUserHoldingTheSlug_returnsTheSameSlug(): void
    {
        // Arrange
        $userSlugService = app(UserSlugServiceInterface::class);
        $number          = random_int(100000, 999999);
        $users           = collect();

        try {
            $users->push(User::factory()->create(['name' => sprintf('woe2 %d', $number)]));
            $suffixedUser = User::factory()->create(['name' => sprintf('woe2#%d', $number)]);
            $users->push($suffixedUser);

            // Act
            $slug = $userSlugService->findAvailableSlug($suffixedUser->name, $suffixedUser->id);

            // Assert
            $this->assertSame(sprintf('woe2-%d-2', $number), $suffixedUser->slug);
            $this->assertSame($suffixedUser->slug, $slug);
        } finally {
            $users->each(static fn(User $user) => $user->delete());
        }
    }

    #[Test]
    public function isBaseSlugTaken_givenNameReducingToAnotherUsersSlug_returnsTrue(): void
    {
        // Arrange
        $userSlugService = app(UserSlugServiceInterface::class);
        $number          = random_int(100000, 999999);
        $existingUser    = null;

        try {
            $existingUser = User::factory()->create(['name' => sprintf('woe2#%d', $number)]);

            // Act
            $takenForOthers = $userSlugService->isBaseSlugTaken(sprintf('WOE2-%d', $number));
            $takenForSelf   = $userSlugService->isBaseSlugTaken(sprintf('WOE2-%d', $number), $existingUser->id);

            // Assert
            $this->assertTrue($takenForOthers);
            $this->assertFalse($takenForSelf);
        } finally {
            $existingUser?->delete();
        }
    }
}
