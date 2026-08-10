<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\TWW;

use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('DarkflameCleft')]
class APICombatLogControllerCorrectEventsDarkflameCleftTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::DARKFLAME_CLEFT->value;
    }

    #[Test]
    public function create_givenDarkflameCleft5Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s2_darkflame_cleft_5');
    }

    #[Test]
    public function create_givenDarkflameCleft14ShadowRealmJson_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s2_darkflame_cleft_14_shadow_realm');
    }
}
