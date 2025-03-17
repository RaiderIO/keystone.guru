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
#[Group('DarkflameCleft')]
class APICombatLogControllerCorrectEventsDarkflameCleftTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_DARKFLAME_CLEFT;
    }

    #[Test]
    public function create_givenDarkflameCleft2Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s2_darkflame_cleft_5');
    }
}
