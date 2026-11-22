<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\GameData\ListDungeonsTool;
use App\Mcp\Tools\Route\CreateRouteTool;
use App\Models\Laratrust\Role;
use App\Service\User\ApiTokenScope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every tool checks the caller before it does anything: the Mcp feature must be active for them and their access
 * token must carry the tool's scope.
 */
#[Group('Mcp')]
#[Group('McpToolAuthorization')]
final class McpToolAuthorizationTest extends McpTestCase
{
    #[Test]
    public function handle_givenUserWithFeatureAndReadScope_runsReadTool(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class);

        // Assert
        $response->assertHasNoErrors();
        $this->assertNotEmpty($this->structuredContent($response)['dungeons']);
    }

    #[Test]
    public function handle_givenFeatureInactiveForUser_returnsFeatureInactiveError(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(Role::ROLE_USER, false), [ApiTokenScope::RoutesWrite]);

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class);

        // Assert
        $response->assertHasErrors([__('mcp.error.feature_inactive')]);
    }

    #[Test]
    public function handle_givenTokenWithoutRouteScopes_returnsMissingScopeError(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::CombatLogRead]);

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class);

        // Assert
        $response->assertHasErrors([__('mcp.error.missing_scope', ['scope' => ApiTokenScope::RoutesRead->value])]);
    }

    #[Test]
    public function handle_givenReadOnlyTokenOnWriteTool_returnsMissingScopeError(): void
    {
        // Arrange
        [$dungeon] = $this->findPackDungeon();
        $user      = $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);

        // Act
        $response = KeystoneGuruServer::tool(CreateRouteTool::class, ['dungeon' => $dungeon->key, 'title' => 'Read only']);

        // Assert
        $response->assertHasErrors([__('mcp.error.missing_scope', ['scope' => ApiTokenScope::RoutesWrite->value])]);
        $this->assertSame(0, $user->dungeonRoutes()->count());
    }

    #[Test]
    public function handle_givenUserWithoutToken_returnsMissingScopeError(): void
    {
        // Arrange - a session-style login carries no access token, so it holds no scope at all
        $this->be($this->createUser(), 'api');
        $this->app['auth']->shouldUse('api');

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class);

        // Assert
        $response->assertHasErrors([__('mcp.error.missing_scope', ['scope' => ApiTokenScope::RoutesRead->value])]);
    }
}
