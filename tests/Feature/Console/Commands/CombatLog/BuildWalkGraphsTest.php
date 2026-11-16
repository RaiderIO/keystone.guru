<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Logic\WalkGraph\WalkGrid;
use App\Models\CombatLog\CombatLogWalkGraphContribution;
use App\Models\WalkGraph\WalkGraph;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('WalkGraph')]
final class BuildWalkGraphsTest extends PublicTestCase
{
    private const int UI_MAP_ID = 9_990_701;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanUpTestData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->cleanUpTestData();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenContributionsForAUiMap_buildsItsWalkGraph(): void
    {
        // Arrange
        CombatLogWalkGraphContribution::factory()
            ->withEdgeKeys([WalkGrid::edgeKey(0, 0, 1, 0), WalkGrid::edgeKey(1, 0, 2, 0)])
            ->create(['ui_map_id' => self::UI_MAP_ID]);

        // Act
        $exitCode = $this->artisan('combatlog:buildwalkgraphs')->run();

        // Assert
        $this->assertSame(0, $exitCode);
        $walkGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->first();
        $this->assertNotNull($walkGraph);
        $this->assertSame(1, $walkGraph->run_count);
        $this->assertSame(2, $walkGraph->edge_count);
    }

    private function cleanUpTestData(): void
    {
        CombatLogWalkGraphContribution::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
        WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
    }
}
