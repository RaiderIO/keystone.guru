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
class APICombatLogControllerCorrectEventsCityOfThreadsTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_CITY_OF_THREADS;
    }

    #[Test]
    public function create_givenCityOfThreads5Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s1_city_of_threads_5');
    }
}
