<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;

trait ResolvesRedirectTarget
{
    /**
     * Resolves the `redirect` request parameter to a path on this site, falling back to the given
     * default for anything that would leave it. The parameter is public and receives a lot of junk
     * traffic, so anything unusable must fall back rather than error.
     */
    protected function resolveRedirectTarget(Request $request, string $default): string
    {
        $redirect = $request->get('redirect');

        if (!is_string($redirect)) {
            return $default;
        }

        // Browsers drop control characters and surrounding whitespace before resolving a URL, so
        // they must not be allowed to hide the scheme or authority from this check either
        $redirect = preg_replace('/[\x00-\x20\x7F]+/', '', $redirect) ?? '';
        // Browsers normalize a leading backslash to a forward slash, making \\host an absolute URL
        $redirect = str_replace('\\', '/', $redirect);

        // Only a path on this site: no scheme, and no authority component
        if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return $default;
        }

        return $redirect;
    }
}
