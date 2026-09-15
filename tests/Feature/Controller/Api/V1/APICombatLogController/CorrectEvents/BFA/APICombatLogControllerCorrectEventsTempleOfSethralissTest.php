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
#[Group('TempleOfSethraliss')]
class APICombatLogControllerCorrectEventsTempleOfSethralissTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::TEMPLE_OF_SETHRALISS->value;
    }

    #[Test]
    public function create_givenTempleOfSethralissSeason2Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('BFA/midnight_s2_temple_of_sethraliss');
    }
}
