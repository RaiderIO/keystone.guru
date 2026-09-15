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
#[Group('MagistersTerraceMidnight')]
class APICombatLogControllerCorrectEventsMagistersTerraceMidnightTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::MAGISTERS_TERRACE_MIDNIGHT->value;
    }

    #[Test]
    public function create_givenMagistersTerraceMidnightPreseasonJson_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('Midnight/midnight_s1_magisters_terrace_preseason');
    }
}
