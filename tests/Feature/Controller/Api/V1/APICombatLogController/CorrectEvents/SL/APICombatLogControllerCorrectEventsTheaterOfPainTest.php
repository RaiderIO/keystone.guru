<?php

namespace Controller\Api\V1\APICombatLogController\CorrectEvents\SL;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('TheaterOfPain')]
class APICombatLogControllerCorrectEventsTheaterOfPainTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THEATER_OF_PAIN;
    }

    #[Test]
    public function create_givenTheaterOfPain14Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('SL/tww_s2_ptr_theater_of_pain_14');
    }
}
