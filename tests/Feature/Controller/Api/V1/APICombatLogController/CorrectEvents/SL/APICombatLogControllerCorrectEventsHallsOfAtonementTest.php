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
#[Group('HallsOfAtonement')]
class APICombatLogControllerCorrectEventsHallsOfAtonementTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_HALLS_OF_ATONEMENT;
    }

    #[Test]
    public function create_givenHallsOfAtonement7Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('SL/tww_s3_ptr_halls_of_atonement_7');
    }
}
