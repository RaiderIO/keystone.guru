<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
class APICombatLogControllerCorrectEventsAlgetharAcademyTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ALGETH_AR_ACADEMY;
    }

    #[Test]
    public function create_givenAlgetharAcademyBunten16Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('df_s4_algethar_academy_bunten_16');
    }
}
