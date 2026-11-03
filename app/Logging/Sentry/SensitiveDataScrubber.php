<?php

namespace App\Logging\Sentry;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;

/**
 * Sentry `before_send` callback that redacts secrets before an event leaves the application. Secrets reach events
 * through query strings (the full request URL in the shared log context, a puppeteer command line quoted in a
 * `ProcessTimedOutException` message) and through same-named array keys in log context. Sentry's server-side
 * scrubber matches neither a query parameter embedded in free text nor keys such as `code`, so this runs client-side.
 */
class SensitiveDataScrubber
{
    public const string FILTERED = '[Filtered]';

    /**
     * Query parameters whose value is redacted wherever a URL or query string appears in a string.
     */
    private const array SENSITIVE_QUERY_PARAMETERS = [
        'code',
        'state',
        'secret',
        'signature',
        'auth_signature',
        'token',
        'access_token',
        'refresh_token',
        'password',
        'client_secret',
    ];

    /**
     * Array keys whose value is redacted outright, matched case-insensitively.
     */
    private const array SENSITIVE_KEYS = [
        'secret',
        'signature',
        'auth_signature',
        'token',
        'access_token',
        'refresh_token',
        'password',
        'password_confirmation',
        'client_secret',
    ];

    public static function apply(Event $event, ?EventHint $hint): ?Event
    {
        $message = $event->getMessage();
        if ($message !== null) {
            $formatted = $event->getMessageFormatted();
            $event->setMessage(
                self::scrubString($message),
                self::scrubArray($event->getMessageParams()),
                $formatted === null ? null : self::scrubString($formatted),
            );
        }

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(self::scrubString($exception->getValue()));
        }

        $event->setExtra(self::scrubArray($event->getExtra()));

        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, self::scrubArray($context));
        }

        $event->setRequest(self::scrubArray($event->getRequest()));

        $event->setBreadcrumb(array_map(
            self::scrubBreadcrumb(...),
            $event->getBreadcrumbs(),
        ));

        return $event;
    }

    public static function scrubString(string $value): string
    {
        return preg_replace_callback(
            '/(^|[?&])([^=&?\s\'"#]+)=([^&\s\'"#]*)/',
            static fn(array $match): string => in_array(self::normalizeQueryParameterName($match[2]), self::SENSITIVE_QUERY_PARAMETERS, true)
                ? sprintf('%s%s=%s', $match[1], $match[2], self::FILTERED)
                : $match[0],
            $value,
        ) ?? $value;
    }

    /**
     * Normalizes a raw query parameter name the way PHP's query parser does before the application reads it, so an
     * encoded (`%73ecret`), array (`secret[]`) or dotted (`access.token`) spelling still matches.
     */
    private static function normalizeQueryParameterName(string $rawName): string
    {
        $name = urldecode($rawName);

        $bracketPosition = strpos($name, '[');
        if ($bracketPosition !== false && $bracketPosition > 0) {
            $name = substr($name, 0, $bracketPosition);
        }

        return strtolower(str_replace(['.', ' '], '_', $name));
    }

    /**
     * @param  array<array-key, mixed> $values
     * @return array<array-key, mixed>
     */
    public static function scrubArray(array $values): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $result[$key] = self::FILTERED;
            } else {
                $result[$key] = self::scrubValue($value);
            }
        }

        return $result;
    }

    private static function scrubValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::scrubString($value);
        }

        if (is_array($value)) {
            return self::scrubArray($value);
        }

        return $value;
    }

    private static function scrubBreadcrumb(Breadcrumb $breadcrumb): Breadcrumb
    {
        $message = $breadcrumb->getMessage();
        if ($message !== null) {
            $breadcrumb = $breadcrumb->withMessage(self::scrubString($message));
        }

        foreach (self::scrubArray($breadcrumb->getMetadata()) as $name => $value) {
            $breadcrumb = $breadcrumb->withMetadata((string)$name, $value);
        }

        return $breadcrumb;
    }
}
