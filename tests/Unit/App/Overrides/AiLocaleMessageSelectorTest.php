<?php

namespace Tests\Unit\App\Overrides;

use App\Overrides\AiLocaleMessageSelector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('AiLocaleMessageSelector')]
class AiLocaleMessageSelectorTest extends TestCase
{
    #[Test]
    #[DataProvider('getPluralIndex_givenAiLocale_matchesBaseLocale_dataProvider')]
    public function getPluralIndex_givenAiLocale_matchesBaseLocale(string $aiLocale, string $baseLocale, int $number): void
    {
        // Arrange
        $selector = new AiLocaleMessageSelector();

        // Act
        $aiIndex   = $selector->getPluralIndex($aiLocale, $number);
        $baseIndex = $selector->getPluralIndex($baseLocale, $number);

        // Assert
        $this->assertSame($baseIndex, $aiIndex);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function getPluralIndex_givenAiLocale_matchesBaseLocale_dataProvider(): array
    {
        return [
            'french, singular'     => ['fr_FR_ai', 'fr_FR', 1],
            'french, plural'       => ['fr_FR_ai', 'fr_FR', 7],
            'russian, one'         => ['ru_RU_ai', 'ru_RU', 1],
            'russian, few'         => ['ru_RU_ai', 'ru_RU', 3],
            'russian, many'        => ['ru_RU_ai', 'ru_RU', 5],
            'german, singular'     => ['de_DE_ai', 'de_DE', 1],
            'german, plural'       => ['de_DE_ai', 'de_DE', 2],
            'korean, no agreement' => ['ko_KR_ai', 'ko_KR', 5],
        ];
    }

    #[Test]
    public function getPluralIndex_givenNonAiLocale_isUnaffected(): void
    {
        // Arrange
        $selector = new AiLocaleMessageSelector();

        // Act
        $result = $selector->getPluralIndex('ru_RU', 5);

        // Assert
        $this->assertSame(2, $result);
    }

    #[Test]
    public function transChoice_givenFrenchAiLocaleAndPluralCount_selectsPluralSegment(): void
    {
        $originalLocale = app()->getLocale();

        try {
            // Arrange
            app()->setLocale('fr_FR_ai');

            // Act
            $result = trans_choice('view_compendium.event.count', 7, ['count' => 7]);

            // Assert
            $this->assertSame('7 événements', $result);
        } finally {
            app()->setLocale($originalLocale);
        }
    }
}
