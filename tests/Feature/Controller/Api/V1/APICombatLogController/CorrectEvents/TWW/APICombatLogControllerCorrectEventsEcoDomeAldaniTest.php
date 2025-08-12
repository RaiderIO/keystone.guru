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
#[Group('EcoDomeAldani')]
class APICombatLogControllerCorrectEventsEcoDomeAldaniTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ECO_DOME_AL_DANI;
    }

    #[Test]
    public function create_givenEcoDomeAldani7Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s3_ptr_eco_dome_aldani_7');
    }
}
