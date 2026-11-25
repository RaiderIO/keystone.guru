<?php

use App\Features\Mcp as McpFeature;
use App\Http\Controllers\Mcp\McpOAuthDiscoveryController;
use App\Http\Controllers\Mcp\McpOAuthRegisterController;
use App\Mcp\Servers\KeystoneGuruServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP
|--------------------------------------------------------------------------
|
| Loaded by laravel/mcp outside every middleware group: no session, no CSRF. The MCP endpoint authenticates with an
| OAuth access token only (the Passport `api` guard) - never Basic authentication or a session.
|
*/

Route::get('.well-known/oauth-protected-resource', [McpOAuthDiscoveryController::class, 'protectedResource'])
    ->name('mcp.oauth.protected-resource');
// AddWwwAuthenticateHeader points a 401 from the MCP endpoint at this route by its name
Route::get('.well-known/oauth-protected-resource/{path}', [McpOAuthDiscoveryController::class, 'protectedResource'])
    ->where('path', '.*')
    ->name('mcp.oauth.protected-resource.nested');
Route::get('.well-known/oauth-authorization-server', [McpOAuthDiscoveryController::class, 'authorizationServer'])
    ->name('mcp.oauth.authorization-server');
Route::get('.well-known/oauth-authorization-server/{path}', [McpOAuthDiscoveryController::class, 'authorizationServer'])
    ->where('path', '.*')
    ->name('mcp.oauth.authorization-server.nested');

// Dynamic client registration (RFC 7591): what lets claude.ai and other MCP clients connect without a client id
// handed out by hand. Registered clients are public (PKCE) and can only redirect to config('mcp.redirect_domains').
Route::post('oauth/register', McpOAuthRegisterController::class)
    ->middleware('throttle:mcp-register')
    ->name('mcp.oauth.register');

Mcp::web('mcp', KeystoneGuruServer::class)
    ->middleware([
        'auth:api',
        sprintf('feature_active:%s', McpFeature::class),
        'throttle:mcp',
    ])
    ->name('mcp');
