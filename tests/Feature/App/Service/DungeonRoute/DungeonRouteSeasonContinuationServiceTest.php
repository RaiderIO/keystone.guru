<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\PublishedState;
use App\Service\DungeonRoute\DungeonRouteSeasonContinuationServiceInterface;
use App\Service\DungeonRoute\Exceptions\SeasonContinuationException;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeonInSeveralSeasons;

#[Group('DungeonRouteSeasonContinuationService')]
final class DungeonRouteSeasonContinuationServiceTest extends DungeonRouteSaveServiceTestCase
{
    use ProvidesDungeonInSeveralSeasons;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
        // cloneRoute() uses Auth::id() for author_id, which is NOT NULL in the DB
        Auth::loginUsingId(1);
    }

    #[\Override]
    protected function tearDown(): void
    {
        Auth::logout();

        parent::tearDown();
    }

    #[Test]
    public function continueInNewerSeason_givenRouteInOlderSeason_returnsCopyInNewestSeason(): void
    {
        // Arrange
        [$dungeon, $olderSeason, $newestSeason] = $this->findDungeonInSeveralSeasons();
        $source                                 = $this->createDungeonRouteInSeason($dungeon, $olderSeason);
        $continuation                           = null;

        try {
            // Act
            $continuation = $this->service()->continueInNewerSeason($source);

            // Assert
            $this->assertSame($newestSeason->id, $continuation->season_id);
            $this->assertSame($source->public_key, $continuation->clone_of);
            $this->assertSame($source->title, $continuation->title);
            $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $continuation->published_state_id);
            $this->assertSame($olderSeason->id, $source->fresh()->season_id);
        } finally {
            $continuation?->delete();
            $source->delete();
        }
    }

    #[Test]
    public function continueInNewerSeason_givenAffixGroupOfOlderSeason_keepsOnlyAffixGroupsOfNewestSeason(): void
    {
        // Arrange
        [$dungeon, $olderSeason, $newestSeason] = $this->findDungeonInSeveralSeasons();
        $source                                 = $this->createDungeonRouteInSeason($dungeon, $olderSeason);
        $continuation                           = null;

        /** @var AffixGroup $olderAffixGroup */
        $olderAffixGroup = $olderSeason->affixGroups()->firstOrFail();
        DungeonRouteAffixGroup::create(['dungeon_route_id' => $source->id, 'affix_group_id' => $olderAffixGroup->id]);

        try {
            // Act
            $continuation = $this->service()->continueInNewerSeason($source);

            // Assert
            $continuationAffixGroups = $continuation->affixes()->get();
            $this->assertNotContains($olderAffixGroup->id, $continuationAffixGroups->pluck('id'));
            foreach ($continuationAffixGroups as $affixGroup) {
                $this->assertSame($newestSeason->id, $affixGroup->season_id);
            }

            $this->assertContains($olderAffixGroup->id, $source->affixes()->pluck('affix_groups.id'));
        } finally {
            $continuation?->delete();
            $source->delete();
        }
    }

    #[Test]
    public function continueInNewerSeason_givenRouteOnOlderMappingVersion_movesOnlyTheCopyToTheLatestMappingVersion(): void
    {
        // Arrange
        [$dungeon, $olderSeason] = $this->findDungeonInSeveralSeasons();
        $existingMappingVersion  = $dungeon->getCurrentMappingVersion();
        $source                  = $this->createDungeonRouteInSeason($dungeon, $olderSeason);
        $newerMappingVersion     = $this->createNewerMappingVersion($dungeon, $existingMappingVersion);
        $continuation            = null;

        try {
            // Act
            $continuation = $this->service()->continueInNewerSeason($source);

            // Assert
            $this->assertSame($newerMappingVersion->id, $continuation->mapping_version_id);
            $this->assertSame($existingMappingVersion->id, $source->fresh()->mapping_version_id);
        } finally {
            $continuation?->delete();
            $source->delete();
            $newerMappingVersion->delete();
        }
    }

    #[Test]
    public function continueInNewerSeason_givenRouteInNewestSeason_throwsSeasonContinuationException(): void
    {
        // Arrange
        [$dungeon, , $newestSeason] = $this->findDungeonInSeveralSeasons();
        $source                     = $this->createDungeonRouteInSeason($dungeon, $newestSeason);

        try {
            // Assert
            $this->expectException(SeasonContinuationException::class);

            // Act
            $this->service()->continueInNewerSeason($source);
        } finally {
            $this->assertSame(0, DungeonRoute::query()->where('clone_of', $source->public_key)->count());
            $source->delete();
        }
    }

    #[Test]
    public function continueInNewerSeason_givenRouteAlreadyContinued_throwsSeasonContinuationException(): void
    {
        // Arrange
        [$dungeon, $olderSeason] = $this->findDungeonInSeveralSeasons();
        $source                  = $this->createDungeonRouteInSeason($dungeon, $olderSeason);
        $continuation            = $this->service()->continueInNewerSeason($source);

        try {
            // Assert
            $this->expectException(SeasonContinuationException::class);

            // Act
            $this->service()->continueInNewerSeason($source);
        } finally {
            $this->assertSame(1, DungeonRoute::query()->where('clone_of', $source->public_key)->count());
            $continuation->delete();
            $source->delete();
        }
    }

    #[Test]
    public function getContinuationSeasons_givenRoutesInOlderNewestAndNoSeason_returnsOnlyTheOlderSeasonRoute(): void
    {
        // Arrange
        [$dungeon, $olderSeason, $newestSeason] = $this->findDungeonInSeveralSeasons();
        $olderRoute                             = $this->createDungeonRouteInSeason($dungeon, $olderSeason);
        $newestRoute                            = $this->createDungeonRouteInSeason($dungeon, $newestSeason);
        $seasonlessRoute                        = $this->createDungeonRouteInSeason($dungeon, null);

        try {
            // Act
            $result = $this->service()->getContinuationSeasons(collect([$olderRoute, $newestRoute, $seasonlessRoute]));

            // Assert
            $this->assertSame([$olderRoute->id], $result->keys()->all());
            $this->assertSame($newestSeason->id, $result->get($olderRoute->id)->id);
        } finally {
            $olderRoute->delete();
            $newestRoute->delete();
            $seasonlessRoute->delete();
        }
    }

    #[Test]
    public function getContinuationSeasons_givenNoRoutes_returnsEmptyCollection(): void
    {
        // Act
        $result = $this->service()->getContinuationSeasons(collect());

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    private function service(): DungeonRouteSeasonContinuationServiceInterface
    {
        return app(DungeonRouteSeasonContinuationServiceInterface::class);
    }
}
