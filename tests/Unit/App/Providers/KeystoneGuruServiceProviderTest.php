<?php

namespace Tests\Unit\App\Providers;

use App\Providers\KeystoneGuruServiceProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Providers')]
#[Group('KeystoneGuruServiceProvider')]
class KeystoneGuruServiceProviderTest extends PublicTestCase
{
    #[Test]
    public function configureSessionCookie_givenHeadersNotSent_setsSameSiteNoneCookieParams(): void
    {
        // Arrange
        $originalCookieParams = session_get_cookie_params();

        try {
            // Act
            $this->configureSessionCookie(false);

            // Assert
            $cookieParams = session_get_cookie_params();
            self::assertSame('None', $cookieParams['samesite']);
            self::assertTrue($cookieParams['secure']);
            self::assertFalse($cookieParams['httponly']);
        } finally {
            session_set_cookie_params($originalCookieParams);
        }
    }

    #[Test]
    public function configureSessionCookie_givenHeadersAlreadySent_leavesCookieParamsUntouched(): void
    {
        // Arrange
        $originalCookieParams = session_get_cookie_params();
        session_set_cookie_params([...$originalCookieParams, 'samesite' => 'Lax']);

        try {
            // Act
            $this->configureSessionCookie(true);

            // Assert
            self::assertSame('Lax', session_get_cookie_params()['samesite']);
        } finally {
            session_set_cookie_params($originalCookieParams);
        }
    }

    private function configureSessionCookie(bool $headersAlreadySent): void
    {
        $provider = new class(app(), $headersAlreadySent) extends KeystoneGuruServiceProvider {
            public function __construct($app, private readonly bool $headersSent)
            {
                parent::__construct($app);
            }

            public function callConfigureSessionCookie(): void
            {
                $this->configureSessionCookie();
            }

            protected function headersAlreadySent(): bool
            {
                return $this->headersSent;
            }
        };

        $provider->callConfigureSessionCookie();
    }
}
