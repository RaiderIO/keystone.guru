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
#[Group('MistsOfTirnaScithe')]
class APICombatLogControllerCorrectEventsMistsOfTirnaScitheTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE;
    }

    #[Test]
    public function create_givenMistsOfTirnaScithe5Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('SL/tww_s1_mists_of_tirna_scithe_5');
    }
}
