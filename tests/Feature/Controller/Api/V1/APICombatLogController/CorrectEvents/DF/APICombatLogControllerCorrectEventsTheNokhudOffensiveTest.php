<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\DF;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
class APICombatLogControllerCorrectEventsTheNokhudOffensiveTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_NOKHUD_OFFENSIVE;
    }

    #[Test]
    public function create_givenTheNokhudOffensiveNoRoster14Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('DF/df_s4_the_nokhud_offensive_no_roster_14');
    }

    #[Test]
    public function create_givenTheNokhudOffensiveNoRoster8Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('DF/df_s4_the_nokhud_offensive_no_roster_8');
    }
}
