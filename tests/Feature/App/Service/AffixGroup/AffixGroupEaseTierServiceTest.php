<?php

namespace Tests\Feature\App\Service\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\Fixtures\LoggingFixtures;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;
use Throwable;

final class AffixGroupEaseTierServiceTest extends PublicTestCase
{
    use LoadsJsonFiles;

    /**
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenCorrectResponseWithNoExistingPulls_ShouldCreateNewPull(): void
    {
        // Arrange
        $affixGroupId = 124;
        $response     = $this->getJsonData('response');

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            null,
            $log,
            ['getAffixGroupByString']
        );

        $affixGroupEaseTierService->expects($this->once())
            ->method('getAffixGroupByString')
            // This is the active affix - trust me bro
            ->willReturn(AffixGroup::findOrFail($affixGroupId));

        // Happen to have 4 tiers active now
        $log->expects($this->exactly(4))
            ->method('parseTierListParseTierStart');

        $log->expects($this->never())
            ->method('parseTierListUnknownAffixGroup');

        $log->expects($this->never())
            ->method('parseTierListUnknownDungeon');

        // 8 dungeons
        $log->expects($this->exactly(8))
            ->method('parseTierListSavedDungeonTier');

        // Happen to have 4 tiers active now
        $log->expects($this->exactly(4))
            ->method('parseTierListParseTierEnd');

        // Act
        $result = null;
        try {
            // Should not be needed if we use repositories, but we're not at the moment..
            DB::transaction(function () use (&$result, $affixGroupEaseTierService, $response) {
                $result = $affixGroupEaseTierService->parseTierList($response);
            });
        } finally {
            // If it was successful, delete the entry again, so we have a clean database.
            $result?->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $result);
        $this->assertGreaterThan(0, $result->id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenResponseWithUnknownAffix_ShouldLogUnknownAffixError(): void
    {
        // Arrange
        $response = $this->getJsonData('response_unknown_affix');

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            null,
            $log,
            ['getAffixGroupByString', 'getTiersHash']
        );

        $affixGroupEaseTierService->expects($this->once())
            ->method('getAffixGroupByString')
            // This is the active affix - trust me bro
            ->willReturn(null);

        $affixGroupEaseTierService->expects($this->never())
            ->method('getTiersHash');

        // Act
        $result = null;
        try {
            $result = $affixGroupEaseTierService->parseTierList($response);
        } finally {
            // If it was successful, delete the entry again, so we have a clean database.
            $result?->delete();
        }

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenResponseWithUnknownDungeon_ShouldLogUnknownDungeonError(): void
    {
        // Arrange
        $response = $this->getJsonData('response_unknown_dungeon');

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(),
            $log
        );

        $log->expects($this->once())
            ->method('parseTierListUnknownDungeon');

        // Act
        $result = null;
        try {
            $result = $affixGroupEaseTierService->parseTierList($response);
        } finally {
            // If it was successful, delete the entry again, so we have a clean database.
            $result?->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenResponseWithDifferentAffixes_ShouldCreateNewPull(): void
    {
        // Arrange
        $response               = $this->getJsonData('response');
        $responseDifferentAffix = $this->getJsonData('response_different_affix');

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(),
            $log
        );
        // Act
        $result                         = null;
        $previousAffixGroupEaseTierPull = null;
        try {
            $previousAffixGroupEaseTierPull = $affixGroupEaseTierService->parseTierList($response);
            $result                         = $affixGroupEaseTierService->parseTierList($responseDifferentAffix);
        } finally {
            // If it was successful, delete the entry again, so we have a clean database.
            $previousAffixGroupEaseTierPull?->delete();
            $result?->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $previousAffixGroupEaseTierPull);
        $this->assertGreaterThan(0, $previousAffixGroupEaseTierPull->id);

        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $result);
        $this->assertGreaterThan(0, $result->id);

        $this->assertNotEquals($previousAffixGroupEaseTierPull->id, $result->id);
        $this->assertNotEquals($previousAffixGroupEaseTierPull->affix_group_id, $result->affix_group_id);
        $this->assertNotEquals($previousAffixGroupEaseTierPull->tiers_hash, $result->tiers_hash);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenResponseWithInvalidLastUpdated_ShouldLogUnknownLastUpdatedError(): void
    {
        // Arrange
        $responseDifferentAffix = $this->getJsonData('response_invalid_last_updated');

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(),
            $log
        );

        $log->expects($this->once())
            ->method('parseTierListInvalidLastUpdated');

        // Act
        $result = $affixGroupEaseTierService->parseTierList($responseDifferentAffix);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenSameResponse_ShouldReturnNull(): void
    {
        // Arrange
        $response = $this->getJsonData('response');

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(),
            $log
        );

        // Act
        $result                         = null;
        $previousAffixGroupEaseTierPull = null;
        try {
            $previousAffixGroupEaseTierPull = $affixGroupEaseTierService->parseTierList($response);
            $result                         = $affixGroupEaseTierService->parseTierList($response);
        } finally {
            // If it was successful, delete the entry again, so we have a clean database.
            $previousAffixGroupEaseTierPull?->delete();
            $result?->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $previousAffixGroupEaseTierPull);
        $this->assertGreaterThan(0, $previousAffixGroupEaseTierPull->id);

        $this->assertNull($result);
    }

    /**
     * @return SeasonServiceInterface|MockObject
     */
    private function getSeasonService(): MockObject|SeasonServiceInterface
    {
        // Hard code a season that fits the affix groups for the response, DF S4
        $season        = Season::find(13);
        $seasonService = ServiceFixtures::getSeasonServiceMock(
            $this,
            null,
            ['getCurrentSeason'],
            collect([
                $season,
            ])
        );
        $seasonService->method('getCurrentSeason')
            ->willReturn($season);

        return $seasonService;
    }
}
