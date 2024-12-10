<?php

namespace Controller\Api\V1\APICombatLogController\CorrectEvents\Cata;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
class APICombatLogControllerCorrectEventsGrimBatolTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_GRIM_BATOL;
    }

    #[Test]
    public function create_givenSiegeOfBoralus5Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Cata/tww_s1_grim_batol_6');
    }
}
