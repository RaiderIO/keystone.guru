<?php

namespace Tests\Feature\Traits;

use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Issuing or validating a real OAuth token needs Passport's signing key pair, which is never committed and does not
 * exist on CI. This generates one per test process into a temporary directory and points Passport at it, so parallel
 * workers never overwrite each other's keys.
 *
 * @mixin TestCase
 */
trait UsesPassportKeys
{
    private static ?string $passportKeyPath = null;

    protected function usePassportKeys(): void
    {
        if (self::$passportKeyPath === null) {
            $path = sprintf('%s/passport-keys-%d', sys_get_temp_dir(), getmypid());
            if (!is_dir($path)) {
                mkdir($path, 0700, true);
            }

            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $privateKey);
            file_put_contents(sprintf('%s/oauth-private.key', $path), $privateKey);
            file_put_contents(sprintf('%s/oauth-public.key', $path), openssl_pkey_get_details($key)['key']);
            chmod(sprintf('%s/oauth-private.key', $path), 0600);
            chmod(sprintf('%s/oauth-public.key', $path), 0600);

            self::$passportKeyPath = $path;
        }

        Passport::loadKeysFrom(self::$passportKeyPath);
    }
}
