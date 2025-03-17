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
#[Group('PrioryOfTheSacredFlame')]
class APICombatLogControllerCorrectEventsPrioryOfTheSacredFlameTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_PRIORY_OF_THE_SACRED_FLAME;
    }

    #[Test]
    public function create_givenPrioryOfTheSacredFlame14Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s2_priory_of_the_sacred_flame_14');
    }
}
