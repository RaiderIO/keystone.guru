<?php

namespace Tests\Unit\App\Service\WagoTools;

use App\Service\WagoTools\GameLocale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('SpellDescription')]
final class GameLocaleTest extends TestCase
{
    #[Test]
    #[DataProvider('appLocaleProvider')]
    public function forAppLocale_givenAnAppLocale_returnsTheClientLocaleItReads(?string $appLocale, GameLocale $expected): void
    {
        // Act
        $gameLocale = GameLocale::forAppLocale($appLocale);

        // Assert
        $this->assertSame($expected, $gameLocale);
    }

    /**
     * @return array<string, array{0: string|null, 1: GameLocale}>
     */
    public static function appLocaleProvider(): array
    {
        return [
            'a locale the client has'            => ['de_DE', GameLocale::German],
            'the AI variant of one'              => ['de_DE_ai', GameLocale::German],
            'the two Spanish locales stay apart' => ['es_MX', GameLocale::SpanishMexican],
            'a locale the client does not have'  => ['uk_UA', GameLocale::English],
            'a joke locale'                      => ['ho_HO', GameLocale::English],
            'English itself'                     => ['en_US', GameLocale::English],
            'no locale at all'                   => [null, GameLocale::English],
        ];
    }

    #[Test]
    public function translated_returnsEveryLocaleButEnglish(): void
    {
        // Act - English descriptions live on the spell itself, so they are not among the stored translations
        $translated = GameLocale::translated();

        // Assert
        $this->assertNotContains(GameLocale::English, $translated);
        $this->assertCount(count(GameLocale::cases()) - 1, $translated);
    }

    #[Test]
    public function appLocale_returnsALocaleWeActuallyOffer(): void
    {
        // Act & Assert - a game locale mapped onto a locale with no lang/ folder would never be reached
        $ourLocales = array_column(config('language.all'), 'long');

        foreach (GameLocale::cases() as $gameLocale) {
            $this->assertContains($gameLocale->appLocale(), $ourLocales, $gameLocale->value);
        }
    }
}
