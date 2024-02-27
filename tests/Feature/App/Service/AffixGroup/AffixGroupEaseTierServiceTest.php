<?php

namespace Tests\Feature\App\Service\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\LoggingFixtures;
use Tests\Unit\Fixtures\ServiceFixtures;

final class AffixGroupEaseTierServiceTest extends PublicTestCase
{
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenCorrectResponseWithNoExistingPulls_ShouldCreateNewPull(): void
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
            $result?->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $result);
        $this->assertGreaterThan(0, $result->id);
    }

    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenResponseWithUnknownAffix_ShouldLogUnknownAffixError(): void
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
            $result?->delete();
        }

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenResponseWithUnknownDungeon_ShouldLogUnknownDungeonError(): void
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
            $result?->delete();
        }

        // Assert
        $this->assertInstanceOf(AffixGroupEaseTierPull::class, $result);
    }

    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenResponseWithDifferentAffixes_ShouldCreateNewPull(): void
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

    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function parseTierList_GivenSameResponse_ShouldReturnNull(): void
    {
        // Arrange
        $response = $this->getResponse();

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            null,
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

    private function getResponse(string $fileName = 'response'): array
    {
        return json_decode(file_get_contents(sprintf('%s/Fixtures/%s.json', __DIR__, $fileName)), true);
    }
}
