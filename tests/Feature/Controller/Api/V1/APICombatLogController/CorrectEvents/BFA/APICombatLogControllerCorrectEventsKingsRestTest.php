<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\BFA;
use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('KingsRest')]
class APICombatLogControllerCorrectEventsKingsRestTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::KINGS_REST->value;
    }

    #[Test]
    public function create_givenKingsRestSeason2Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('BFA/midnight_s2_kings_rest');
    }
}
