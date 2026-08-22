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
#[Group('DenOfNalorakk')]
class APICombatLogControllerCorrectEventsDenOfNalorakkTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::DEN_OF_NALORAKK->value;
    }

    #[Test]
    public function create_givenDenOfNalorakkSeason2Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Midnight/midnight_s2_den_of_nalorakk');
    }
}
