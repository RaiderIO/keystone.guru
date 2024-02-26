<?php

namespace Tests\Unit\App\Logic\SimulationCraft;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Logic\SimulationCraft\RaidEventPullEnemy;
use App\Models\Enemy;
use Tests\Unit\Fixtures\Traits\CreatesEnemy;
use Tests\Unit\Fixtures\Traits\CreatesNpc;
use Tests\Unit\Fixtures\Traits\CreatesRaidEventPullEnemy;
use Tests\Unit\Fixtures\Traits\CreatesSimulationCraftRaidEventsOptions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class RaidEventPullEnemyTest extends TestCase
{
    use CreatesNpc;
    use CreatesEnemy;
    use CreatesSimulationCraftRaidEventsOptions;
    use CreatesRaidEventPullEnemy;

    private const NPC_ID          = 123123;
    private const NPC_NAME        = 'My NPC';
    private const NPC_BASE_HEALTH = 439587;

    private const ENEMY_ID = 51234123;

    private const ENEMY_INDEX_IN_PULL = 1;

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @param RaidEventPullEnemy|MockObject $raidEventPullEnemy
     * @return void
     */
    private function mockRaidEventPullEnemyCalculateHealth($raidEventPullEnemy): void
    {
        $raidEventPullEnemy
            ->expects($this->once())
            ->method('calculateHealth')
            ->willReturn(self::NPC_BASE_HEALTH);
    }

    /**
     * @param array|null $npcAttributes
     * @param array|null $enemyAttributes
     * @return RaidEventPullEnemy|MockObject
     */
    private function createRaidEventPullEnemyWithParams(?array $npcAttributes = null, ?array $enemyAttributes = null, int $enemyIndexInPull = self::ENEMY_INDEX_IN_PULL): MockObject
    {
        $npc           = $this->createNpc($npcAttributes ?? [
            'id'          => self::NPC_ID,
            'name'        => self::NPC_NAME,
            'base_health' => self::NPC_BASE_HEALTH,
        ]);
        $enemy         = $this->createEnemy($enemyAttributes ?? [
            'id' => self::ENEMY_ID,
        ]);
        $enemy->npc_id = $npc->id;
        $enemy->npc    = $npc;

        $options = $this->createSimulationCraftRaidEventsOptions();
        return $this->createRaidEventPullEnemy(['calculateHealth'], $options, $enemy, $enemyIndexInPull);
    }
}
