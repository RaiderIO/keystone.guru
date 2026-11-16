<?php

namespace Tests\Unit\App\Logging;

use App\Logging\SensitiveDataRedactor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
final class SensitiveDataRedactorTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('isSensitiveKey_sensitiveKeys_provider')]
    public function isSensitiveKey_givenSensitiveKey_returnsTrue(string $key): void
    {
        // Act
        $result = SensitiveDataRedactor::isSensitiveKey($key);

        // Assert
        self::assertTrue($result);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function isSensitiveKey_sensitiveKeys_provider(): array
    {
        $keys = [
            'password',
            'current_password',
            'new_password',
            'new_password-confirm',
            'password_confirmation',
            'confirm_password',
            'passwd',
            'PHP_AUTH_PW',
            'php-auth-pw',
            'secret',
            'client_secret',
            '_token',
            'token',
            'access_token',
            'refresh_token',
            'remember_token',
            'accessToken',
            'auth_token',
            'csrf_token',
            'X-CSRF-TOKEN',
            'X-XSRF-TOKEN',
            'XSRF-TOKEN',
            'api_key',
            'apiKey',
            'APIKEY',
            'access_key',
            'Authorization',
            'auth',
            'Cookie',
            'Set-Cookie',
            'HTTP_COOKIE',
            'keystoneguru_session',
            'remember_web_59ba36addc2b2f9401580f014c7f58ea4e30989d',
            'signature',
            'auth_signature',
            'X-Amz-Signature',
            'X-Amz-Credential',
            'X-Amz-Security-Token',
            'X-Forwarded-For',
            'HTTP_X_FORWARDED_FOR',
            'Forwarded',
            'X-Real-IP',
            'CF-Connecting-IP',
            'True-Client-IP',
            'X-Client-IP',
            'REMOTE_ADDR',
        ];

        return array_combine($keys, array_map(static fn(string $key): array => [$key], $keys));
    }

    #[Test]
    #[DataProvider('isSensitiveKey_harmlessKeys_provider')]
    public function isSensitiveKey_givenHarmlessKey_returnsFalse(string $key): void
    {
        // Act
        $result = SensitiveDataRedactor::isSensitiveKey($key);

        // Assert
        self::assertFalse($result);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function isSensitiveKey_harmlessKeys_provider(): array
    {
        $keys = [
            'title',
            'dungeon_id',
            'author_id',
            'authored_at',
            'name',
            'tokens',
            'token_type',
            'code',
            'state',
            'User-Agent',
            'X-Forwarded-Proto',
            'description',
            'url',
        ];

        return array_combine($keys, array_map(static fn(string $key): array => [$key], $keys));
    }

    #[Test]
    #[DataProvider('isSensitiveRequestParameter_oauthParameters_provider')]
    public function isSensitiveRequestParameter_givenOAuthParameter_returnsTrue(string $name): void
    {
        // Act
        $result = SensitiveDataRedactor::isSensitiveRequestParameter($name);

        // Assert
        self::assertTrue($result);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function isSensitiveRequestParameter_oauthParameters_provider(): array
    {
        return [
            'code'  => ['code'],
            'state' => ['state'],
        ];
    }

    #[Test]
    public function scrubArray_givenNestedArrays_redactsSensitiveKeysAtEveryDepth(): void
    {
        // Arrange
        $values = [
            'title'  => 'My route',
            'nested' => [
                'dungeon_id' => 12,
                'deeper'     => [
                    'new_password-confirm' => 'hunter3',
                    'X-Forwarded-For'      => '203.0.113.7',
                    'url'                  => 'https://keystone.guru/x?refresh_token=abc&page=2',
                ],
            ],
            'list'         => [['api_key' => 'k3y'], ['name' => 'Wotuu']],
            'Cookie'       => ['keystoneguru_session' => 'abc'],
            'code'         => 404,
            'hasSignature' => true,
        ];

        // Act
        $result = SensitiveDataRedactor::scrubArray($values);

        // Assert
        self::assertSame([
            'title'  => 'My route',
            'nested' => [
                'dungeon_id' => 12,
                'deeper'     => [
                    'new_password-confirm' => '[Filtered]',
                    'X-Forwarded-For'      => '[Filtered]',
                    'url'                  => 'https://keystone.guru/x?refresh_token=[Filtered]&page=2',
                ],
            ],
            'list'         => [['api_key' => '[Filtered]'], ['name' => 'Wotuu']],
            'Cookie'       => '[Filtered]',
            'code'         => 404,
            'hasSignature' => true,
        ], $result);
    }

    #[Test]
    public function scrubArray_givenRequestInput_alsoRedactsOAuthParameters(): void
    {
        // Arrange
        $values = ['code' => 'oauthcode123', 'state' => 'oauthstate456', 'page' => '2'];

        // Act
        $result = SensitiveDataRedactor::scrubArray($values, true);

        // Assert
        self::assertSame(['code' => '[Filtered]', 'state' => '[Filtered]', 'page' => '2'], $result);
    }

    #[Test]
    #[DataProvider('scrubString_sensitiveUrls_provider')]
    public function scrubString_givenUrlWithSensitiveParameter_redactsOnlyThatValue(string $value, string $expected): void
    {
        // Act
        $result = SensitiveDataRedactor::scrubString($value);

        // Assert
        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function scrubString_sensitiveUrls_provider(): array
    {
        return [
            'oauth callback' => [
                'https://keystone.guru/patreon-link?code=oauthcode123&state=oauthstate456&page=2',
                'https://keystone.guru/patreon-link?code=[Filtered]&state=[Filtered]&page=2',
            ],
            'camelCase api key' => [
                'https://example.com/v1?apiKey=k3y&region=eu',
                'https://example.com/v1?apiKey=[Filtered]&region=eu',
            ],
            'client secret and refresh token' => [
                'grant_type=refresh_token&refresh_token=r3fresh&client_secret=s3cret&client_id=12',
                'grant_type=refresh_token&refresh_token=[Filtered]&client_secret=[Filtered]&client_id=12',
            ],
        ];
    }
}
