<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\DF;

use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('HallsOfInfusion')]
class APICombatLogControllerCorrectEventsHallsOfInfusionTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::HALLS_OF_INFUSION->value;
    }

    #[Test]
    public function create_givenHallsOfInfusionBuntenNoRoster22Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('DF/df_s2_halls_of_infusion_bunten_no_roster_22');
    }
}
