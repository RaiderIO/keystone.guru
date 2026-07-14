<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('PullGraph')]
final class PullGraphTest extends PublicTestCase
{
    /**
     * @param  array<int, array{enemy_forces: int, has_boss: bool}> $pulls
     * @return Collection<int, stdClass>
     */
    private function pullForces(array $pulls): Collection
    {
        return collect($pulls)->map(static function (array $pull): stdClass {
            $row               = new stdClass();
            $row->enemy_forces = $pull['enemy_forces'];
            $row->has_boss     = $pull['has_boss'];

            return $row;
        });
    }

    #[Test]
    public function render_givenPullsWithForces_returnsOneBarPerPull(): void
    {
        // Arrange
        $pullForces = $this->pullForces([
            ['enemy_forces' => 10, 'has_boss' => false],
            ['enemy_forces' => 20, 'has_boss' => false],
            ['enemy_forces' => 5, 'has_boss' => false],
        ]);

        // Act
        $html = view('common.dungeonroute.pullgraph', [
            'pullForces'  => $pullForces,
            'chartHeight' => 22,
            'fill'        => 'rgba(255, 255, 255, 0.45)',
            'bossFill'    => 'rgba(240, 180, 60, 0.9)',
            'graphClass'  => 'leaderboard_pull_graph',
            'tooltipKey'  => 'view_common.dungeonroute.cardrow.pulls',
        ])->render();

        // Assert
        $this->assertStringContainsString('leaderboard_pull_graph', $html);
        $this->assertSame(3, substr_count($html, '<rect'));
    }

    #[Test]
    public function render_givenForcelessNonBossPulls_omitsThoseBars(): void
    {
        // Arrange - two real pulls plus two empty ones that should not render
        $pullForces = $this->pullForces([
            ['enemy_forces' => 10, 'has_boss' => false],
            ['enemy_forces' => 0, 'has_boss' => false],
            ['enemy_forces' => 15, 'has_boss' => false],
            ['enemy_forces' => 0, 'has_boss' => false],
        ]);

        // Act
        $html = view('common.dungeonroute.pullgraph', [
            'pullForces'  => $pullForces,
            'chartHeight' => 22,
            'fill'        => 'rgba(255, 255, 255, 0.45)',
            'bossFill'    => 'rgba(240, 180, 60, 0.9)',
            'graphClass'  => 'leaderboard_pull_graph',
            'tooltipKey'  => 'view_common.dungeonroute.cardrow.pulls',
        ])->render();

        // Assert - only the two force-bearing pulls render, but the tooltip reflects the real total of 4
        $this->assertSame(2, substr_count($html, '<rect'));
        $this->assertStringContainsString('4 pulls', $html);
    }

    #[Test]
    public function render_givenBossPull_returnsFullHeightAccentBar(): void
    {
        // Arrange - a forceless boss pull must still render, at full height in the accent color
        $pullForces = $this->pullForces([
            ['enemy_forces' => 10, 'has_boss' => false],
            ['enemy_forces' => 0, 'has_boss' => true],
        ]);

        // Act
        $html = view('common.dungeonroute.pullgraph', [
            'pullForces'  => $pullForces,
            'chartHeight' => 22,
            'fill'        => 'rgba(255, 255, 255, 0.45)',
            'bossFill'    => 'rgba(240, 180, 60, 0.9)',
            'graphClass'  => 'leaderboard_pull_graph',
            'tooltipKey'  => 'view_common.dungeonroute.cardrow.pulls',
        ])->render();

        // Assert
        $this->assertSame(2, substr_count($html, '<rect'));
        $this->assertStringContainsString('rgba(240, 180, 60, 0.9)', $html);
        $this->assertStringContainsString('height="22"', $html);
    }

    #[Test]
    public function render_givenNoPulls_rendersNothing(): void
    {
        // Act
        $html = view('common.dungeonroute.pullgraph', [
            'pullForces'  => collect(),
            'chartHeight' => 22,
            'fill'        => 'rgba(255, 255, 255, 0.45)',
            'bossFill'    => 'rgba(240, 180, 60, 0.9)',
            'graphClass'  => 'leaderboard_pull_graph',
            'tooltipKey'  => 'view_common.dungeonroute.cardrow.pulls',
        ])->render();

        // Assert
        $this->assertStringNotContainsString('leaderboard_pull_graph', trim($html));
    }
}
