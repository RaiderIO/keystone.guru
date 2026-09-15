<?php

namespace App\Service\Request;

use Illuminate\Http\Request;

interface ApiRequestServiceInterface
{
    /**
     * Whether $request targets the API surface - the set of paths that only ever produce a
     * machine-readable (JSON) response and never render a Blade view.
     */
    public function isApiRequest(Request $request): bool;

    /**
     * Path variant of {@see ApiRequestServiceInterface::isApiRequest()}.
     *
     * @param string $pathInfo The raw, slash-preserving request path as returned by
     *                         {@see Request::getPathInfo()}. Do NOT pass Request::path() or
     *                         Request::decodedPath() - both trim the trailing slash, which makes a
     *                         bare "/api/" request read as "api" and escape detection (#3903).
     */
    public function isApiRequestPath(string $pathInfo): bool;
}
