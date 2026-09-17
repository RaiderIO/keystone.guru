<?php

namespace App\Models\Traits;

/**
 * For an int-backed enum whose cases are single bits of a mask column, and whose slugs double as translation-key
 * suffixes.
 */
trait BitmaskEnum
{
    abstract public function slug(): string;

    /**
     * This case's translation key - the prefix comes from the enum's own TRANSLATION_KEY_PREFIX.
     */
    public function translationKey(): string
    {
        return static::TRANSLATION_KEY_PREFIX . $this->slug();
    }

    /**
     * Every case set in the given mask, translated and comma separated, in declaration order.
     */
    public static function maskToTranslatedString(int $mask): string
    {
        $result = [];

        foreach (self::cases() as $case) {
            if ($case->isSetIn($mask)) {
                $result[] = __($case->translationKey());
            }
        }

        return implode(', ', $result);
    }

    /**
     * Every case's slug keyed by its bit, in declaration order - the shape the map context's static data expects.
     *
     * @return array<int, string>
     */
    public static function slugsByBit(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[$case->value] = $case->slug();
        }

        return $result;
    }

    /**
     * Whether this case's bit is set in the given mask.
     */
    public function isSetIn(int $mask): bool
    {
        return ($mask & $this->value) !== 0;
    }
}
