<?php

namespace App\Logging;

/**
 * The single list of what counts as sensitive in an error report or a log record, shared by the Sentry `before_send`
 * scrubber, the browser snippet's scrub fields and the exception handler's log context. Keys are matched by pattern
 * on a normalised name rather than by exact name, so a new form field such as `new_password-confirm` or a header such as
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
     * Keys whose value is an e-mail address. These are masked rather than filtered: the first characters and the
     * domain stay readable, so a report can still be tied to the account that caused it.
     */
    private const string EMAIL_KEY_PATTERN = '/(^|_)e_?mail(_address)?$/';

    /**
     * Request parameters that are only sensitive as request input (query string, form body), not as an arbitrary log
     * context key: the OAuth authorisation `code` and `state`. A `code` in log context is usually an HTTP status.
     */
    private const array SENSITIVE_REQUEST_PARAMETERS = [
        'code',
        'state',
    ];

    /**
     * The browser scrubber matches exact key names (case-insensitively) rather than the patterns above, so it gets the
     * concrete spellings of the keys those patterns cover. Every entry must be one this class already treats as sensitive.
     */
    private const array BROWSER_SCRUB_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'passwd',
        'passphrase',
        'secret',
        'client_secret',
        'token',
        '_token',
        'access_token',
        'accessToken',
        'refresh_token',
        'id_token',
        'api_token',
        'remember_token',
        'csrf_token',
        'xsrf_token',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'XSRF-TOKEN',
        'api_key',
        'apiKey',
        'access_key',
        'private_key',
        'auth',
        'authorization',
        'Authorization',
        'cookie',
        'Cookie',
        'session',
        'session_id',
        'signature',
        'credential',
        'credentials',
        'remember',
        'code',
        'state',
        'email',
        'e_mail',
        'email_address',
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

    public static function isEmailKey(string $key): bool
    {
        return preg_match(self::EMAIL_KEY_PATTERN, self::normalizeKey($key)) === 1;
    }

    /**
     * Keeps the first two characters of the local part and the whole domain: `wouter@example.com` becomes
     * `wo***@example.com`. A value without an `@` keeps its first two characters only.
     */
    public static function maskEmail(string $email): string
    {
        $atPosition = strrpos($email, '@');
        $localPart  = $atPosition === false ? $email : substr($email, 0, $atPosition);
        $domain     = $atPosition === false ? '' : substr($email, $atPosition);

        return sprintf('%s***%s', mb_substr($localPart, 0, 2), $domain);
    }

    public static function isSensitiveRequestParameter(string $name): bool
    {
        return in_array(self::normalizeKey($name), self::SENSITIVE_REQUEST_PARAMETERS, true) || self::isSensitiveKey($name);
    }

    /**
     * @return array<int, string>
     */
    public static function browserScrubFields(): array
    {
        return self::BROWSER_SCRUB_FIELDS;
    }

    /**
     * Redacts the value of every sensitive query parameter found in a URL, a bare query string or free text quoting
     * either.
     */
    public static function scrubString(string $value): string
    {
        return preg_replace_callback(
            '/(^|[?&])([^=&?\s\'"#]+)=([^&\s\'"#]*)/',
            static fn(array $match): string => match (true) {
                self::isSensitiveQueryParameterName($match[2]) => sprintf('%s%s=%s', $match[1], $match[2], self::FILTERED),
                self::isEmailKey(urldecode($match[2]))         => sprintf('%s%s=%s', $match[1], $match[2], self::maskEmail(urldecode($match[3]))),
                default                                        => $match[0],
            },
            $value,
        ) ?? $value;
    }

    /**
     * Redacts the value of every sensitive key, recursively, masks e-mail values, and redacts every sensitive query
     * parameter in string values.
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
            } elseif (is_string($key) && is_string($value) && self::isEmailKey($key)) {
                $result[$key] = self::maskEmail($value);
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
