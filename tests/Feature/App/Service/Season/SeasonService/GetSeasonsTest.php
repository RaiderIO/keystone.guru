<?php

namespace Tests\Feature\App\Service\Season\SeasonService;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetSeasons')]
final class GetSeasonsTest extends PublicTestCase
{
    #[Test]
    public function getSeasons_givenNoExpansion_returnsTheCurrentExpansionsSeasons(): void
    {
        // Arrange
        $service          = app(SeasonServiceInterface::class);
        $currentExpansion = app(ExpansionServiceInterface::class)->getCurrentExpansion(GameServerRegion::getUserOrDefaultRegion(null));
        $expectedIds      = Season::query()
            ->where('expansion_id', $currentExpansion->id)
            ->orderBy('start')
            ->pluck('id')
            ->all();
        $this->assertNotEmpty($expectedIds, 'The current expansion must have seasons, or this test proves nothing.');

        // Act
        $result = $service->getSeasons();

        // Assert
        $this->assertSame($expectedIds, $result->pluck('id')->values()->all());
    }

    #[Test]
    public function getSeasons_GivenBfaExpansion_ShouldReturnOnlyBfaSeasons(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('key', Expansion::EXPANSION_BFA)->firstOrFail();

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
        $bfaExpansion = Expansion::where('key', Expansion::EXPANSION_BFA)->firstOrFail();

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
        $bfaExpansion = Expansion::where('key', Expansion::EXPANSION_BFA)->firstOrFail();

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
        $bfaExpansion = Expansion::where('key', Expansion::EXPANSION_BFA)->firstOrFail();

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
