<?php

namespace Tests\Unit\Traits;

use App\Logic\SimulationCraft\RaidEventPullEnemy;
use App\Models\Enemy;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * @mixin TestCase
 * @mixin CreatesSimulationCraftRaidEventsOptions
 * @mixin CreatesEnemy
 */
trait CreatesRaidEventPullEnemy
{
    /**
     * @return RaidEventPullEnemy|MockObject
     */
    public function createRaidEventPullEnemy(array $methodsToMock = [], SimulationCraftRaidEventsOptions $options = null, Enemy $enemy = null, int $enemyIndexInPull = 0)
    {
        return $this->getMockBuilder(RaidEventPullEnemy::class)
            ->setConstructorArgs([
                    $options ?? $this->createSimulationCraftRaidEventsOptions(),
                    $enemy ?? $this->createEnemy(),
                    $enemyIndexInPull,
            ])
            ->onlyMethods($methodsToMock)
            ->getMock();
    }
}
