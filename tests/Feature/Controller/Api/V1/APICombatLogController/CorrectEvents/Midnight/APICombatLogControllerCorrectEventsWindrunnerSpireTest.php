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
#[Group('WindrunnerSpire')]
class APICombatLogControllerCorrectEventsWindrunnerSpireTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_WINDRUNNER_SPIRE;
    }

    #[Test]
    public function create_givenWindrunnerSpirePreseasonJson_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Midnight/midnight_s1_windrunner_spire_preseason');
    }
}
