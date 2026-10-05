<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('PullGraph')]
final class PullGraphTest extends PublicTestCase
{
    /**
     * @param  array<int, array{enemy_forces: int, has_boss: bool}> $pulls
     * @return Collection<int, KillZoneEnemyForces>
     */
    private function pullForces(array $pulls): Collection
    {
        return collect($pulls)->map(static fn(array $pull): KillZoneEnemyForces => new KillZoneEnemyForces(
            $pull['enemy_forces'],
            $pull['has_boss'],
        ));
    }

    /**
     * @return array<int, string> The height of every bar, in render order.
     */
    private function barHeights(string $html): array
    {
        preg_match_all('/<rect [^>]*height="(\d+)"/', $html, $matches);

        return $matches[1];
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

        // Assert - trash bars scale against the largest trash pull: 10/20 and 5/20 of the chart height, rounded
        $this->assertStringContainsString('leaderboard_pull_graph', $html);
        $this->assertSame(3, substr_count($html, '<rect'));
        $this->assertSame(['11', '22', '6'], $this->barHeights($html));
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

        // Assert - the svg itself is 22 high as well, so the boss bar is matched as a whole
        $this->assertSame(2, substr_count($html, '<rect'));
        $this->assertStringContainsString('rgba(240, 180, 60, 0.9)', $html);
        $this->assertStringContainsString('height="22"', $html);
        $this->assertMatchesRegularExpression(
            '/<rect x="4" y="0" width="3" height="22"\s+fill="rgba\(240, 180, 60, 0\.9\)"><\/rect>/',
            $html,
        );
    }

    #[Test]
    public function render_givenMoreThanThirtyPulls_capsTheBarsAndKeepsTheRealPullCount(): void
    {
        // Arrange
        $pullForces = $this->pullForces(array_fill(0, 35, ['enemy_forces' => 10, 'has_boss' => false]));

        // Act
        $html = view('common.dungeonroute.pullgraph', [
            'pullForces' => $pullForces,
            'graphClass' => 'leaderboard_pull_graph',
            'tooltipKey' => 'view_common.dungeonroute.cardrow.pulls',
        ])->render();

        // Assert
        $this->assertSame(30, substr_count($html, '<rect'));
        $this->assertStringContainsString('35 pulls', $html);
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

    #[Test]
    public function render_givenNoRenderTuningOptions_fallsBackToDefaults(): void
    {
        // Arrange - chartHeight/fill/bossFill omitted entirely
        $pullForces = $this->pullForces([
            ['enemy_forces' => 10, 'has_boss' => false],
            ['enemy_forces' => 0, 'has_boss' => true],
        ]);

        // Act
        $html = view('common.dungeonroute.pullgraph', [
            'pullForces' => $pullForces,
            'graphClass' => 'hero_pull_graph',
            'tooltipKey' => 'view_common.dungeonroute.cardhero.pulls',
        ])->render();

        // Assert
        $this->assertStringContainsString('height="26"', $html);
        $this->assertStringContainsString('rgba(255, 255, 255, 0.6)', $html);
        $this->assertStringContainsString('rgba(240, 180, 60, 0.95)', $html);
    }
}
