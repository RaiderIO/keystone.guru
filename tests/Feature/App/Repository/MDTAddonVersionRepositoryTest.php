<?php

namespace Tests\Feature\App\Repository;

use App\Models\MDTAddonVersion;
use App\Repositories\Database\MDTAddonVersionRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Exercises the MDT addonVersion lookups against the seeded mdt_addon_versions table. Anchors used:
 *   40120 => 2022-11-28 (a historical, stable release), newest overall computed dynamically.
 */
#[Group('MDT')]
#[Group('MDTAddonVersion')]
final class MDTAddonVersionRepositoryTest extends PublicTestCase
{
    private MDTAddonVersionRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MDTAddonVersionRepository();
    }

    #[Test]
    public function findReleaseDate_givenKnownAddonVersion_returnsReleaseDate(): void
    {
        // Act
        $result = $this->repository->findReleaseDate(40120);

        // Assert
        $this->assertInstanceOf(CarbonInterface::class, $result);
        $this->assertSame('2022-11-28', $result->toDateString());
    }

    #[Test]
    public function findReleaseDate_givenUnknownAddonVersion_returnsNull(): void
    {
        // Act
        $result = $this->repository->findReleaseDate(999999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function findLatestAddonVersionAtDate_givenDateAfterAllReleases_returnsNewestAddonVersion(): void
    {
        // Arrange - the newest release in the seeded map, resolved independently of the JSON's contents.
        /** @var MDTAddonVersion $newest */
        $newest = MDTAddonVersion::query()->orderByDesc('released_at')->firstOrFail();

        // Act
        $result = $this->repository->findLatestAddonVersionAtDate(Carbon::parse('2100-01-01 00:00:00'));

        // Assert
        $this->assertSame($newest->addon_version, $result);
    }

    #[Test]
    public function findLatestAddonVersionAtDate_givenDateBeforeAllReleases_returnsNull(): void
    {
        // Act - MDT's first release is in 2018, so nothing was live in 2000.
        $result = $this->repository->findLatestAddonVersionAtDate(Carbon::parse('2000-01-01 00:00:00'));

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function findLatestAddonVersionAtDate_givenExactReleaseDate_returnsVersionLiveAtThatMoment(): void
    {
        // Arrange - the release live on 2022-11-28 (40120) must resolve to a version at or before that date.
        $date = Carbon::parse('2022-11-28 13:31:27');

        // Act
        $result = $this->repository->findLatestAddonVersionAtDate($date);

        // Assert
        $this->assertNotNull($result);
        $resultDate = $this->repository->findReleaseDate($result);
        $this->assertNotNull($resultDate);
        $this->assertTrue($resultDate->lessThanOrEqualTo($date));
    }
}
