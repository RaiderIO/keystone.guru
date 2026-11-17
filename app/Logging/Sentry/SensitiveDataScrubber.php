<?php

namespace App\Logging\Sentry;

use App\Logging\SensitiveDataRedactor;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;

/**
 * Sentry `before_send` callback that redacts secrets before an event leaves the application, using the patterns in
 * {@see SensitiveDataRedactor}. Secrets reach events through query strings (the full request URL in the shared log
 * context, a puppeteer command line quoted in a `ProcessTimedOutException` message), through same-named array keys in
 * log context and through the request the SDK attaches. Cookies, credential headers and the client IP are removed
 * whatever `send_default_pii` says; the user id the SDK sets is kept.
 */
class SensitiveDataScrubber
{
    public static function apply(Event $event, ?EventHint $hint): ?Event
    {
        $message = $event->getMessage();
        if ($message !== null) {
            $formatted = $event->getMessageFormatted();
            $event->setMessage(
                SensitiveDataRedactor::scrubString($message),
                SensitiveDataRedactor::scrubArray($event->getMessageParams()),
                $formatted === null ? null : SensitiveDataRedactor::scrubString($formatted),
            );
        }

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(SensitiveDataRedactor::scrubString($exception->getValue()));
        }

        $event->setExtra(SensitiveDataRedactor::scrubArray($event->getExtra()));

        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, SensitiveDataRedactor::scrubArray($context));
        }

        $event->setRequest(self::scrubRequest($event->getRequest()));

        $user = $event->getUser();
        $user?->setIpAddress(null);
        if ($user?->getEmail() !== null) {
            $user->setEmail(SensitiveDataRedactor::maskEmail($user->getEmail()));
        }

        $event->setBreadcrumb(array_map(
            self::scrubBreadcrumb(...),
            $event->getBreadcrumbs(),
        ));

        return $event;
    }

    /**
     * @param  array<string, mixed> $request
     * @return array<string, mixed>
     */
    private static function scrubRequest(array $request): array
    {
        unset($request['cookies']);

        $data = $request['data'] ?? null;
        unset($request['data']);

        $request = SensitiveDataRedactor::scrubArray($request);

        if (is_array($data)) {
            $request['data'] = SensitiveDataRedactor::scrubArray($data, true);
        } elseif (is_string($data)) {
            $request['data'] = SensitiveDataRedactor::scrubString($data);
        }

        return $request;
    }

    private static function scrubBreadcrumb(Breadcrumb $breadcrumb): Breadcrumb
    {
        $message = $breadcrumb->getMessage();
        if ($message !== null) {
            $breadcrumb = $breadcrumb->withMessage(SensitiveDataRedactor::scrubString($message));
        }

        foreach (SensitiveDataRedactor::scrubArray($breadcrumb->getMetadata()) as $name => $value) {
            $breadcrumb = $breadcrumb->withMetadata((string)$name, $value);
        }

        return $breadcrumb;
    }
}
