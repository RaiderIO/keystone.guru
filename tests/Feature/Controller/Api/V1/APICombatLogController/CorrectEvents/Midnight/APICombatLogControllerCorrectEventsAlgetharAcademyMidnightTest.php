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
#[Group('AlgetharAcademyMidnight')]
class APICombatLogControllerCorrectEventsAlgetharAcademyMidnightTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ALGETH_AR_ACADEMY_MIDNIGHT;
    }

    #[Test]
    public function create_givenAlgetharAcademyMidnightPreseasonJson_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Midnight/midnight_s1_algethar_academy_preseason');
    }
}
