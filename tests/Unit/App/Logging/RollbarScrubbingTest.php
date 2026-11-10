<?php

namespace Tests\Unit\App\Logging;

use App\Logging\Rollbar\RollbarScrubbing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Rollbar\Config;
use Rollbar\DataBuilder;
use Rollbar\Defaults;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
final class RollbarScrubbingTest extends PublicTestCase
{
    #[Test]
    public function scrubFields_givenRollbarDefaults_containsEveryDefaultField(): void
    {
        // Arrange
        $defaults = Defaults::get()->scrubFields();

        // Act
        $scrubFields = RollbarScrubbing::scrubFields();

        // Assert
        self::assertNotEmpty($defaults);
        foreach ($defaults as $default) {
            self::assertContains($default, $scrubFields);
        }
    }

    #[Test]
    #[DataProvider('sensitiveFieldProvider')]
    public function options_givenSensitiveField_listsItAsScrubField(string $field): void
    {
        // Arrange

        // Act
        $options = RollbarScrubbing::options();

        // Assert
        self::assertContains($field, $options['scrub_fields']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sensitiveFieldProvider(): array
    {
        $fields = [
            'cookie',
            'authorization',
            'php-auth-pw',
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

        return array_combine($fields, array_map(static fn(string $field): array => [$field], $fields));
    }

    #[Test]
    public function options_givenDefaultConfiguration_anonymizesTheClientIp(): void
    {
        // Arrange

        // Act
        $options = RollbarScrubbing::options();

        // Assert
        self::assertSame(DataBuilder::ANONYMIZE_IP, $options['capture_ip']);
    }

    #[Test]
    public function logger_givenBootedApplication_usesTheScrubbingOptions(): void
    {
        // Arrange
        $logger = Rollbar::logger();
        self::assertNotNull($logger);

        // Act
        $configArray = $logger->getConfig()->getConfigArray();

        // Assert
        self::assertSame(RollbarScrubbing::scrubFields(), $configArray['scrub_fields'] ?? null);
        self::assertSame(DataBuilder::ANONYMIZE_IP, $configArray['capture_ip'] ?? null);
    }

    #[Test]
    public function scrub_givenRequestWithCredentials_scrubsHeadersPostFieldsQueryAndIp(): void
    {
        // Arrange
        $originalServer = $_SERVER;
        $originalPost   = $_POST;
        $originalGet    = $_GET;

        try {
            $_SERVER['HTTP_COOKIE']        = 'keystoneguru_session=cookievalue123';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Basic YXBpdXNlcjphcGlwYXNz';
            $_SERVER['HTTP_X_CSRF_TOKEN']  = 'csrfvalue456';
            $_SERVER['HTTP_USER_AGENT']    = 'phpunit-agent';
            $_SERVER['HTTP_HOST']          = 'keystone.guru';
            $_SERVER['REQUEST_URI']        = '/patreon-link?code=oauthcode789&state=oauthstate012&page=2';
            $_SERVER['QUERY_STRING']       = 'code=oauthcode789&state=oauthstate012&page=2';
            $_SERVER['REMOTE_ADDR']        = '203.0.113.77';
            $_SERVER['REQUEST_METHOD']     = 'POST';
            unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP']);
            $_GET  = ['code' => 'oauthcode789', 'state' => 'oauthstate012', 'page' => '2'];
            $_POST = [
                '_token'           => 'formtoken345',
                'current_password' => 'currentpass678',
                'new_password'     => 'newpass901',
                'name'             => 'Visible Name',
            ];

            $config = new Config(array_merge([
                'access_token' => str_repeat('a', 32),
                'environment'  => 'testing',
                'enabled'      => false,
            ], RollbarScrubbing::options()));

            $serialized = $config->getDataBuilder()->makeData(Level::ERROR, 'Something failed', [])->serialize();

            // Act
            $scrubbed = $config->getScrubber()->scrub($serialized);

            // Assert
            $request = $scrubbed['request'];
            $json    = json_encode($scrubbed, JSON_THROW_ON_ERROR);
            foreach ([
                'cookievalue123',
                'YXBpdXNlcjphcGlwYXNz',
                'csrfvalue456',
                'formtoken345',
                'currentpass678',
                'newpass901',
                'oauthcode789',
                'oauthstate012',
                '203.0.113.77',
            ] as $secret) {
                self::assertStringNotContainsString($secret, $json);
            }
            self::assertSame('phpunit-agent', $request['headers']['User-Agent']);
            self::assertSame('Visible Name', $request['POST']['name']);
            self::assertSame('2', $request['GET']['page']);
            self::assertSame('203.0.113.0', $request['user_ip']);
        } finally {
            $_SERVER = $originalServer;
            $_POST   = $originalPost;
            $_GET    = $originalGet;
        }
    }
}
