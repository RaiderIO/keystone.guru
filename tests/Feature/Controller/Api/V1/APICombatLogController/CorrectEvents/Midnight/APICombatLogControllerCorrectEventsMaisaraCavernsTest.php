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
#[Group('MaisaraCaverns')]
class APICombatLogControllerCorrectEventsMaisaraCavernsTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_MAISARA_CAVERNS;
    }

    #[Test]
    public function create_givenMaisaraCavernsPreseasonJson_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Midnight/midnight_s1_maisara_caverns_preseason');
    }
}
