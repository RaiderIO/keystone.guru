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
#[Group('TazaveshStreetsOfWonder')]
class APICombatLogControllerCorrectEventsTazaveshStreetsOfWonderTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_TAZAVESH_STREETS_OF_WONDER;
    }

    #[Test]
    public function create_givenTazaveshStreetsOfWonder7Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('SL/tww_s3_ptr_tazavesh_streets_of_wonder_7');
    }
}
