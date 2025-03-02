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
#[Group('AraKaraCityOfEchoes')]
class APICombatLogControllerCorrectEventsAraKaraCityOfEchoesTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_ARA_KARA_CITY_OF_ECHOES;
    }

    #[Test]
    public function create_givenAraKaraCityOfEchoes3Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s1_ara_kara_city_of_echoes_3');
    }
}
