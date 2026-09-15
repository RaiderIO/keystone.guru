<?php

namespace App\Models\Spell;

/**
 * What can dispel a spell's effect.
 *
 * `spells`.`dispel_type` stores the {@see self::translationKey()} form (`spelldispeltype.magic`), not the bare
 * backing value.
 */
enum SpellDispelType: string
{
    case Magic        = 'magic';
    case Disease      = 'disease';
    case Poison       = 'poison';
    case Curse        = 'curse';
    case Enrage       = 'enrage';
    case None         = 'none';
    case NotAvailable = 'n_a';
    case Unknown      = 'unknown';

    public const string TRANSLATION_KEY_PREFIX = 'spelldispeltype.';

    /**
     * This dispel type as `spells`.`dispel_type` stores it, which is also its translation key.
     */
    public function translationKey(): string
    {
        return self::TRANSLATION_KEY_PREFIX . $this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Every dispel type in the form `spells`.`dispel_type` stores.
     *
     * @return list<string>
     */
    public static function translationKeys(): array
    {
        return array_map(static fn(self $dispelType): string => $dispelType->translationKey(), self::cases());
    }
}
