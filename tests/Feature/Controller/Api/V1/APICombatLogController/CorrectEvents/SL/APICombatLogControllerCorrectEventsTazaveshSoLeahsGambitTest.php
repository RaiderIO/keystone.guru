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
#[Group('TazaveshSoLeahsGambit')]
class APICombatLogControllerCorrectEventsTazaveshSoLeahsGambitTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_TAZAVESH_SO_LEAHS_GAMBIT;
    }

    #[Test]
    public function create_givenTazaveshSoLeahsGambit7Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('SL/tww_s3_ptr_tazavesh_so_leahs_gambit_7');
    }
}
