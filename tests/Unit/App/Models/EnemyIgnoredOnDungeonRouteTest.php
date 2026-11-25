<?php

namespace Tests\Unit\App\Models;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * {@see Enemy::isIgnoredOnDungeonRoute()} decides which enemies the MCP pull tools let a pull kill, and must hide
 * exactly what the route editor's Enemy.shouldBeIgnored() hides.
 */
#[Group('Models')]
final class EnemyIgnoredOnDungeonRouteTest extends PublicTestCase
{
    private const int AWAKENED_NPC_ID = 161124;

    private const int REGULAR_NPC_ID = 1;

    #[Test]
    public function isIgnoredOnDungeonRoute_givenARegularEnemy_returnsFalse(): void
    {
        // Arrange
        $enemy = $this->makeEnemy();

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute(), false, false);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('awakenedEnemyProvider')]
    public function isIgnoredOnDungeonRoute_givenAnAwakenedEnemy_returnsTrueOnlyWhenLinkedToTheLastBossAndUnpulled(
        bool $isLinkedToLastBoss,
        bool $isPulled,
        bool $expectedIgnored,
    ): void {
        // Arrange
        $enemy = $this->makeEnemy(npcId: self::AWAKENED_NPC_ID);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute([Affix::AFFIX_AWAKENED]), $isLinkedToLastBoss, $isPulled);

        // Assert
        $this->assertSame($expectedIgnored, $result);
    }

    /** @return array<string, array{0: bool, 1: bool, 2: bool}> */
    public static function awakenedEnemyProvider(): array
    {
        return [
            'linked to the last boss, unpulled'     => [true, false, true],
            'linked to the last boss, pulled'       => [true, true, false],
            'not linked to the last boss, unpulled' => [false, false, false],
        ];
    }

    #[Test]
    public function isIgnoredOnDungeonRoute_givenARegularNpcLinkedToTheLastBossAndUnpulled_returnsFalse(): void
    {
        // Arrange
        $enemy = $this->makeEnemy();

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute(), true, false);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('affixBoundSeasonalTypeProvider')]
    public function isIgnoredOnDungeonRoute_givenAnAffixBoundSeasonalTypeWithoutItsAffix_returnsTrue(string $seasonalType, string $affix): void
    {
        // Arrange
        $enemy = $this->makeEnemy(seasonalType: $seasonalType);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute([Affix::AFFIX_FORTIFIED]), false, false);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[DataProvider('affixBoundSeasonalTypeProvider')]
    public function isIgnoredOnDungeonRoute_givenAnAffixBoundSeasonalTypeWithItsAffix_returnsFalse(string $seasonalType, string $affix): void
    {
        // Arrange
        $enemy = $this->makeEnemy(seasonalType: $seasonalType);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute([Affix::AFFIX_FORTIFIED, $affix]), false, false);

        // Assert
        $this->assertFalse($result);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function affixBoundSeasonalTypeProvider(): array
    {
        return [
            'beguiling' => [Enemy::SEASONAL_TYPE_BEGUILING, Affix::AFFIX_BEGUILING],
            'awakened'  => [Enemy::SEASONAL_TYPE_AWAKENED, Affix::AFFIX_AWAKENED],
            'tormented' => [Enemy::SEASONAL_TYPE_TORMENTED, Affix::AFFIX_TORMENTED],
            'encrypted' => [Enemy::SEASONAL_TYPE_ENCRYPTED, Affix::AFFIX_ENCRYPTED],
        ];
    }

    #[Test]
    #[DataProvider('shroudedAffixProvider')]
    public function isIgnoredOnDungeonRoute_givenANoShroudedEnemy_returnsTrueOnlyWithShrouded(bool $hasShroudedAffix): void
    {
        // Arrange
        $enemy = $this->makeEnemy(seasonalType: Enemy::SEASONAL_TYPE_NO_SHROUDED);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute($hasShroudedAffix ? [Affix::AFFIX_SHROUDED] : []), false, false);

        // Assert
        $this->assertSame($hasShroudedAffix, $result);
    }

    #[Test]
    #[DataProvider('shroudedAffixProvider')]
    public function isIgnoredOnDungeonRoute_givenAnMdtPlaceholder_returnsTrue(bool $hasShroudedAffix): void
    {
        // Arrange
        $enemy = $this->makeEnemy(seasonalType: Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute($hasShroudedAffix ? [Affix::AFFIX_SHROUDED] : []), false, false);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[DataProvider('shroudedNpcProvider')]
    public function isIgnoredOnDungeonRoute_givenAShroudedEnemy_returnsTrueOnlyForTheReplacingNpcsWithoutShrouded(
        int  $npcId,
        bool $hasShroudedAffix,
        bool $expectedIgnored,
    ): void {
        // Arrange
        $enemy = $this->makeEnemy(npcId: $npcId, seasonalType: Enemy::SEASONAL_TYPE_SHROUDED);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute($hasShroudedAffix ? [Affix::AFFIX_SHROUDED] : []), false, false);

        // Assert
        $this->assertSame($expectedIgnored, $result);
    }

    /** @return array<string, array{0: bool}> */
    public static function shroudedAffixProvider(): array
    {
        return [
            'with shrouded'    => [true],
            'without shrouded' => [false],
        ];
    }

    /** @return array<string, array{0: int, 1: bool, 2: bool}> */
    public static function shroudedNpcProvider(): array
    {
        return [
            'nathrezim infiltrator without shrouded' => [Npc::NPC_ID_NATHREZIM_INFILTRATOR, false, true],
            'zul\'gamux without shrouded'            => [Npc::NPC_ID_ZUL_GAMUX, false, true],
            'nathrezim infiltrator with shrouded'    => [Npc::NPC_ID_NATHREZIM_INFILTRATOR, true, false],
            'regular npc without shrouded'           => [self::REGULAR_NPC_ID, false, false],
        ];
    }

    #[Test]
    #[DataProvider('dungeonDifficultyProvider')]
    public function isIgnoredOnDungeonRoute_givenADifficultyBoundEnemy_returnsTrueOnlyForAnotherDifficulty(
        ?int $routeDungeonDifficulty,
        bool $expectedIgnored,
    ): void {
        // Arrange
        $enemy = $this->makeEnemy(dungeonDifficulty: 2);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute(dungeonDifficulty: $routeDungeonDifficulty), false, false);

        // Assert
        $this->assertSame($expectedIgnored, $result);
    }

    /** @return array<string, array{0: int|null, 1: bool}> */
    public static function dungeonDifficultyProvider(): array
    {
        return [
            'the same difficulty' => [2, false],
            'another difficulty'  => [1, true],
            'a route without one' => [null, true],
        ];
    }

    #[Test]
    public function isIgnoredOnDungeonRoute_givenACritter_returnsTrue(): void
    {
        // Arrange
        $enemy = $this->makeEnemy(npcTypeId: NpcType::CRITTER);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute(), false, false);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isIgnoredOnDungeonRoute_givenACritterOfTheRoutesDifficulty_returnsFalse(): void
    {
        // Arrange
        $enemy = $this->makeEnemy(npcTypeId: NpcType::CRITTER, dungeonDifficulty: 2);

        // Act
        $result = $enemy->isIgnoredOnDungeonRoute($this->makeDungeonRoute(dungeonDifficulty: 2), false, false);

        // Assert
        $this->assertFalse($result);
    }

    private function makeEnemy(
        int     $npcId = self::REGULAR_NPC_ID,
        ?string $seasonalType = null,
        int     $npcTypeId = NpcType::HUMANOID,
        ?int    $dungeonDifficulty = null,
    ): Enemy {
        $npc = new Npc();
        $npc->forceFill([
            'id'                => $npcId,
            'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
            'npc_type_id'       => $npcTypeId,
        ]);

        $enemy = new Enemy();
        $enemy->forceFill([
            'npc_id'             => $npcId,
            'seasonal_type'      => $seasonalType,
            'dungeon_difficulty' => $dungeonDifficulty,
        ]);
        $enemy->setRelation('npc', $npc);

        return $enemy;
    }

    /**
     * @param array<int, string> $affixKeys
     */
    private function makeDungeonRoute(array $affixKeys = [], ?int $dungeonDifficulty = null): DungeonRoute
    {
        $affixGroup = new AffixGroup();
        $affixGroup->setRelation('affixes', new EloquentCollection(array_map(static function (string $key) {
            $affix = new Affix();
            $affix->forceFill(['key' => $key]);

            return $affix;
        }, $affixKeys)));

        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->forceFill(['dungeon_difficulty' => $dungeonDifficulty]);
        $dungeonRoute->setRelation('affixes', new EloquentCollection([$affixGroup]));

        return $dungeonRoute;
    }
}
