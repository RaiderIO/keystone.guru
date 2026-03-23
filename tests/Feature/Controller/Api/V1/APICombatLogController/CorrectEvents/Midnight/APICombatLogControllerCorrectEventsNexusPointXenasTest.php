<?php

namespace Controller\Api\V1\APICombatLogController\CorrectEvents\Midnight;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('NexusPointXenas')]
class APICombatLogControllerCorrectEventsNexusPointXenasTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_NEXUS_POINT_XENAS;
    }

    #[Test]
    public function create_givenNexusPointXenasPreseasonJson_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Midnight/midnight_s1_nexus_point_xenas_preseason');
    }
}
