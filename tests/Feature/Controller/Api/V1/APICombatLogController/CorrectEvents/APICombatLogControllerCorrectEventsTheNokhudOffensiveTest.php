<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
class APICombatLogControllerCorrectEventsTheNokhudOffensiveTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ALGETH_AR_ACADEMY;
    }

    #[Test]
    public function create_givenTheNokhudOffensive14Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('df_s4_the_nokhud_offensive_14');
    }

    #[Test]
    public function create_givenTheNokhudOffensive8Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('df_s4_the_nokhud_offensive_8');
    }
}
