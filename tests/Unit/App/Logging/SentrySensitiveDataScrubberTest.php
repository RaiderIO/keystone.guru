<?php

namespace Tests\Unit\App\Logging;

use App\Logging\Sentry\BeforeSend;
use App\Logging\Sentry\SensitiveDataScrubber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\ExceptionDataBag;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
final class SentrySensitiveDataScrubberTest extends PublicTestCase
{
    #[Test]
    public function apply_givenExceptionQuotingAThumbnailCommandLine_redactsTheSecretAndKeepsOtherParameters(): void
    {
        // Arrange
        $event = Event::createEvent();
        $event->setExceptions([
            new ExceptionDataBag(new RuntimeException(
                "The process \"'node' 'route_thumbnail.js' 'https://keystone.guru/route/x/abc/x/preview/4?secret=c0ffee&z=1&killzonepathweight=3' '/tmp/abc.png'\" exceeded the timeout of 60 seconds.",
            )),
        ]);

        // Act
        $result = SensitiveDataScrubber::apply($event, null);

        // Assert
        $value = $result->getExceptions()[0]->getValue();
        self::assertStringNotContainsString('c0ffee', $value);
        self::assertStringContainsString('?secret=[Filtered]&z=1&killzonepathweight=3', $value);
    }

    #[Test]
    public function apply_givenLogContextUrlWithOAuthCodeAndState_redactsBoth(): void
    {
        // Arrange
        $event = Event::createEvent();
        $event->setExtra([
            'log_context' => [
                'correlationId' => 'f8a731e0',
                'url'           => 'https://keystone.guru/patreon-link?code=oauthcode123&state=oauthstate456',
            ],
        ]);

        // Act
        $result = SensitiveDataScrubber::apply($event, null);

        // Assert
        self::assertSame([
            'log_context' => [
                'correlationId' => 'f8a731e0',
                'url'           => 'https://keystone.guru/patreon-link?code=[Filtered]&state=[Filtered]',
            ],
        ], $result->getExtra());
    }

    #[Test]
    public function apply_givenSensitiveKeysInNestedContexts_redactsTheirValuesOnly(): void
    {
        // Arrange
        $event = Event::createEvent();
        $event->setContext('laravel', [
            'structured:patreonservicelogging::linktouseraccounttokens' => [
                'tokens' => [
                    'access_token'  => 'access123',
                    'Refresh_Token' => 'refresh456',
                    'scope'         => 'identity',
                    'expires_in'    => 2678400,
                ],
            ],
        ]);

        // Act
        $result = SensitiveDataScrubber::apply($event, null);

        // Assert
        self::assertSame([
            'tokens' => [
                'access_token'  => '[Filtered]',
                'Refresh_Token' => '[Filtered]',
                'scope'         => 'identity',
                'expires_in'    => 2678400,
            ],
        ], $result->getContexts()['laravel']['structured:patreonservicelogging::linktouseraccounttokens']);
    }

    #[Test]
    public function apply_givenRequestQueryString_redactsSensitiveParameters(): void
    {
        // Arrange
        $event = Event::createEvent();
        $event->setRequest([
            'url'          => 'https://keystone.guru/patreon-link',
            'method'       => 'GET',
            'query_string' => 'code=oauthcode123&state=oauthstate456&utm_source=mail',
        ]);

        // Act
        $result = SensitiveDataScrubber::apply($event, null);

        // Assert
        self::assertSame(
            'code=[Filtered]&state=[Filtered]&utm_source=mail',
            $result->getRequest()['query_string'],
        );
        self::assertSame('https://keystone.guru/patreon-link', $result->getRequest()['url']);
    }

    #[Test]
    public function apply_givenBreadcrumbWithSensitiveMessageAndMetadata_redactsBoth(): void
    {
        // Arrange
        $event = Event::createEvent();
        $event->setBreadcrumb([
            new Breadcrumb(
                Breadcrumb::LEVEL_INFO,
                Breadcrumb::TYPE_DEFAULT,
                'log.debug',
                'GET https://keystone.guru/patreon-link?code=oauthcode123',
                ['password' => 'hunter2', 'url' => 'https://keystone.guru/x?token=abc&page=2', 'count' => 3],
            ),
        ]);

        // Act
        $result = SensitiveDataScrubber::apply($event, null);

        // Assert
        $breadcrumb = $result->getBreadcrumbs()[0];
        self::assertSame('GET https://keystone.guru/patreon-link?code=[Filtered]', $breadcrumb->getMessage());
        self::assertSame([
            'password' => '[Filtered]',
            'url'      => 'https://keystone.guru/x?token=[Filtered]&page=2',
            'count'    => 3,
        ], $breadcrumb->getMetadata());
    }

    #[Test]
    public function apply_givenMessageEventWithSensitiveUrl_redactsTheMessage(): void
    {
        // Arrange
        $event = Event::createEvent();
        $event->setMessage('Request to https://keystone.guru/x?signature=abc123 failed');

        // Act
        $result = SensitiveDataScrubber::apply($event, null);

        // Assert
        self::assertSame('Request to https://keystone.guru/x?signature=[Filtered] failed', $result->getMessage());
    }

    #[Test]
    #[DataProvider('scrubString_nonSensitiveStrings_provider')]
    public function scrubString_givenNoSensitiveParameter_returnsTheStringUnchanged(string $value): void
    {
        // Act
        $result = SensitiveDataScrubber::scrubString($value);

        // Assert
        self::assertSame($value, $result);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function scrubString_nonSensitiveStrings_provider(): array
    {
        return [
            'plain message'              => ['PatreonServiceLogging::linkToUserAccountIdentityIncludedNotSet'],
            'url without query'          => ['https://keystone.guru/patreon-link'],
            'non-sensitive query'        => ['https://keystone.guru/routes?page=2&sort=rating'],
            'parameter name as a suffix' => ['https://keystone.guru/x?barcode=123&zipcode=456'],
        ];
    }

    #[Test]
    public function beforeSend_givenSensitiveLogContext_scrubsIt(): void
    {
        // Arrange
        $event = Event::createEvent();
        $event->setExtra(['log_context' => ['url' => 'https://keystone.guru/patreon-link?code=oauthcode123']]);

        // Act
        $result = BeforeSend::apply($event, EventHint::fromArray([]));

        // Assert
        self::assertNotNull($result);
        self::assertSame(
            ['log_context' => ['url' => 'https://keystone.guru/patreon-link?code=[Filtered]']],
            $result->getExtra(),
        );
    }
}
