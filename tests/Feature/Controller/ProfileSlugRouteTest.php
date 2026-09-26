<?php

namespace Tests\Feature\Controller;

use App\Models\User;
use App\Service\User\UserSlugServiceInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class ProfileSlugRouteTest extends PublicTestCase
{
    #[Test]
    public function view_givenSlug_returnsTheProfile(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create(['name' => sprintf('Wotuu%d', random_int(100000, 999999))]);

            // Act
            $response = $this->get(sprintf('/user/%s', $user->slug));

            // Assert
            $response->assertOk();
            $response->assertViewHas('user', static fn(User $viewedUser): bool => $viewedUser->is($user));
            $this->assertSame(url(sprintf('/user/%s', $user->slug)), route('profile.view', ['user' => $user]));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function view_givenSlugInAnotherCase_redirectsToTheCanonicalUrl(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create(['name' => sprintf('Wotuu%d', random_int(100000, 999999))]);

            // Act
            $response = $this->get(sprintf('/user/%s?tab=routes', mb_strtoupper($user->slug)));

            // Assert
            $response->assertStatus(301);
            $response->assertRedirect(sprintf('%s?tab=routes', route('profile.view', ['user' => $user])));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function view_givenUnicodeSlug_returnsTheProfile(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create(['name' => sprintf('Вотуу%d', random_int(100000, 999999))]);

            // Act
            $response = $this->get(route('profile.view', ['user' => $user]));

            // Assert
            $this->assertStringStartsWith('вотуу', $user->slug);
            $response->assertOk();
            $response->assertViewHas('user', static fn(User $viewedUser): bool => $viewedUser->is($user));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function view_givenUnknownSlug_returnsNotFound(): void
    {
        // Act
        $response = $this->get(sprintf('/user/no-such-user-%d', random_int(100000, 999999)));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function viewLegacy_givenNumericId_redirectsToTheSlugUrl(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create();

            // Act
            $response = $this->get(sprintf('/profile/%d', $user->id));

            // Assert
            $response->assertStatus(301);
            $response->assertRedirect(route('profile.view', ['user' => $user]));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function viewLegacy_givenUnknownId_returnsNotFound(): void
    {
        // Act
        $response = $this->get(sprintf('/profile/%d', User::query()->max('id') + 1000));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function view_givenRenamedUser_servesTheNewSlugAndNotTheOld(): void
    {
        // Arrange
        $user = null;

        try {
            $user    = User::factory()->create(['name' => sprintf('before%d', random_int(100000, 999999))]);
            $oldSlug = $user->slug;

            // Act
            $user->name = sprintf('after%d', random_int(100000, 999999));
            $user->save();

            // Assert
            $this->assertSame(mb_strtolower($user->name), $user->slug);
            $this->get(sprintf('/user/%s', $user->slug))->assertOk();
            $this->get(sprintf('/user/%s', $oldSlug))->assertNotFound();
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function save_givenUserWithoutSlug_generatesOne(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create(['name' => sprintf('Wotuu#%d', random_int(100000, 999999))]);
            User::query()->whereKey($user->id)->update(['slug' => null]);
            $user->refresh();

            // Act
            $user->echo_color = '#abcdef';
            $user->save();

            // Assert
            $this->assertSame(str_replace('#', '-', mb_strtolower($user->name)), $user->fresh()?->slug);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function getSlugAttribute_givenPersistedUserWithoutSlug_assignsOneSoItsLinkResolves(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create(['name' => sprintf('Wotuu#%d', random_int(100000, 999999))]);
            User::query()->whereKey($user->id)->update(['slug' => null]);
            $slugless = User::findOrFail($user->id);

            // Act
            $url = route('profile.view', ['user' => $slugless]);

            // Assert
            $expectedSlug = str_replace('#', '-', mb_strtolower($user->name));
            $this->assertSame(url(sprintf('/user/%s', $expectedSlug)), $url);
            $this->assertSame($expectedSlug, User::query()->whereKey($user->id)->value('slug'));
            $this->assertFalse($slugless->isDirty('slug'));
            $this->get($url)->assertOk();
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function getSlugAttribute_givenPartialSelectWithoutSlug_returnsTheStoredSlugUnchanged(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create(['name' => sprintf('Wotuu%d', random_int(100000, 999999))]);
            User::query()->whereKey($user->id)->update(['slug' => sprintf('custom-%d', $user->id)]);
            $partialUser = User::query()->select(['id'])->findOrFail($user->id);

            // Act
            $slug = $partialUser->slug;

            // Assert
            $this->assertSame(sprintf('custom-%d', $user->id), $slug);
            $this->assertSame(sprintf('custom-%d', $user->id), User::query()->whereKey($user->id)->value('slug'));
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function save_givenSlugClaimedBetweenCheckAndWrite_retriesWithTheNextFreeSlug(): void
    {
        // Arrange
        $number    = random_int(100000, 999999);
        $holder    = null;
        $newcomer  = null;
        $takenSlug = sprintf('woe2-%d', $number);

        try {
            $holder = User::factory()->create(['name' => sprintf('woe2 %d', $number)]);

            // Stale on its first answer, as if the holder's row landed right after the availability check
            $realService = app(UserSlugServiceInterface::class);
            $staleCalls  = 0;
            $slugService = $this->createMockPublic(UserSlugServiceInterface::class);
            $slugService->method('findAvailableSlug')->willReturnCallback(
                static function (string $name, ?int $exceptUserId) use ($realService, $takenSlug, &$staleCalls): string {
                    return $staleCalls++ === 0 ? $takenSlug : $realService->findAvailableSlug($name, $exceptUserId);
                },
            );
            app()->instance(UserSlugServiceInterface::class, $slugService);

            // Act
            $newcomer = User::factory()->create(['name' => sprintf('woe2#%d', $number)]);

            // Assert
            $this->assertSame(sprintf('woe2-%d-2', $number), $newcomer->slug);
            $this->assertSame(2, $staleCalls);
        } finally {
            app()->forgetInstance(UserSlugServiceInterface::class);
            $newcomer?->delete();
            $holder?->delete();
        }
    }

    #[Test]
    public function update_givenDuplicateSlugWrittenDirectly_throwsUniqueConstraintViolation(): void
    {
        // Arrange
        $users = collect();

        try {
            $users->push($first = User::factory()->create());
            $users->push($second = User::factory()->create());

            // Assert
            $this->expectException(UniqueConstraintViolationException::class);

            // Act
            User::query()->whereKey($second->id)->update(['slug' => $first->slug]);
        } finally {
            $users->each(static fn(User $user) => $user->delete());
        }
    }
}
