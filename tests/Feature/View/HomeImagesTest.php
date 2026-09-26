<?php

namespace Tests\Feature\View;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
final class HomeImagesTest extends PublicTestCase
{
    private const string DESKTOP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    #[Test]
    public function home_givenAGuest_servesPageImagesAsWebp(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $content = $response->getContent();
        foreach (['home/featured/', 'flags/', 'gameversions/', 'logo/logo', 'dungeons/'] as $imageFolder) {
            $this->assertDoesNotMatchRegularExpression(
                sprintf('#<img[^>]+src="[^"]*/images/%s[^"]*\.(png|jpg)"#', preg_quote($imageFolder, '#')),
                $content,
                sprintf('An <img> under images/%s still points at a png/jpg instead of the webp', $imageFolder),
            );
        }
    }

    #[Test]
    public function home_givenAGuest_lazyLoadsTheFeaturedImages(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $this->assertSame(
            3,
            preg_match_all('#<img[^>]+/images/home/featured/[^>]+loading="lazy"#s', $response->getContent()),
        );
    }

    #[Test]
    public function home_givenAGuest_loadsTheHeaderLogoEagerly(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $this->assertMatchesRegularExpression('#<img src="[^"]*/images/logo/logo_and_text\.webp"#', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('#<img src="[^"]*/images/logo/logo_and_text\.webp"[^>]*loading="lazy"#', $response->getContent());
    }
}
