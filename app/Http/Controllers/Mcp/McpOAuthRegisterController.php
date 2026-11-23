<?php

namespace App\Http\Controllers\Mcp;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;

/**
 * Dynamic client registration (RFC 7591) as laravel/mcp implements it, logging every registration it refuses for a
 * redirect URI outside config('mcp.redirect_domains'): that log is how a new AI client that wants to connect shows up.
 */
class McpOAuthRegisterController extends OAuthRegisterController
{
    /**
     * @throws BindingResolutionException
     */
    #[\Override]
    public function __invoke(Request $request): JsonResponse
    {
        $response = parent::__invoke($request);

        if ($response->getStatusCode() === 400 && $response->getData(true)['error'] === 'invalid_redirect_uri') {
            Log::warning('MCP client registration refused: redirect URI outside the allowed domains', [
                'client_name'   => $request->input('client_name') ?? $request->input('name'),
                'redirect_uris' => $request->input('redirect_uris'),
            ]);
        }

        return $response;
    }
}
