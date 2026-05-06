<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\TWW;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('TheDawnbreaker')]
class APICombatLogControllerCorrectEventsTheDawnbreakerTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_DAWNBREAKER;
    }

    #[Test]
    public function create_givenTheDawnbreaker4Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s1_the_dawnbreaker_4');
    }
}
