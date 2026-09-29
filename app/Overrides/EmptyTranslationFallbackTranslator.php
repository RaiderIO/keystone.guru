<?php

namespace App\Overrides;

use Illuminate\Translation\Translator as BaseTranslator;
use Override;

/**
 * Treats an empty string in a non-fallback locale as a missing translation, so `__()` falls back to the
 * fallback locale (en_US) instead of rendering a blank.
 *
 * The locale files carry `''` for every key a locale has no translation for yet: the game-data syncs
 * (NPC, spell and zone names) write one for anything Wowhead does not know in that language, and the
 * translation pipeline adds one for every new key. Laravel only falls back on a *missing* key, never on
 * an empty one.
 *
 * An empty string stays an empty string when the fallback locale has no value for the key either, so a
 * key that was blank before never turns into the raw key.
 */
class EmptyTranslationFallbackTranslator extends BaseTranslator
{
    public static function fromTranslator(BaseTranslator $translator): self
    {
        $result = new self($translator->getLoader(), $translator->getLocale());
        $result->setFallback($translator->getFallback());
        $result->setSelector($translator->getSelector());

        return $result;
    }

    /**
     * @param  string                     $key
     * @param  array<string, mixed>       $replace
     * @param  string|null                $locale
     * @param  bool                       $fallback
     * @return string|array<mixed, mixed>
     */
    #[Override]
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $line   = parent::get($key, $replace, $locale, $fallback);
        $locale = $locale ?: $this->locale;

        if ($line !== '' || !$fallback || $locale === $this->fallback) {
            return $line;
        }

        $fallbackLine = parent::get($key, $replace, $this->fallback, false);

        // A key the fallback locale lacks comes back as the key itself
        return is_string($fallbackLine) && $fallbackLine !== '' && $fallbackLine !== $key ? $fallbackLine : $line;
    }
}
