<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Service\User\ApiTokenScope;
use Illuminate\Http\JsonResponse;

/**
 * The OAuth discovery documents an MCP client reads before it registers itself and sends the user through
 * /oauth/authorize (RFC 9728 protected resource metadata, RFC 8414 authorization server metadata).
 *
 * laravel/mcp's own Mcp::oauthRoutes() advertises only its catch-all `mcp:use` scope; these advertise the API's
 * route scopes instead, so the token a client obtains carries exactly what the MCP tools check.
 */
class McpOAuthDiscoveryController extends Controller
{
    private const array SCOPES_SUPPORTED = [
        ApiTokenScope::RoutesRead->value,
        ApiTokenScope::RoutesWrite->value,
    ];

    public function protectedResource(?string $path = null): JsonResponse
    {
        return response()->json([
            'resource'              => url(sprintf('/%s', $path ?? '')),
            'authorization_servers' => [$this->issuer()],
            'scopes_supported'      => self::SCOPES_SUPPORTED,
        ]);
    }

    public function authorizationServer(): JsonResponse
    {
        return response()->json([
            'issuer'                                => $this->issuer(),
            'authorization_endpoint'                => route('passport.authorizations.authorize'),
            'token_endpoint'                        => route('passport.token'),
            'registration_endpoint'                 => route('mcp.oauth.register'),
            'response_types_supported'              => ['code'],
            'code_challenge_methods_supported'      => ['S256'],
            'scopes_supported'                      => self::SCOPES_SUPPORTED,
            'grant_types_supported'                 => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_basic', 'client_secret_post'],
        ]);
    }

    private function issuer(): string
    {
        return config('mcp.authorization_server') ?? url('/');
    }
}
