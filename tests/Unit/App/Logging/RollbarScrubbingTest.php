<?php

namespace Tests\Unit\App\Logging;

use App\Logging\Rollbar\RollbarScrubbing;
use App\Logging\SensitiveDataRedactor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Rollbar\Config;
use Rollbar\DataBuilder;
use Rollbar\Defaults;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
final class RollbarScrubbingTest extends PublicTestCase
{
    #[Test]
    public function scrub_givenRollbarDefaultScrubFields_redactsEveryOne(): void
    {
        // Arrange
        $defaults = Defaults::get()->scrubFields();
        $data     = ['custom' => array_fill_keys($defaults, 'sensitive-value')];

        // Act
        $scrubbed = new RollbarScrubbing()->scrub($data);

        // Assert
        self::assertNotEmpty($defaults);
        self::assertSame(array_fill_keys($defaults, SensitiveDataRedactor::FILTERED), $scrubbed['custom']);
    }

    #[Test]
    #[DataProvider('sensitiveFieldProvider')]
    public function scrub_givenSensitiveRequestField_redactsIt(string $field): void
    {
        // Arrange
        $data = ['request' => ['POST' => [$field => 'sensitive-value', 'name' => 'Visible Name']]];

        // Act
        $scrubbed = new RollbarScrubbing()->scrub($data);

        // Assert
        self::assertSame([$field => SensitiveDataRedactor::FILTERED, 'name' => 'Visible Name'], $scrubbed['request']['POST']);
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
            'new_password-confirm',
            'access_key',
            'secret',
            'token',
            'code',
            'state',
            'x-amz-signature',
            'x-amz-credential',
            'x-amz-security-token',
            'refresh_token',
            'client_secret',
            'signature',
            'set-cookie',
            'remember_token',
            'api_key',
            'x-forwarded-for',
        ];

        return array_combine($fields, array_map(static fn(string $field): array => [$field], $fields));
    }

    #[Test]
    public function options_givenDefaultConfiguration_disablesLocalVarsDump(): void
    {
        // Arrange

        // Act
        $options = RollbarScrubbing::options();

        // Assert
        self::assertArrayHasKey('local_vars_dump', $options);
        self::assertFalse($options['local_vars_dump']);
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
        self::assertInstanceOf(RollbarScrubbing::class, $logger->getConfig()->getScrubber());
        self::assertSame(DataBuilder::ANONYMIZE_IP, $configArray['capture_ip'] ?? null);
        self::assertFalse($configArray['local_vars_dump'] ?? null);
    }

    #[Test]
    public function scrub_givenRequestWithCredentials_scrubsHeadersPostFieldsQueryAndIps(): void
    {
        // Arrange
        $originalServer = $_SERVER;
        $originalPost   = $_POST;
        $originalGet    = $_GET;

        try {
            $_SERVER['HTTP_COOKIE']           = 'keystoneguru_session=cookievalue123';
            $_SERVER['HTTP_AUTHORIZATION']    = 'Basic YXBpdXNlcjphcGlwYXNz';
            $_SERVER['HTTP_X_CSRF_TOKEN']     = 'csrfvalue456';
            $_SERVER['HTTP_USER_AGENT']       = 'phpunit-agent';
            $_SERVER['HTTP_HOST']             = 'keystone.guru';
            $_SERVER['REQUEST_URI']           = '/patreon-link?code=oauthcode789&state=oauthstate012&page=2';
            $_SERVER['QUERY_STRING']          = 'code=oauthcode789&state=oauthstate012&page=2';
            $_SERVER['REMOTE_ADDR']           = '203.0.113.77';
            $_SERVER['REQUEST_METHOD']        = 'POST';
            $_SERVER['HTTP_X_FORWARDED_FOR']  = '198.51.100.23';
            $_SERVER['HTTP_X_REAL_IP']        = '198.51.100.24';
            $_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.25';
            $_GET                             = ['code' => 'oauthcode789', 'state' => 'oauthstate012', 'page' => '2'];
            $_POST                            = [
                '_token'               => 'formtoken345',
                'current_password'     => 'currentpass678',
                'new_password'         => 'newpass901',
                'new_password-confirm' => 'newconfirm234',
                'name'                 => 'Visible Name',
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
                'newconfirm234',
                'oauthcode789',
                'oauthstate012',
                '203.0.113.77',
                '198.51.100.23',
                '198.51.100.24',
                '198.51.100.25',
            ] as $secret) {
                self::assertStringNotContainsString($secret, $json);
            }
            self::assertSame('phpunit-agent', $request['headers']['User-Agent']);
            self::assertSame('Visible Name', $request['POST']['name']);
            self::assertSame('2', $request['GET']['page']);
            self::assertSame('198.51.100.0', $request['user_ip']);
        } finally {
            $_SERVER = $originalServer;
            $_POST   = $originalPost;
            $_GET    = $originalGet;
        }
    }

    #[Test]
    public function makeData_givenExceptionThrownInsideAFunctionWithASecretArgument_omitsFrameArguments(): void
    {
        // Arrange
        $config = new Config(array_merge([
            'access_token' => str_repeat('a', 32),
            'environment'  => 'testing',
            'enabled'      => false,
        ], RollbarScrubbing::options()));
        $throwingFunction = static fn(string $password): RuntimeException => new RuntimeException('Something failed');
        $exception        = $throwingFunction('framesecret567');

        // Act
        $serialized = $config->getDataBuilder()->makeData(Level::ERROR, $exception, [])->serialize();

        // Assert
        self::assertNotEmpty($serialized['body']['trace']['frames'] ?? []);
        self::assertStringNotContainsString('framesecret567', json_encode($serialized, JSON_THROW_ON_ERROR));
    }
}
