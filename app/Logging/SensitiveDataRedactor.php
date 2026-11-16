<?php

namespace App\Logging;

/**
 * The single list of what counts as sensitive in an error report or a log record, shared by the Sentry `before_send`
 * scrubber, the Rollbar scrubber and the exception handler's log context. Keys are matched by pattern on a normalised
 * name rather than by exact name, so a new form field such as `new_password-confirm` or a header such as
 * `CF-Connecting-IP` is covered without editing a list. The price is that an unrelated field that happens to share a
 * name fragment (a `session_count`, say) is redacted as well.
 */
final class SensitiveDataRedactor
{
    public const string FILTERED = '[Filtered]';

    /**
     * Patterns matched against a key after normalisation (camelCase split, lower-cased, every run of non-alphanumeric
     * characters folded into a single `_`).
     */
    private const array SENSITIVE_KEY_PATTERNS = [
        '/pass(word|wd|phrase)/',
        '/secret/',
        '/token$/',
        '/api_?key/',
        '/access_?key/',
        '/private_?key/',
        '/(^|_)auth($|_|orization)/',
        '/cookie/',
        '/session/',
        '/signature/',
        '/credential/',
        '/(^|_)remember/',
        '/(^|_)[cx]srf($|_)/',
        '/(^|_)(x_)?forwarded(_for)?$/',
        '/(^|_)(x_original_forwarded_for|x_real_ip|cf_connecting_ip(v6)?|true_client_ip|x_client_ip|x_cluster_client_ip|remote_addr)$/',
    ];

    /**
     * Request parameters that are only sensitive as request input (query string, form body), not as an arbitrary log
     * context key: the OAuth authorisation `code` and `state`. A `code` in log context is usually an HTTP status.
     */
    private const array SENSITIVE_REQUEST_PARAMETERS = [
        'code',
        'state',
    ];

    public static function isSensitiveKey(string $key): bool
    {
        $normalizedKey = self::normalizeKey($key);

        foreach (self::SENSITIVE_KEY_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalizedKey) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function isSensitiveRequestParameter(string $name): bool
    {
        return in_array(self::normalizeKey($name), self::SENSITIVE_REQUEST_PARAMETERS, true) || self::isSensitiveKey($name);
    }

    /**
     * Redacts the value of every sensitive query parameter found in a URL, a bare query string or free text quoting
     * either.
     */
    public static function scrubString(string $value): string
    {
        return preg_replace_callback(
            '/(^|[?&])([^=&?\s\'"#]+)=([^&\s\'"#]*)/',
            static fn(array $match): string => self::isSensitiveQueryParameterName($match[2])
                ? sprintf('%s%s=%s', $match[1], $match[2], self::FILTERED)
                : $match[0],
            $value,
        ) ?? $value;
    }

    /**
     * Redacts the value of every sensitive key, recursively, and every sensitive query parameter in string values.
     *
     * @param  array<array-key, mixed> $values
     * @param  bool                    $isRequestInput True when the keys are request parameters (query or form body)
     * @return array<array-key, mixed>
     */
    public static function scrubArray(array $values, bool $isRequestInput = false): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && self::isRedactable($value) && ($isRequestInput ? self::isSensitiveRequestParameter($key) : self::isSensitiveKey($key))) {
                $result[$key] = self::FILTERED;
            } else {
                $result[$key] = self::scrubValue($value, $isRequestInput);
            }
        }

        return $result;
    }

    /**
     * A boolean or null cannot carry a secret, and flags such as `hasSignature` stay readable for triage.
     */
    private static function isRedactable(mixed $value): bool
    {
        return $value !== null && !is_bool($value);
    }

    private static function scrubValue(mixed $value, bool $isRequestInput): mixed
    {
        if (is_string($value)) {
            return self::scrubString($value);
        }

        if (is_array($value)) {
            return self::scrubArray($value, $isRequestInput);
        }

        return $value;
    }

    private static function normalizeKey(string $key): string
    {
        $key = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $key) ?? $key;

        return preg_replace('/[^a-z0-9]+/', '_', strtolower($key)) ?? $key;
    }

    /**
     * Decodes a raw query parameter name the way PHP's query parser does before the application reads it and checks
     * the root and every bracketed component, so an encoded (`%73ecret`), array (`secret[]`), nested
     * (`account[password]`, `account%5Bpassword%5D`) or dotted (`access.token`) spelling still matches.
     */
    private static function isSensitiveQueryParameterName(string $rawName): bool
    {
        $components = preg_split('/[\[\]]+/', urldecode($rawName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($components as $component) {
            if (self::isSensitiveRequestParameter($component)) {
                return true;
            }
        }

        return false;
    }
}
