<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\Cata;

use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('GrimBatol')]
class APICombatLogControllerCorrectEventsGrimBatolTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::GRIM_BATOL->value;
    }

    #[Test]
    public function create_givenGrimBatol6Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Cata/tww_s1_grim_batol_6');
    }
}
