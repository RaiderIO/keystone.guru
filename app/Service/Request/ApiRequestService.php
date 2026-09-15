<?php

namespace App\Service\Request;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiRequestService implements ApiRequestServiceInterface
{
    /**
     * Path prefixes that make up the API surface. The exact spelling is load bearing: '/api/' and
     * '/ajax/' carry a trailing slash so that a page like '/api_keys.json' or '/apis/config.js' is
     * NOT considered an API request, while '/benchmark' deliberately has none since it is a single
     * route rather than a prefixed group.
     *
     * @var array<int, string>
     */
    private const array API_PATH_PREFIXES = [
        '/ajax/',
        '/api/',
        '/benchmark',
    ];

    public function isApiRequest(Request $request): bool
    {
        return $this->isApiRequestPath($request->getPathInfo());
    }

    public function isApiRequestPath(string $pathInfo): bool
    {
        return Str::startsWith($pathInfo, self::API_PATH_PREFIXES);
    }
}
