<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\Midnight;
use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('TheBlindingVale')]
class APICombatLogControllerCorrectEventsTheBlindingValeTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::THE_BLINDING_VALE->value;
    }

    #[Test]
    public function create_givenTheBlindingValeSeason2Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Midnight/midnight_s2_the_blinding_vale');
    }
}
