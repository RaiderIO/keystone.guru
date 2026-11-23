<?php

namespace Tests\Feature\Mcp;

use App\Models\Laratrust\Role;
use App\Service\User\ApiTokenScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Teapot\StatusCode;

/**
 * The /mcp endpoint over HTTP: OAuth bearer tokens only, behind the Mcp feature, plus the OAuth discovery documents
 * and dynamic client registration an MCP client needs to connect.
 */
#[Group('Mcp')]
#[Group('McpEndpoint')]
final class McpEndpointTest extends McpTestCase
{
    #[Test]
    public function mcp_givenNoToken_returnsUnauthorizedWithResourceMetadataPointer(): void
    {
        // Arrange
        $body = $this->toolsListBody();

        // Act
        $response = $this->postMcp($body, []);

        // Assert
        $response->assertStatus(StatusCode::UNAUTHORIZED);
        $this->assertStringContainsString(
            route('mcp.oauth.protected-resource.nested', ['path' => 'mcp']),
            (string)$response->headers->get('WWW-Authenticate'),
        );
    }

    #[Test]
    public function mcp_givenValidTokenAndActiveFeature_listsTools(): void
    {
        // Arrange
        $user  = $this->createUser();
        $token = $user->createToken('mcp', [ApiTokenScope::RoutesRead->value])->accessToken;

        // Act
        $response = $this->postMcp($this->toolsListBody(), ['Authorization' => sprintf('Bearer %s', $token)]);

        // Assert
        $response->assertOk();
        $toolNames = array_column($response->json('result.tools') ?? [], 'name');
        $this->assertContains('get_dungeon_packs', $toolNames);
        $this->assertContains('create_route', $toolNames);
    }

    #[Test]
    public function mcp_givenValidTokenAndInactiveFeature_returnsNotFound(): void
    {
        // Arrange
        $user  = $this->createUser(Role::ROLE_USER, false);
        $token = $user->createToken('mcp', [ApiTokenScope::RoutesRead->value])->accessToken;

        // Act
        $response = $this->postMcp($this->toolsListBody(), ['Authorization' => sprintf('Bearer %s', $token)]);

        // Assert
        $response->assertStatus(StatusCode::NOT_FOUND);
    }

    #[Test]
    public function protectedResource_givenMcpPath_advertisesTheRouteScopes(): void
    {
        // Arrange
        $url = route('mcp.oauth.protected-resource.nested', ['path' => 'mcp']);

        // Act
        $response = $this->getJson($url);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('resource', url('/mcp'));
        $response->assertJsonPath('scopes_supported', [ApiTokenScope::RoutesRead->value, ApiTokenScope::RoutesWrite->value]);
    }

    #[Test]
    public function authorizationServer_givenRequest_advertisesPassportEndpointsAndRegistration(): void
    {
        // Arrange
        $url = route('mcp.oauth.authorization-server');

        // Act
        $response = $this->getJson($url);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'));
        $response->assertJsonPath('token_endpoint', route('passport.token'));
        $response->assertJsonPath('registration_endpoint', route('mcp.oauth.register'));
        $response->assertJsonPath('scopes_supported', [ApiTokenScope::RoutesRead->value, ApiTokenScope::RoutesWrite->value]);
    }

    #[Test]
    public function register_givenAllowedRedirectUri_createsPublicClient(): void
    {
        // Arrange
        $redirectUri = 'https://claude.ai/api/mcp/auth_callback';

        try {
            // Act
            $response = $this->postJson(route('mcp.oauth.register'), [
                'client_name'   => 'Claude',
                'redirect_uris' => [$redirectUri],
            ]);

            // Assert
            $response->assertStatus(StatusCode::CREATED);
            /** @var Client $client */
            $client = Client::query()->whereKey($response->json('client_id'))->firstOrFail();
            $this->assertSame([$redirectUri], $client->getAttribute('redirect_uris'));
            $this->assertFalse($client->confidential());
        } finally {
            Client::query()->where('name', 'Claude')->where('redirect_uris', 'like', '%claude.ai/api/mcp%')->delete();
        }
    }

    #[Test]
    public function register_givenRedirectUriOutsideAllowedDomains_returnsInvalidRedirectUri(): void
    {
        // Arrange
        $clientCount = Client::query()->count();

        // Act
        $response = $this->postJson(route('mcp.oauth.register'), [
            'client_name'   => 'Phisher',
            'redirect_uris' => ['https://claude.ai.evil.example/callback'],
        ]);

        // Assert
        $response->assertStatus(StatusCode::BAD_REQUEST);
        $response->assertJsonPath('error', 'invalid_redirect_uri');
        $this->assertSame($clientCount, Client::query()->count());
    }

    #[Test]
    public function register_givenRedirectUriOutsideAllowedDomains_logsTheRefusedClient(): void
    {
        // Arrange
        $log = Log::spy();

        // Act
        $response = $this->postJson(route('mcp.oauth.register'), [
            'client_name'   => 'Some new AI',
            'redirect_uris' => ['https://new-ai.example/callback'],
        ]);

        // Assert
        $response->assertStatus(StatusCode::BAD_REQUEST);
        $log->shouldHaveReceived('warning')->once()->withArgs(static fn(string $message, array $context) => $context === [
            'client_name'   => 'Some new AI',
            'redirect_uris' => ['https://new-ai.example/callback'],
        ]);
    }

    #[Test]
    public function register_givenChatGptRedirectUri_createsPublicClient(): void
    {
        // Arrange
        $redirectUri = 'https://chatgpt.com/connector_platform_oauth_redirect';

        try {
            // Act
            $response = $this->postJson(route('mcp.oauth.register'), [
                'client_name'   => 'ChatGPT',
                'redirect_uris' => [$redirectUri],
            ]);

            // Assert
            $response->assertStatus(StatusCode::CREATED);
            /** @var Client $client */
            $client = Client::query()->whereKey($response->json('client_id'))->firstOrFail();
            $this->assertSame([$redirectUri], $client->getAttribute('redirect_uris'));
        } finally {
            Client::query()->where('name', 'ChatGPT')->where('redirect_uris', 'like', '%chatgpt.com%')->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function toolsListBody(): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => []];
    }

    /**
     * @param  array<string, mixed>          $body
     * @param  array<string, string>         $headers
     * @return TestResponse<SymfonyResponse>
     */
    private function postMcp(array $body, array $headers): TestResponse
    {
        return $this->postJson(route('mcp'), $body, array_merge([
            'Accept' => 'application/json, text/event-stream',
        ], $headers));
    }
}
