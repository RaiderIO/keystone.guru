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
#[Group('Skyreach')]
class APICombatLogControllerCorrectEventsSkyreachTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_SKYREACH;
    }

    #[Test]
    public function create_givenMidnightS1SkyreachPreseasonJson_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Cata/midnight_s1_skyreach_preseason');
    }
}
