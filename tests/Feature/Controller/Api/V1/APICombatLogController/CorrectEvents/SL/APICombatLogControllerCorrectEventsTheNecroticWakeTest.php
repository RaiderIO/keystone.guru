<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\SL;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('TheNecroticWake')]
class APICombatLogControllerCorrectEventsTheNecroticWakeTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_NECROTIC_WAKE;
    }

    #[Test]
    public function create_givenTheNecroticWake6Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('SL/tww_s1_the_necrotic_wake_6');
    }
}
