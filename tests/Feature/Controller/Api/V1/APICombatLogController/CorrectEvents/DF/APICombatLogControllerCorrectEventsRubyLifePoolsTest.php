<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\DF;
use App\Models\DungeonKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents\APICombatLogControllerCorrectEventsTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
#[Group('RubyLifePools')]
class APICombatLogControllerCorrectEventsRubyLifePoolsTest extends APICombatLogControllerCorrectEventsTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::RUBY_LIFE_POOLS->value;
    }

    #[Test]
    public function create_givenRubyLifePoolsSeason2Json_shouldReturnCorrectedJsonData(): void
    {
        $this->executeTest('DF/midnight_s2_ruby_life_pools');
    }
}
