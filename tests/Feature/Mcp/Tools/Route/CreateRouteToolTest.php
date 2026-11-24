<?php

namespace Tests\Feature\Mcp\Tools\Route;

use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\Route\CreateRouteTool;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Mcp\McpTestCase;

#[Group('Mcp')]
#[Group('McpCreateRoute')]
final class CreateRouteToolTest extends McpTestCase
{
    #[Test]
    public function handle_givenValidDungeon_createsPrivateRouteOwnedByCaller(): void
    {
        // Arrange
        [$dungeon] = $this->findPackDungeon();
        $user      = $this->actingAsMcpUser($this->createUser());

        // Act
        $response = KeystoneGuruServer::tool(CreateRouteTool::class, [
            'dungeon'   => $dungeon->key,
            'title'     => 'My AI route',
            'level_min' => 10,
            'level_max' => 12,
        ]);

        // Assert
        $response->assertHasNoErrors();
        $content = $this->structuredContent($response);

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::query()->where('public_key', $content['public_key'])->firstOrFail();
        $this->assertSame($user->id, $dungeonRoute->author_id);
        $this->assertSame($dungeon->id, $dungeonRoute->dungeon_id);
        $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $dungeonRoute->published_state_id);
        $this->assertNull($dungeonRoute->team_id);
        $this->assertNull($dungeonRoute->expires_at);
        $this->assertSame('My AI route', $dungeonRoute->title);
        $this->assertSame(10, $dungeonRoute->level_min);
        $this->assertSame(12, $dungeonRoute->level_max);
        $this->assertSame('unpublished', $content['published_state']);
        $this->assertSame([], $content['pulls']);
    }

    #[Test]
    public function handle_givenUnknownDungeon_returnsDungeonNotFound(): void
    {
        // Arrange
        $user = $this->actingAsMcpUser($this->createUser());

        // Act
        $response = KeystoneGuruServer::tool(CreateRouteTool::class, ['dungeon' => 'not-a-dungeon', 'title' => 'Nope']);

        // Assert
        $response->assertHasErrors([__('mcp.error.dungeon_not_found', ['dungeon' => 'not-a-dungeon'])]);
        $this->assertSame(0, $user->dungeonRoutes()->count());
    }

    #[Test]
    public function handle_givenLevelMaxBelowLevelMin_returnsValidationError(): void
    {
        // Arrange
        [$dungeon] = $this->findPackDungeon();
        $user      = $this->actingAsMcpUser($this->createUser());

        // Act
        $response = KeystoneGuruServer::tool(CreateRouteTool::class, [
            'dungeon'   => $dungeon->key,
            'title'     => 'Backwards',
            'level_min' => 12,
            'level_max' => 10,
        ]);

        // Assert
        $response->assertHasErrors(['level max']);
        $this->assertSame(0, $user->dungeonRoutes()->count());
    }

    #[Test]
    public function handle_givenRateLimitReached_refusesAnotherRoute(): void
    {
        // Arrange
        config(['keystoneguru.api.mcp.create_route_per_hour' => 1]);
        [$dungeon] = $this->findPackDungeon();
        $user      = $this->actingAsMcpUser($this->createUser());
        KeystoneGuruServer::tool(CreateRouteTool::class, ['dungeon' => $dungeon->key, 'title' => 'First'])->assertHasNoErrors();

        // Act
        $response = KeystoneGuruServer::tool(CreateRouteTool::class, ['dungeon' => $dungeon->key, 'title' => 'Second']);

        // Assert
        $response->assertHasErrors(['Too many requests']);
        $this->assertSame(1, $user->dungeonRoutes()->count());
    }
}
