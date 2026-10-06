<?php

namespace Tests\Unit\App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventPullEnemy;
use App\Models\Affix;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\Traits\CreatesEnemy;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\Fixtures\Traits\CreatesRaidEventPullEnemy;
use Tests\Fixtures\Traits\CreatesSimulationCraftRaidEventsOptions;
use Tests\TestCase;

final class RaidEventPullEnemyTest extends TestCase
{
    use CreatesEnemy;
    use CreatesNpc;
    use CreatesRaidEventPullEnemy;
    use CreatesSimulationCraftRaidEventsOptions;

    private const int NPC_ID              = 123123;
    private const string NPC_NAME         = 'My NPC';
    private const int NPC_BASE_HEALTH     = 439587;
    private const int ENEMY_ID            = 51234123;
    private const int ENEMY_INDEX_IN_PULL = 1;

    #[Test]
    #[Group('SimulationCraft')]
    public function toString_GivenNormalNpc_ShouldReturnRegularString(): void
    {
        // Arrange
        $raidEventPullEnemy = $this->createRaidEventPullEnemyWithParams();
        $this->mockRaidEventPullEnemyCalculateHealth($raidEventPullEnemy);

        // Act
        $string = $raidEventPullEnemy->toString();

        // Assert
        Assert::assertEquals(sprintf('"%s_%d":%d', Str::slug(self::NPC_NAME), self::ENEMY_INDEX_IN_PULL, self::NPC_BASE_HEALTH), $string);
    }

    #[Test]
    #[Group('SimulationCraft')]
    public function toString_GivenShroudedNpc_ShouldReturnBountyString(): void
    {
        // Arrange
        $raidEventPullEnemy = $this->createRaidEventPullEnemyWithParams(null, [
            'id'            => self::ENEMY_ID,
            'seasonal_type' => Enemy::SEASONAL_TYPE_SHROUDED,
        ]);
        $this->mockRaidEventPullEnemyCalculateHealth($raidEventPullEnemy);

        // Act
        $string = $raidEventPullEnemy->toString();

        // Assert
        Assert::assertEquals(sprintf('"BOUNTY1_%s_%d":%d', Str::slug(self::NPC_NAME), self::ENEMY_INDEX_IN_PULL, self::NPC_BASE_HEALTH), $string);
    }

    #[Test]
    #[Group('SimulationCraft')]
    public function toString_GivenShroudedZulGamuxNpc_ShouldReturnBountyString(): void
    {
        // Arrange
        $raidEventPullEnemy = $this->createRaidEventPullEnemyWithParams(null, [
            'id'            => self::ENEMY_ID,
            'seasonal_type' => Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX,
        ]);
        $this->mockRaidEventPullEnemyCalculateHealth($raidEventPullEnemy);

        // Act
        $string = $raidEventPullEnemy->toString();

        // Assert
        Assert::assertEquals(sprintf('"BOUNTY3_%s_%d":%d', Str::slug(self::NPC_NAME), self::ENEMY_INDEX_IN_PULL, self::NPC_BASE_HEALTH), $string);
    }

    #[Test]
    #[Group('SimulationCraft')]
    public function toString_GivenBossNpc_ShouldReturnBossString(): void
    {
        // Arrange
        $raidEventPullEnemy = $this->createRaidEventPullEnemyWithParams([
            'id'                => self::NPC_ID,
            'name'              => self::NPC_NAME,
            'base_health'       => self::NPC_BASE_HEALTH,
            'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
        ]);
        $this->mockRaidEventPullEnemyCalculateHealth($raidEventPullEnemy);

        // Act
        $string = $raidEventPullEnemy->toString();

        // Assert
        Assert::assertEquals(sprintf('"BOSS_%s_%d":%d', Str::slug(self::NPC_NAME), self::ENEMY_INDEX_IN_PULL, self::NPC_BASE_HEALTH), $string);
    }

    #[Test]
    #[Group('SimulationCraft')]
    public function calculateHealth_GivenHpPercent_ShouldScaleTheKeyLevelHealthOfTheNpc(): void
    {
        // Arrange
        $gameVersion    = new GameVersion();
        $mappingVersion = new MappingVersion();
        $mappingVersion->setRelation('gameVersion', $gameVersion);
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->setRelation('mappingVersion', $mappingVersion);
        $options = $this->createSimulationCraftRaidEventsOptions([
            'key_level'  => 12,
            'affix'      => SimulationCraftRaidEventsOptions::AFFIX_FORTIFIED,
            'hp_percent' => 50,
        ]);
        $options->setRelation('dungeonRoute', $dungeonRoute);

        $npc = $this->createPartialMock(Npc::class, ['calculateHealthForKey']);
        $npc->expects($this->once())
            ->method('calculateHealthForKey')
            ->with($gameVersion, 12, [Affix::AFFIX_FORTIFIED])
            ->willReturn(1001.0);

        $raidEventPullEnemy = new RaidEventPullEnemy($options, $this->createEnemy(), self::ENEMY_INDEX_IN_PULL);

        // Act
        $health = $raidEventPullEnemy->calculateHealth($options, $npc);

        // Assert
        Assert::assertSame(500, $health);
    }

    /**
     * @param RaidEventPullEnemy|MockObject $raidEventPullEnemy
     */
    private function mockRaidEventPullEnemyCalculateHealth($raidEventPullEnemy): void
    {
        $raidEventPullEnemy
            ->expects($this->once())
            ->method('calculateHealth')
            ->willReturn(self::NPC_BASE_HEALTH);
    }

    /**
     * @param  array<string, mixed>|null     $npcAttributes
     * @param  array<string, mixed>|null     $enemyAttributes
     * @return MockObject&RaidEventPullEnemy
     */
    private function createRaidEventPullEnemyWithParams(?array $npcAttributes = null, ?array $enemyAttributes = null, int $enemyIndexInPull = self::ENEMY_INDEX_IN_PULL): MockObject
    {
        $npc = $this->createNpc($npcAttributes ?? [
            'id'          => self::NPC_ID,
            'name'        => self::NPC_NAME,
            'base_health' => self::NPC_BASE_HEALTH,
        ]);
        $enemy = $this->createEnemy($enemyAttributes ?? [
            'id' => self::ENEMY_ID,
        ]);
        $enemy->npc_id = $npc->id;
        $enemy->npc    = $npc;

        $options = $this->createSimulationCraftRaidEventsOptions();

        return $this->createRaidEventPullEnemy(['calculateHealth'], $options, $enemy, $enemyIndexInPull);
    }
}
