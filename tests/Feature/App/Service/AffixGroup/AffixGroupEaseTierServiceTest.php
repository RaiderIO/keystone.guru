<?php

namespace Tests\Feature\App\Service\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Models\Season;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use DB;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ['getAffixGroupByString'],
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
            ['getAffixGroupByString', 'getTiersHash'],
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
            $log,
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
            $log,
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
            $log,
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
            $log,
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
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    #[DataProvider('affixStringProvider')]
    public function getAffixGroupByString_givenAffixStringOfTheCurrentSeason_returnsAffixGroupWithThoseAffixes(
        int    $seasonId,
        string $affixString,
    ): void {
        // Arrange
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService($seasonId),
            LoggingFixtures::createAffixGroupEaseTierServiceLogging($this),
            [],
            $this->getSeasonAffixGroupService(null),
        );

        // Act
        $result = $affixGroupEaseTierService->getAffixGroupByString($affixString);

        // Assert
        // Multiple affix groups of a season have the exact same affixes, so assert the affixes of the affix group we
        // found rather than which affix group specifically we found - which affix group of those is the correct one
        // is decided by the current affix group instead, see getAffixGroupByString_givenAffixesOfTheCurrentAffixGroup_
        $this->assertInstanceOf(AffixGroup::class, $result);
        $this->assertSame($seasonId, $result->season_id);
        // The affixes are compared by name, which is currently the same as the key of an affix
        $this->assertEqualsCanonicalizing(
            explode(', ', $affixString),
            $result->affixes->pluck('key')->toArray(),
        );
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function affixStringProvider(): array
    {
        return [
            // The amount of affixes an affix group has differs per season - it must not be assumed to be 3 or 4
            'DF S4 (3 affixes)'            => [Season::SEASON_DF_S4, 'Fortified, Entangling, Bolstering'],
            'TWW S3 (4 affixes)'           => [Season::SEASON_TWW_S3, "Xal'atath's Bargain: Ascendant, Fortified, Tyrannical, Xal'atath's Guile"],
            'Midnight S1 (5 affixes)'      => [Season::SEASON_MIDNIGHT_S1, "Lindormi's Guidance, Fortified, Tyrannical, Xal'atath's Bargain: Devour, Xal'atath's Guile"],
            'Affixes in a different order' => [Season::SEASON_MIDNIGHT_S1, "Xal'atath's Guile, Tyrannical, Lindormi's Guidance, Xal'atath's Bargain: Devour, Fortified"],
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenAffixesOfTheCurrentAffixGroup_returnsTheCurrentAffixGroup(): void
    {
        // Arrange
        // A season has multiple affix groups with the exact same affixes - the one of the current week must be
        // returned, since ease tiers are looked up by the id of the current affix group. Deliberately take the last
        // of those affix groups, so that this test fails if the first affix group having the affixes is returned.
        $affixGroupsWithTheSameAffixes = $this->getAffixGroupsWithTheSameAffixes(
            Season::SEASON_MIDNIGHT_S1,
            "Lindormi's Guidance, Fortified, Tyrannical, Xal'atath's Bargain: Devour, Xal'atath's Guile",
        );
        $this->assertGreaterThan(1, $affixGroupsWithTheSameAffixes->count());

        $currentAffixGroup = $affixGroupsWithTheSameAffixes->last();

        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_MIDNIGHT_S1),
            LoggingFixtures::createAffixGroupEaseTierServiceLogging($this),
            [],
            $this->getSeasonAffixGroupService($currentAffixGroup),
        );

        // Act
        $result = $affixGroupEaseTierService->getAffixGroupByString(
            $currentAffixGroup->affixes->pluck('key')->join(', '),
        );

        // Assert
        $this->assertInstanceOf(AffixGroup::class, $result);
        $this->assertSame($currentAffixGroup->id, $result->id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenAffixesOfAnotherWeekThanTheCurrentAffixGroup_returnsThatOtherAffixGroup(): void
    {
        // Arrange
        // Archon may still be serving the tier list of the previous week shortly after a reset - the affix group of
        // those affixes must be returned then, not the affix group of the current week
        $affixString       = "Lindormi's Guidance, Fortified, Tyrannical, Xal'atath's Bargain: Ascendant, Xal'atath's Guile";
        $currentAffixGroup = $this->getAffixGroupsWithTheSameAffixes(
            Season::SEASON_MIDNIGHT_S1,
            "Lindormi's Guidance, Fortified, Tyrannical, Xal'atath's Bargain: Devour, Xal'atath's Guile",
        )->first();

        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_MIDNIGHT_S1),
            LoggingFixtures::createAffixGroupEaseTierServiceLogging($this),
            [],
            $this->getSeasonAffixGroupService($currentAffixGroup),
        );

        // Act
        $result = $affixGroupEaseTierService->getAffixGroupByString($affixString);

        // Assert
        $this->assertInstanceOf(AffixGroup::class, $result);
        $this->assertNotSame($currentAffixGroup->id, $result->id);
        $this->assertEqualsCanonicalizing(
            explode(', ', $affixString),
            $result->affixes->pluck('key')->toArray(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenCurrentAffixGroupIsOfADifferentSeason_doesNotShortCircuitToThatAffixGroup(): void
    {
        // Arrange
        // The current affix group can be that of a timewalking event, which belongs to a different season than the
        // one getAffixGroupByString() is resolving against (SeasonAffixGroupService::getAffixGroupAt() defers to
        // TimewalkingEventService::getAffixGroupAt() in that case, which returns an affix group of
        // getCurrentSeason($expansion) rather than the queried season). Pick a current affix group whose affixes
        // would otherwise satisfy the requested string, to prove the season_id guard - not a lack of matching
        // affixes - is what stops the wrong affix group from being returned.
        $affixGroupsOfADifferentSeason = $this->getAffixGroupsWithTheSameAffixes(
            Season::SEASON_DF_S4,
            'Fortified, Entangling, Bolstering',
        );
        $this->assertGreaterThan(0, $affixGroupsOfADifferentSeason->count());
        $currentAffixGroupOfADifferentSeason = $affixGroupsOfADifferentSeason->first();
        $this->assertInstanceOf(AffixGroup::class, $currentAffixGroupOfADifferentSeason);

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_MIDNIGHT_S1),
            $log,
            [],
            $this->getSeasonAffixGroupService($currentAffixGroupOfADifferentSeason),
        );

        $log->expects($this->once())
            ->method('getAffixGroupByStringNoMatchingAffixGroup');

        // Act
        // None of Midnight S1's affix groups have Entangling or Bolstering, so without the season_id guard this
        // would incorrectly short-circuit to $currentAffixGroupOfADifferentSeason instead of falling through
        $result = $affixGroupEaseTierService->getAffixGroupByString('Fortified, Entangling, Bolstering');

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenPartialAffixStringOfTheCurrentSeason_returnsAffixGroupHavingThoseAffixes(): void
    {
        // Arrange
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_MIDNIGHT_S1),
            LoggingFixtures::createAffixGroupEaseTierServiceLogging($this),
            [],
            $this->getSeasonAffixGroupService(null),
        );

        // Act
        $result = $affixGroupEaseTierService->getAffixGroupByString("Fortified, Xal'atath's Bargain: Devour");

        // Assert
        // Only the affix groups of the Devour weeks have both affixes, and they all have the exact same affixes
        $this->assertInstanceOf(AffixGroup::class, $result);
        $this->assertEqualsCanonicalizing(
            ["Lindormi's Guidance", 'Fortified', 'Tyrannical', "Xal'atath's Bargain: Devour", "Xal'atath's Guile"],
            $result->affixes->pluck('key')->toArray(),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenTooFewAffixesToTellAffixGroupsApart_returnsNull(): void
    {
        // Arrange
        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_DF_S3),
            $log,
            [],
            $this->getSeasonAffixGroupService(null),
        );

        $log->expects($this->once())
            ->method('getAffixGroupByStringAmbiguousAffixes');

        // Act
        // Five affix groups of DF S3 have Tyrannical, all with different other affixes
        $result = $affixGroupEaseTierService->getAffixGroupByString('Tyrannical');

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenTooFewAffixesToTellAffixGroupsApartAndACurrentAffixGroupThatDoesNotMatch_returnsNull(): void
    {
        // Arrange
        // Unlike getAffixGroupByString_givenTooFewAffixesToTellAffixGroupsApart_returnsNull, this sets a real current
        // affix group (a live season always has one) that does not have the requested affixes, so the current-week
        // fast path is skipped on its own merits and the ambiguity check below it is reached the way it would be in
        // production, not only because getCurrentAffixGroup() was mocked to null.
        $currentAffixGroup = $this->getAffixGroupsWithTheSameAffixes(
            Season::SEASON_DF_S3,
            'Fortified, Incorporeal, Sanguine',
        )->first();
        $this->assertInstanceOf(AffixGroup::class, $currentAffixGroup);

        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_DF_S3),
            $log,
            [],
            $this->getSeasonAffixGroupService($currentAffixGroup),
        );

        $log->expects($this->once())
            ->method('getAffixGroupByStringAmbiguousAffixes');

        // Act
        // Five affix groups of DF S3 have Tyrannical, all with different other affixes; the current affix group
        // (Fortified, Incorporeal, Sanguine) is not one of them
        $result = $affixGroupEaseTierService->getAffixGroupByString('Tyrannical');

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenAffixesThatNoSingleAffixGroupHas_returnsNull(): void
    {
        // Arrange
        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_MIDNIGHT_S1),
            $log,
            [],
            $this->getSeasonAffixGroupService(null),
        );

        $log->expects($this->once())
            ->method('getAffixGroupByStringNoMatchingAffixGroup');

        // Act
        $result = $affixGroupEaseTierService->getAffixGroupByString(
            "Xal'atath's Bargain: Devour, Xal'atath's Bargain: Pulsar",
        );

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenUnknownAffix_returnsNullAndLogsTheUnknownAffixes(): void
    {
        // Arrange
        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_MIDNIGHT_S1),
            $log,
        );

        $log->expects($this->once())
            ->method('getAffixGroupByStringUnknownAffixes')
            ->with('Breaking');

        // Act
        $result = $affixGroupEaseTierService->getAffixGroupByString('Fortified, Breaking');

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    #[DataProvider('emptyAffixStringProvider')]
    public function getAffixGroupByString_givenAffixStringWithoutAnyAffixes_returnsNull(string $affixString): void
    {
        // Arrange
        $log                       = LoggingFixtures::createAffixGroupEaseTierServiceLogging($this);
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(Season::SEASON_MIDNIGHT_S1),
            $log,
        );

        $log->expects($this->once())
            ->method('getAffixGroupByStringNoAffixes');

        // Act
        $result = $affixGroupEaseTierService->getAffixGroupByString($affixString);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function emptyAffixStringProvider(): array
    {
        return [
            'Empty string'    => [''],
            'Whitespace only' => ['   '],
            'Separators only' => [', ,'],
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getAffixGroupByString_givenNoCurrentSeason_returnsNull(): void
    {
        // Arrange
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock(
            $this,
            $this->getSeasonService(null),
            LoggingFixtures::createAffixGroupEaseTierServiceLogging($this),
        );

        // Act
        $result = $affixGroupEaseTierService->getAffixGroupByString('Fortified, Entangling, Bolstering');

        // Assert
        $this->assertNull($result);
    }

    /**
     * @param  int|null                          $seasonId Which season should act as the current season, DF S4 by default
     * @return SeasonServiceInterface|MockObject
     * @throws Exception
     */
    private function getSeasonService(?int $seasonId = Season::SEASON_DF_S4): MockObject|SeasonServiceInterface
    {
        // Hard code a season that fits the affix groups for the response, DF S4
        $season        = $seasonId === null ? null : Season::findOrFail($seasonId);
        $seasonService = ServiceFixtures::getSeasonServiceMock(
            $this,
            null,
            ['getCurrentSeason'],
            collect(array_filter([
                $season,
            ])),
        );
        $seasonService->method('getCurrentSeason')
            ->willReturn($season);

        return $seasonService;
    }

    /**
     * @return Collection<int, AffixGroup> All affix groups of the season that have exactly the given affixes
     */
    private function getAffixGroupsWithTheSameAffixes(int $seasonId, string $affixString): Collection
    {
        $affixKeys = collect(explode(', ', $affixString))->sort()->values();

        return Season::findOrFail($seasonId)->affixGroups->filter(
            static fn(AffixGroup $affixGroup): bool => $affixGroup->affixes->pluck('key')->sort()->values()
                ->toArray() === $affixKeys->toArray(),
        );
    }

    /**
     * @param  AffixGroup|null                             $currentAffixGroup Which affix group is active this week
     * @return SeasonAffixGroupServiceInterface|MockObject
     * @throws Exception
     */
    private function getSeasonAffixGroupService(
        ?AffixGroup $currentAffixGroup,
    ): MockObject|SeasonAffixGroupServiceInterface {
        $seasonAffixGroupService = ServiceFixtures::getSeasonAffixGroupServiceMock(
            $this,
            null,
            null,
            ['getCurrentAffixGroup'],
        );
        $seasonAffixGroupService->method('getCurrentAffixGroup')
            ->willReturn($currentAffixGroup);

        return $seasonAffixGroupService;
    }
}
