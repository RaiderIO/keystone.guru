<?php

namespace Tests\Feature\Console\Commands\User;

use App\Console\Commands\User\GenerateSlugs;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('User')]
final class GenerateSlugsTest extends PublicTestCase
{
    #[Test]
    public function handle_givenUsersWithAndWithoutSlug_fillsOnlyTheMissingOnes(): void
    {
        // Arrange
        $number      = random_int(100000, 999999);
        $withoutSlug = null;
        $withSlug    = null;

        try {
            $withoutSlug = User::factory()->create(['name' => sprintf('Wotuu#%d', $number)]);
            $withSlug    = User::factory()->create(['name' => sprintf('Kept%d', $number)]);
            User::query()->whereKey($withoutSlug->id)->update(['slug' => null]);
            User::query()->whereKey($withSlug->id)->update(['slug' => sprintf('custom-kept-%d', $number)]);
            $updatedAt = $withoutSlug->fresh()?->updated_at;

            // Act
            $this->artisan(GenerateSlugs::class)->assertSuccessful();

            // Assert
            $refreshedWithoutSlug = User::findOrFail($withoutSlug->id);
            $this->assertSame(sprintf('wotuu-%d', $number), $refreshedWithoutSlug->slug);
            $this->assertEquals($updatedAt, $refreshedWithoutSlug->updated_at);
            $this->assertSame(sprintf('custom-kept-%d', $number), $withSlug->fresh()?->slug);
            $this->assertSame(0, User::query()->whereNull('slug')->count());
        } finally {
            $withoutSlug?->delete();
            $withSlug?->delete();
        }
    }
}
