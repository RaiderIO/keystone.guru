<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAiSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
final class DungeonRouteAiSourceStaysTest extends PublicTestCase
{
    #[Test]
    public function save_givenAiCreatedRouteSavedWithoutAiSource_keepsTheAiSource(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create([
            'ai_source'      => DungeonRouteAiSource::Claude,
            'ai_client_name' => 'Claude Code',
        ]);

        try {
            // Act
            $dungeonRoute->update(['ai_source' => null, 'ai_client_name' => null, 'title' => 'Renamed']);

            // Assert
            $fresh = $dungeonRoute->fresh();
            $this->assertSame('Renamed', $fresh->title);
            $this->assertSame(DungeonRouteAiSource::Claude, $fresh->ai_source);
            $this->assertSame('Claude Code', $fresh->ai_client_name);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function save_givenRouteNotCreatedByAi_canBeMarkedLater(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create(['ai_source' => null]);

        try {
            // Act
            $dungeonRoute->update(['ai_source' => DungeonRouteAiSource::Codex]);

            // Assert
            $this->assertSame(DungeonRouteAiSource::Codex, $dungeonRoute->fresh()->ai_source);
        } finally {
            $dungeonRoute->delete();
        }
    }
}
