<?php

namespace Tests\Feature\Traits;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

/**
 * Locks the guarantees {@see ProvidesDungeon} makes.
 *
 * The point of this file is that seeded mapping data drifting out from under the test suite fails
 * loudly here - naming the requirement that stopped being satisfiable - instead of showing up as an
 * intermittent failure in one of the ~40 tests that pick a dungeon.
 */
#[Group('ProvidesDungeon')]
final class ProvidesDungeonTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function findDungeon_givenNoRequirements_returnsDungeonWithCurrentMappingVersion(): void
    {
        // Arrange & Act
        [$dungeon, $mappingVersion] = $this->findDungeon();

        // Assert
        self::assertSame($dungeon->id, $mappingVersion->dungeon_id);
        self::assertGreaterThanOrEqual(1, $dungeon->floorsForMapFacade($mappingVersion)->active()->count());
    }

    #[Test]
    public function findDungeon_givenFacadeEnabledFalse_returnsDungeonWithoutAnyFacadeFloor(): void
    {
        // Arrange & Act
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false);

        // Assert
        self::assertFalse((bool)$mappingVersion->facade_enabled);
        self::assertFalse($dungeon->floors()->where('facade', 1)->exists());
        self::assertGreaterThanOrEqual(1, $dungeon->floors()->where('facade', 0)->where('active', 1)->count());
    }

    #[Test]
    public function findDungeon_givenFacadeEnabledTrue_returnsDungeonWithActiveFacadeFloor(): void
    {
        // Arrange & Act
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true);

        // Assert
        self::assertTrue((bool)$mappingVersion->facade_enabled);
        self::assertGreaterThanOrEqual(1, $dungeon->floors()->where('facade', 1)->where('active', 1)->count());
    }

    /**
     * The active() filter mirrors how production counts renderable floors
     * (`floorsForMapFacade(...)->active()`), which is what thumbnail queueing asserts against.
     */
    #[Test]
    #[DataProvider('activeFloorBoundsProvider')]
    public function findDungeon_givenActiveFloorBounds_returnsDungeonWithinThoseBounds(int $min, ?int $max): void
    {
        // Arrange & Act
        [$dungeon, $mappingVersion] = $this->findDungeon(
            facadeEnabled:   false,
            minActiveFloors: $min,
            maxActiveFloors: $max,
        );

        // Assert
        $activeFloorCount = $dungeon->floorsForMapFacade($mappingVersion)->active()->count();

        self::assertGreaterThanOrEqual($min, $activeFloorCount);
        if ($max !== null) {
            self::assertLessThanOrEqual($max, $activeFloorCount);
        }
    }

    /** @return array<string, array{0: int, 1: int|null}> */
    public static function activeFloorBoundsProvider(): array
    {
        return [
            'at least one' => [1, null],
            'exactly one'  => [1, 1],
            'at least two' => [2, null],
            'at most two'  => [1, 2],
        ];
    }

    #[Test]
    public function findDungeon_givenMinEnemies_returnsDungeonWhoseCurrentMappingVersionHasThatManyEnemies(): void
    {
        // Arrange & Act
        [, $mappingVersion] = $this->findDungeon(challengeMode: true, minEnemies: 5);

        // Assert
        self::assertGreaterThanOrEqual(5, $mappingVersion->enemies()->count());
    }

    #[Test]
    public function findDungeon_givenMinEnemyPacks_returnsDungeonWhoseCurrentMappingVersionHasThatManyPacks(): void
    {
        // Arrange & Act
        [, $mappingVersion] = $this->findDungeon(challengeMode: true, minEnemyPacks: 1);

        // Assert
        self::assertGreaterThanOrEqual(1, $mappingVersion->enemyPacks()->count());
    }

    #[Test]
    public function findDungeon_givenChallengeMode_returnsMythicPlusDungeon(): void
    {
        // Arrange & Act
        [$dungeon] = $this->findDungeon(challengeMode: true);

        // Assert
        self::assertNotNull($dungeon->challenge_mode_id);
    }

    /**
     * The dungeon that fails the requirement is made, not found.
     *
     * Both of these requirements are near-universally satisfied by seeded data - 154 of 155 dungeons
     * have an active default floor - so an unconstrained assertion would pass even if the filter
     * were a no-op. Worse, what *doesn't* satisfy them differs per environment: `dungeons.json`
     * carries no `active` key (the column is in Dungeon::$hidden, so mapping:save omits it), so a
     * freshly seeded CI database has no inactive dungeon at all, while a long-lived developer
     * database drifts into having them. Taking a dungeon that qualifies in every other respect and
     * breaking exactly one thing about it makes that thing the sole possible reason for rejection,
     * identically in every environment.
     */
    #[Test]
    public function findDungeon_givenRequireDefaultFloor_skipsDungeonsWithoutOne(): void
    {
        // Arrange
        [$expected, $otherwiseValid] = $this->twoInterchangeableDungeons(requireDefaultFloor: true);

        $defaultFloorIds = $otherwiseValid->floors()->where('default', 1)->pluck('id')->all();
        self::assertNotEmpty($defaultFloorIds);
        $otherwiseValid->floors()->whereIn('id', $defaultFloorIds)->update(['default' => false]);

        try {
            // Act
            [$dungeon] = $this->findDungeon(
                requireDefaultFloor: true,
                constraint:          static fn(Builder $query) => $query->whereIn('dungeons.id', [$otherwiseValid->id, $expected->id]),
            );

            // Assert
            self::assertSame($expected->id, $dungeon->id);
        } finally {
            $otherwiseValid->floors()->whereIn('id', $defaultFloorIds)->update(['default' => true]);
        }
    }

    /** @see self::findDungeon_givenRequireDefaultFloor_skipsDungeonsWithoutOne() for why the inactive dungeon is created rather than searched for. */
    #[Test]
    public function findDungeon_givenDungeonActive_skipsInactiveDungeons(): void
    {
        // Arrange
        [$expected, $otherwiseValid] = $this->twoInterchangeableDungeons(dungeonActive: true);

        $otherwiseValid->update(['active' => false]);

        try {
            // Act
            [$dungeon] = $this->findDungeon(
                dungeonActive: true,
                constraint:    static fn(Builder $query) => $query->whereIn('dungeons.id', [$otherwiseValid->id, $expected->id]),
            );

            // Assert
            self::assertSame($expected->id, $dungeon->id);
            self::assertTrue((bool)$dungeon->active);
        } finally {
            $otherwiseValid->update(['active' => true]);
        }
    }

    /**
     * Two distinct dungeons that both satisfy the given requirement, so that breaking one of them
     * leaves the other as the only correct answer.
     *
     * @return array{0: Dungeon, 1: Dungeon}
     */
    private function twoInterchangeableDungeons(bool $requireDefaultFloor = false, ?bool $dungeonActive = null): array
    {
        [$first]  = $this->findDungeon(dungeonActive: $dungeonActive, requireDefaultFloor: $requireDefaultFloor);
        [$second] = $this->findDungeon(
            dungeonActive:       $dungeonActive,
            requireDefaultFloor: $requireDefaultFloor,
            constraint:          static fn(Builder $query) => $query->where('dungeons.id', '!=', $first->id),
        );

        return [$first, $second];
    }

    #[Test]
    public function findDungeon_givenChallengeModeFalse_returnsNonMythicPlusDungeon(): void
    {
        // Arrange & Act
        [$dungeon] = $this->findDungeon(challengeMode: false);

        // Assert
        self::assertNull($dungeon->challenge_mode_id);
    }

    #[Test]
    public function findDungeon_givenSpeedrunEnabled_returnsSpeedrunDungeon(): void
    {
        // Arrange & Act
        [$dungeon] = $this->findDungeon(speedrunEnabled: true);

        // Assert
        self::assertTrue((bool)$dungeon->speedrun_enabled);
    }

    /**
     * `getCurrentMappingVersion()` falls back to the acting user's game version, then the default,
     * then simply the highest version - so asking for a game version is only meaningful if the
     * resolved mapping version is verified to belong to it. Several seeded Mythic+ dungeons (e.g.
     * Upper Blackrock Spire) carry Classic mapping versions only, and would otherwise satisfy a
     * Retail request.
     */
    #[Test]
    public function findDungeon_givenGameVersion_skipsDungeonsWhoseCurrentMappingVersionIsForAnotherGameVersion(): void
    {
        // Arrange
        $retail   = GameVersion::getDefaultGameVersion();
        $otherIds = [];
        foreach (Dungeon::query()->disableCache()->get() as $candidate) {
            /** @var Dungeon $candidate */
            $mappingVersion = $candidate->getCurrentMappingVersion();

            if ($mappingVersion !== null && $mappingVersion->game_version_id !== $retail->id) {
                $otherIds[] = $candidate->id;
            }
        }
        self::assertNotEmpty($otherIds, 'Expected seeded dungeons whose current mapping version is not Retail');

        [$expected] = $this->findDungeon(gameVersion: $retail);
        $poolIds    = [...$otherIds, $expected->id];

        // Act
        [$dungeon, $mappingVersion] = $this->findDungeon(
            gameVersion: $retail,
            constraint:  static fn(Builder $query) => $query->whereIn('dungeons.id', $poolIds),
        );

        // Assert
        self::assertSame($expected->id, $dungeon->id);
        self::assertSame($retail->id, $mappingVersion->game_version_id);
    }

    /**
     * The regression that matters most: the scan must walk *past* unsuitable candidates rather than
     * sampling and hoping. Restricting the pool to several dungeons that cannot satisfy the
     * requirement plus exactly one that can means a sampling implementation would fail most of the
     * time, while an exhaustive one always returns the single suitable dungeon.
     */
    #[Test]
    public function findDungeon_givenPoolOfMostlyUnsuitableDungeons_skipsThemAndReturnsTheSuitableOne(): void
    {
        // Arrange
        $unsuitableIds = $this->dungeonIdsWithoutEnemiesOnCurrentMappingVersion();
        self::assertNotEmpty($unsuitableIds, 'Expected seeded dungeons whose current mapping version has no enemies');

        [$suitable] = $this->findDungeon(minEnemies: 1);
        $poolIds    = [...$unsuitableIds, $suitable->id];

        // Act & Assert - repeated because the scan order is shuffled; every order must succeed
        for ($attempt = 0; $attempt < 10; $attempt++) {
            [$dungeon] = $this->findDungeon(
                minEnemies: 1,
                constraint: static fn(Builder $query) => $query->whereIn('dungeons.id', $poolIds),
            );

            self::assertSame($suitable->id, $dungeon->id);
        }
    }

    #[Test]
    public function findDungeon_givenUnsatisfiableRequirements_throwsNamingTheRequirements(): void
    {
        // Arrange
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/minEnemies: 999999/');

        // Act
        $this->findDungeon(minEnemies: 999999);
    }

    #[Test]
    public function findDungeon_givenConstraintMatchingNothing_throwsReportingAnEmptySqlPool(): void
    {
        // Arrange
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/0 dungeon\(s\) matched the SQL criteria/');

        // Act
        $this->findDungeon(constraint: static fn(Builder $query) => $query->whereRaw('1 = 0'));
    }

    /**
     * A resolve() that legitimately resolves to a falsy value must still count as a match - only a
     * strictly null return rejects the candidate.
     */
    #[Test]
    public function findDungeon_givenResolveReturningFalse_acceptsTheCandidate(): void
    {
        // Arrange & Act
        [$dungeon, $mappingVersion, $match] = $this->findDungeon(resolve: static fn(): bool => false);

        // Assert
        self::assertFalse($match);
        self::assertSame($dungeon->id, $mappingVersion->dungeon_id);
    }

    #[Test]
    public function findDungeon_givenResolveReturningValue_exposesItAsTheThirdElement(): void
    {
        // Arrange & Act
        [$dungeon, $mappingVersion, $match] = $this->findDungeon(
            challengeMode: true,
            minEnemies:    1,
            resolve:       static fn(Dungeon $dungeon, MappingVersion $mappingVersion) => $mappingVersion->enemies()->first(),
        );

        // Assert
        self::assertInstanceOf(Enemy::class, $match);
        self::assertSame($mappingVersion->id, $match->mapping_version_id);
        self::assertSame($dungeon->id, $mappingVersion->dungeon_id);
    }

    #[Test]
    public function findDungeon_givenResolveRejectingEveryCandidate_throwsCountingTheRejections(): void
    {
        // Arrange
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/resolve: \d+/');

        // Act
        $this->findDungeon(resolve: static fn() => null);
    }

    #[Test]
    public function findDungeon_givenShuffleDisabled_returnsTheFirstMatchInQueryOrder(): void
    {
        // Arrange & Act - a stable ordering must produce a stable pick
        [$first] = $this->findDungeon(
            shuffle:    false,
            constraint: static fn(Builder $query) => $query->orderByDesc('dungeons.id'),
        );
        [$second] = $this->findDungeon(
            shuffle:    false,
            constraint: static fn(Builder $query) => $query->orderByDesc('dungeons.id'),
        );

        // Assert
        self::assertSame($first->id, $second->id);
    }

    /**
     * The scan needs a collection, but `preventLazyLoading()` only exempts models loaded on their
     * own - so the dungeon handed back must be re-fetched, or every caller touching a relation that
     * was not eager-loaded would start throwing.
     */
    #[Test]
    public function findDungeon_givenCallerLazyLoadsARelation_doesNotThrowALazyLoadingViolation(): void
    {
        // Arrange
        [$dungeon] = $this->findDungeon(
            speedrunEnabled: true,
            constraint:      static fn(Builder $query) => $query->whereHas('dungeonSpeedrunDifficulties'),
        );

        // Act
        $difficulties = $dungeon->getEnabledSpeedrunDifficulties();

        // Assert
        self::assertNotEmpty($difficulties);
    }

    /**
     * Every constraint a real call site passes must still resolve, so a shrinking seeded pool is
     * reported here rather than in whichever test happens to run first.
     *
     * @param (Closure(Builder<Dungeon>): mixed)|null $constraint
     */
    #[Test]
    #[DataProvider('realCallSiteConstraintProvider')]
    public function findDungeon_givenAConstraintUsedByARealCallSite_stillResolves(string $preset, ?Closure $constraint): void
    {
        // Arrange & Act
        $dungeon = match ($preset) {
            'exactlyOne' => $this->getDungeonWithExactlyOneNonFacadeFloor($constraint),
            'multiple'   => $this->getDungeonWithMultipleNonFacadeFloors($constraint),
            'facade'     => $this->getDungeonWithFacadeFloor($constraint),
            default      => $this->getDungeonWithNonFacadeFloor($constraint),
        };

        // Assert
        self::assertNotNull($dungeon->getCurrentMappingVersion());
    }

    /** @return array<string, array{0: string, 1: (Closure(Builder<Dungeon>): mixed)|null}> */
    public static function realCallSiteConstraintProvider(): array
    {
        return [
            'non-facade, unconstrained'         => ['nonFacade', null],
            'non-facade, mythic plus'           => ['nonFacade', static fn(Builder $query) => $query->whereNotNull('challenge_mode_id')],
            'non-facade, no speedrun'           => ['nonFacade', static fn(Builder $query) => $query->where('speedrun_enabled', false)],
            'non-facade, speedrun difficulties' => ['nonFacade', static fn(Builder $query) => $query->where('speedrun_enabled', true)->whereHas('dungeonSpeedrunDifficulties')],
            'non-facade, without demo routes'   => ['nonFacade', static fn(Builder $query) => $query->whereNotNull('challenge_mode_id')->whereDoesntHave('dungeonRoutes', static fn(Builder $routes) => $routes->where('demo', true))],
            'exactly one floor'                 => ['exactlyOne', null],
            'multiple floors'                   => ['multiple', null],
            'facade, unconstrained'             => ['facade', null],
            'facade, mythic plus'               => ['facade', static fn(Builder $query) => $query->whereNotNull('challenge_mode_id')],
        ];
    }

    /**
     * Dungeons whose *current* mapping version carries no enemies at all - the seeded shape behind
     * the #3679 / #3710 flakes.
     *
     * @return list<int>
     */
    private function dungeonIdsWithoutEnemiesOnCurrentMappingVersion(): array
    {
        $ids = [];

        foreach (Dungeon::query()->disableCache()->get() as $dungeon) {
            /** @var Dungeon $dungeon */
            $mappingVersion = $dungeon->getCurrentMappingVersion();

            if ($mappingVersion !== null && $mappingVersion->enemies()->doesntExist()) {
                $ids[] = $dungeon->id;
            }
        }

        return $ids;
    }
}
