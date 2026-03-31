<?php

namespace Controller\Api\V1\APICombatLogController\CorrectEvents\WotLK;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('PitOfSaron')]
class APICombatLogControllerCorrectEventsPitOfSaronTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_PIT_OF_SARON;
    }

    #[Test]
    public function create_givenMidnightS1PitOfSaron13Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('WotLK/midnight_s1_pit_of_saron_13');
    }
}
