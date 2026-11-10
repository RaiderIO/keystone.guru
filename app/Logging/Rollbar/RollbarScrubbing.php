<?php

namespace App\Logging\Rollbar;

use Rollbar\DataBuilder;
use Rollbar\Defaults;

/**
 * Rollbar options that keep credentials and personal data out of reported payloads. Rollbar's scrubber walks the
 * whole payload (headers, POST, GET, query strings inside URLs) and matches keys case-insensitively, so header names
 * such as `Cookie` and `Authorization` are covered by listing them here. A configured `scrub_fields` replaces
 * Rollbar's own defaults rather than extending them, hence the merge.
 */
final class RollbarScrubbing
{
    /**
     * Fields scrubbed on top of Rollbar's defaults.
     */
    private const array SCRUB_FIELDS = [
        'cookie',
        'authorization',
        'php-auth-pw',
        'php_auth_pw',
        'x-csrf-token',
        'x-xsrf-token',
        '_token',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'access_key',
        'secret',
        'token',
        'code',
        'state',
        'x-amz-signature',
        'x-amz-credential',
        'x-amz-security-token',
    ];

    /**
     * @return array{scrub_fields: list<string>, capture_ip: string}
     */
    public static function options(): array
    {
        return [
            'scrub_fields' => self::scrubFields(),
            'capture_ip'   => DataBuilder::ANONYMIZE_IP,
        ];
    }

    /**
     * @return list<string>
     */
    public static function scrubFields(): array
    {
        return array_values(array_unique(array_merge(
            Defaults::get()->scrubFields(),
            self::SCRUB_FIELDS,
        )));
    }
}
