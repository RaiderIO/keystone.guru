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
#[Group('VoidscarArena')]
class APICombatLogControllerCorrectEventsVoidscarArenaTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::VOIDSCAR_ARENA->value;
    }

    #[Test]
    public function create_givenVoidscarArenaSeason2Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Midnight/midnight_s2_voidscar_arena');
    }
}
