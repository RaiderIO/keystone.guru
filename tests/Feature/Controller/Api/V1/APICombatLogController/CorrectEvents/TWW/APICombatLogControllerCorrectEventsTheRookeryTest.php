<?php

namespace Controller\Api\V1\APICombatLogController\CorrectEvents\TWW;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('TheRookery')]
class APICombatLogControllerCorrectEventsTheRookeryTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_ROOKERY;
    }

    #[Test]
    public function create_givenTheRookery2Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s2_the_rookery_13');
    }
}
