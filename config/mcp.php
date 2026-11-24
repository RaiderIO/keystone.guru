<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redirect domains
    |--------------------------------------------------------------------------
    |
    | The redirect URIs an OAuth client may register through dynamic client registration (POST /oauth/register).
    | Anyone can register a client, so this is what stops a client from sending authorization codes anywhere but to
    | an AI client the user actually runs. http://localhost and http://127.0.0.1 cover desktop and CLI clients
    | (Claude Code, Claude Desktop), which listen on a random local port.
    |
    */

    'redirect_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string)env('MCP_REDIRECT_DOMAINS', 'https://claude.ai,https://claude.com,http://localhost,http://127.0.0.1')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Custom schemes
    |--------------------------------------------------------------------------
    |
    | Non-http(s) redirect URI schemes a client may register, e.g. "cursor" or "vscode".
    |
    */

    'custom_schemes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string)env('MCP_CUSTOM_SCHEMES', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Authorization server
    |--------------------------------------------------------------------------
    |
    | The issuer advertised in the OAuth discovery documents; null for this application itself.
    |
    */

    'authorization_server' => null,
];
