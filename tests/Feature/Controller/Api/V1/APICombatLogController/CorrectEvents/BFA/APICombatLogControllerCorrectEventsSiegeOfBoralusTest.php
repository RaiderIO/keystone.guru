<?php

namespace Controller\Api\V1\APICombatLogController\CorrectEvents\BFA;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('SiegeOfBoralus')]
class APICombatLogControllerCorrectEventsSiegeOfBoralusTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_SIEGE_OF_BORALUS;
    }

    #[Test]
    public function create_givenSiegeOfBoralus5Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('BFA/tww_s1_siege_of_boralus_5');
    }
}
