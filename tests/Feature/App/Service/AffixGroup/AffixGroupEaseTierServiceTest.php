<?php

namespace Tests\Feature\App\Service\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\LoggingFixtures;
use Tests\Unit\Fixtures\ServiceFixtures;

class AffixGroupEaseTierServiceTest extends PublicTestCase
{

    /**
     * @test
     *
     * @return void
     *
     * @group AffixGroupEaseTierService
     */
    public function parseTierList_GivenCorrectResponseWithNoExistingPulls_ShouldCreateNewPull()
    {
        // Arrange
        $affixGroupId = 124;
        $response     = $this->getResponse();

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
            $result = $affixGroupEaseTierService->parseTierList($response);
        } finally {
            // If it was successful, delete the entry again, so we have a clean database.
            optional($result)->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $result);
        $this->assertGreaterThan(0, $result->id);
    }

    /**
     * @test
     *
     * @return void
     *
     * @group AffixGroupEaseTierService
     */
    public function parseTierList_GivenResponseWithUnknownAffix_ShouldLogUnknownAffixError()
    {
        // Arrange
        $response = $this->getResponse('response_unknown_affix');

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
            optional($result)->delete();
        }

        // Assert
        $this->assertNull($result);
    }

    /**
     * @test
     *
     * @return void
     *
     * @group AffixGroupEaseTierService
     */
    public function parseTierList_GivenResponseWithUnknownDungeon_ShouldLogUnknownDungeonError()
    {
        // Arrange
        $response = $this->getResponse('response_unknown_dungeon');

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            null,
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
            optional($result)->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $result);
    }

    /**
     * @test
     *
     * @return void
     *
     * @group AffixGroupEaseTierService
     */
    public function parseTierList_GivenResponseWithDifferentAffixes_ShouldCreateNewPull()
    {
        // Arrange
        $response               = $this->getResponse();
        $responseDifferentAffix = $this->getResponse('response_different_affix');

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            null,
            $log
        );

        // Act
        $result = $previousAffixGroupEaseTierPull = null;
        try {
            $previousAffixGroupEaseTierPull = $affixGroupEaseTierService->parseTierList($response);
            $result                         = $affixGroupEaseTierService->parseTierList($responseDifferentAffix);
        } finally {
            // If it was successful, delete the entry again, so we have a clean database.
            optional($previousAffixGroupEaseTierPull)->delete();
            optional($result)->delete();
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
     * @test
     *
     * @return void
     *
     * @group AffixGroupEaseTierService
     */
    public function parseTierList_GivenSameResponse_ShouldReturnNull()
    {
        // Arrange
        $response               = $this->getResponse();

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            null,
            $log
        );

        // Act
        $result = $previousAffixGroupEaseTierPull = null;
        try {
            $previousAffixGroupEaseTierPull = $affixGroupEaseTierService->parseTierList($response);
            $result                         = $affixGroupEaseTierService->parseTierList($response);
        } finally {
            // If it was successful, delete the entry again, so we have a clean database.
            optional($previousAffixGroupEaseTierPull)->delete();
            optional($result)->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $previousAffixGroupEaseTierPull);
        $this->assertGreaterThan(0, $previousAffixGroupEaseTierPull->id);

        $this->assertNull($result);
    }

    /**
     * @param string $fileName
     * @return array
     */
    private function getResponse(string $fileName = 'response'): array
    {
        return json_decode(file_get_contents(sprintf('%s/Fixtures/%s.json', __DIR__, $fileName)), true);
    }
}
