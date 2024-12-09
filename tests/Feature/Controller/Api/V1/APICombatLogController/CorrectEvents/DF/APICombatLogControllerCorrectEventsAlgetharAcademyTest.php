<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\DF;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

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
    public function create_givenAlgetharAcademyBuntenNoRoster16Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('DF/df_s4_algethar_academy_bunten_no_roster_16');
    }
}
