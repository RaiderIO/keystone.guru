<?php

namespace Tests\Unit\App\Overrides;

use App\Overrides\AiLocaleMessageSelector;
use App\Overrides\EmptyTranslationFallbackTranslator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('EmptyTranslationFallbackTranslator')]
final class EmptyTranslationFallbackTranslatorTest extends TestCase
{
    #[Test]
    public function get_givenLocaleWithTranslation_returnsThatTranslation(): void
    {
        // Arrange
        $translator = $this->createTranslator(['name' => 'Priorat der Heiligen Flamme'], ['name' => 'Priory of the Sacred Flame']);

        // Act
        $line = $translator->get('dungeons.name');

        // Assert
        $this->assertSame('Priorat der Heiligen Flamme', $line);
    }

    #[Test]
    public function get_givenLocaleWithEmptyTranslation_returnsFallbackTranslation(): void
    {
        // Arrange
        $translator = $this->createTranslator(['name' => ''], ['name' => 'Priory of the Sacred Flame']);

        // Act
        $line = $translator->get('dungeons.name');

        // Assert
        $this->assertSame('Priory of the Sacred Flame', $line);
    }

    #[Test]
    public function get_givenLocaleWithEmptyTranslationAndReplacements_returnsFallbackTranslationWithReplacements(): void
    {
        // Arrange
        $translator = $this->createTranslator(['title' => ''], ['title' => 'NPC :name']);

        // Act
        $line = $translator->get('dungeons.title', ['name' => 'Kobold']);

        // Assert
        $this->assertSame('NPC Kobold', $line);
    }

    #[Test]
    public function get_givenEmptyTranslationInLocaleAndFallback_returnsEmptyString(): void
    {
        // Arrange
        $translator = $this->createTranslator(['decimal' => ''], ['decimal' => '']);

        // Act
        $line = $translator->get('dungeons.decimal');

        // Assert
        $this->assertSame('', $line);
    }

    #[Test]
    public function get_givenEmptyTranslationAndKeyMissingFromFallback_returnsEmptyString(): void
    {
        // Arrange
        $translator = $this->createTranslator(['name' => ''], []);

        // Act
        $line = $translator->get('dungeons.name');

        // Assert
        $this->assertSame('', $line);
    }

    #[Test]
    public function get_givenEmptyTranslationWithFallbackDisabled_returnsEmptyString(): void
    {
        // Arrange
        $translator = $this->createTranslator(['name' => ''], ['name' => 'Priory of the Sacred Flame']);

        // Act
        $line = $translator->get('dungeons.name', [], null, false);

        // Assert
        $this->assertSame('', $line);
    }

    #[Test]
    public function get_givenEmptyTranslationInFallbackLocaleItself_returnsEmptyString(): void
    {
        // Arrange
        $translator = $this->createTranslator(['name' => 'Priorat der Heiligen Flamme'], ['name' => '']);

        // Act
        $line = $translator->get('dungeons.name', [], 'en_US');

        // Assert
        $this->assertSame('', $line);
    }

    #[Test]
    public function get_givenMissingKey_returnsTheKey(): void
    {
        // Arrange
        $translator = $this->createTranslator([], []);

        // Act
        $line = $translator->get('dungeons.missing');

        // Assert
        $this->assertSame('dungeons.missing', $line);
    }

    #[Test]
    public function choice_givenLocaleWithEmptyTranslation_returnsFallbackTranslation(): void
    {
        // Arrange
        $translator = $this->createTranslator(['routes' => ''], ['routes' => ':count route|:count routes']);

        // Act
        $line = $translator->choice('dungeons.routes', 3);

        // Assert
        $this->assertSame('3 routes', $line);
    }

    #[Test]
    public function translator_givenApplicationContainer_isTheEmptyTranslationFallbackTranslator(): void
    {
        // Arrange & Act
        $translator = app('translator');

        // Assert
        $this->assertInstanceOf(EmptyTranslationFallbackTranslator::class, $translator);
        $this->assertInstanceOf(AiLocaleMessageSelector::class, $translator->getSelector());
        $this->assertSame(config('app.fallback_locale'), $translator->getFallback());
    }

    #[Test]
    public function fromTranslator_givenATranslator_keepsItsLoaderLocaleAndFallback(): void
    {
        // Arrange
        $loader   = new ArrayLoader();
        $original = new Translator($loader, 'de_DE_ai');
        $original->setFallback('en_US');

        // Act
        $translator = EmptyTranslationFallbackTranslator::fromTranslator($original);

        // Assert
        $this->assertSame($loader, $translator->getLoader());
        $this->assertSame('de_DE_ai', $translator->getLocale());
        $this->assertSame('en_US', $translator->getFallback());
    }

    #[Test]
    public function choice_givenApplicationContainerAndAiLocale_selectsTheBaseLocalePluralForm(): void
    {
        // Arrange
        $translator = app('translator');
        $translator->addLines(['ksg_plural_test.routes' => ':count маршрут|:count маршрута|:count маршрутов'], 'ru_RU_ai');

        // Act
        $line = $translator->choice('ksg_plural_test.routes', 5, [], 'ru_RU_ai');

        // Assert
        $this->assertSame('5 маршрутов', $line);
        $this->assertInstanceOf(AiLocaleMessageSelector::class, $translator->getSelector());
    }

    #[Test]
    public function translate_givenAiLocaleWithEmptyTranslation_returnsEnglishTranslation(): void
    {
        // Arrange
        app('translator')->addLines(['npcs.999999101' => 'English Name'], 'en_US');
        app('translator')->addLines(['npcs.999999101' => ''], 'de_DE_ai');
        app()->setLocale('de_DE_ai');

        // Act
        $line = __('npcs.999999101');

        // Assert
        $this->assertSame('English Name', $line);
    }

    /**
     * @param array<string, string> $localeLines
     * @param array<string, string> $fallbackLines
     */
    private function createTranslator(array $localeLines, array $fallbackLines): EmptyTranslationFallbackTranslator
    {
        $loader = new ArrayLoader();
        $loader->addMessages('de_DE_ai', 'dungeons', $localeLines);
        $loader->addMessages('en_US', 'dungeons', $fallbackLines);

        $translator = new EmptyTranslationFallbackTranslator($loader, 'de_DE_ai');
        $translator->setFallback('en_US');
        $translator->setSelector(new AiLocaleMessageSelector());

        return $translator;
    }
}
