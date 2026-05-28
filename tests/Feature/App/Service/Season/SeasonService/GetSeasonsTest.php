<?php

namespace App\Service\Season\SeasonService;

use App\Models\Expansion;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetSeasons')]
final class GetSeasonsTest extends PublicTestCase
{
    #[Test]
    public function getSeasons_GivenNoArguments_ShouldReturnNonTimewalkingSeasons(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        /** @var Collection<Season> $result */
        $result = $service->getSeasons();

        // Assert
        foreach ($result as $season) {
            $this->assertNull($season->expansion->timewalkingEvent);
        }
    }

    #[Test]
    public function getSeasons_GivenBfaExpansion_ShouldReturnOnlyBfaSeasons(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();

        // Act
        $result = $service->getSeasons($bfaExpansion);

        // Assert
        $this->assertNotEmpty($result);
        foreach ($result as $season) {
            $this->assertEquals($bfaExpansion->id, $season->expansion_id);
        }
    }

    #[Test]
    public function getSeasons_GivenBfaExpansion_ShouldReturn4Seasons(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();

        // Act
        $result = $service->getSeasons($bfaExpansion);

        // Assert
        $this->assertCount(4, $result);
    }

    #[Test]
    public function getSeasons_CalledTwiceWithSameExpansion_ShouldReturnSameSeasonIds(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();

        // Act
        $result1 = $service->getSeasons($bfaExpansion);
        $result2 = $service->getSeasons($bfaExpansion);

        // Assert
        $this->assertEquals($result1->pluck('id'), $result2->pluck('id'));
    }

    #[Test]
    public function getSeasons_ShouldReturnSeasonsOrderedByStartDate(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();

        // Act
        $result = $service->getSeasons($bfaExpansion);

        // Assert
        $previousStart = null;
        foreach ($result as $season) {
            if ($previousStart !== null) {
                $this->assertTrue($season->start->gte($previousStart));
            }
            $previousStart = $season->start;
        }
    }
}
