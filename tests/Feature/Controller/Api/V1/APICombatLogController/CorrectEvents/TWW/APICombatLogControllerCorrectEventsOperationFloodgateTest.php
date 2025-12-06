<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\TWW;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('OperationFloodgate')]
class APICombatLogControllerCorrectEventsOperationFloodgateTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_OPERATION_FLOODGATE;
    }

    #[Test]
    public function create_givenTwwS2OperationFloodgate8Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s2_operation_floodgate_8');
    }

    #[Test]
    public function create_givenTwwS3PtrOperationFloodgate7Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('TWW/tww_s3_ptr_operation_floodgate_7');
    }
}
