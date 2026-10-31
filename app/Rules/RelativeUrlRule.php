<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a url points somewhere on this site.
 *
 * A leading slash is not enough on its own: a browser reads `//evil.com` as a protocol-relative
 * URL and `/\evil.com` the same way, and Laravel's redirect helper passes both through untouched -
 * so either would send a reader off-site.
 */
class RelativeUrlRule implements ValidationRule
{
    public static function isRelativeUrl(?string $url): bool
    {
        if ($url === null || !str_starts_with($url, '/')) {
            return false;
        }

        // The second character decides it: another slash or a backslash makes this an authority,
        // not a path
        return !str_starts_with($url, '//') && !str_starts_with($url, '/\\');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!self::isRelativeUrl(is_string($value) ? $value : null)) {
            $fail(__('rules.relative_url_rule.not_relative'));
        }
    }
}
