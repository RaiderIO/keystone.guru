<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\Spell\SpellDescriptionTranslation;
use App\SeederHelpers\RelationImport\Parsers\Attribute\JsonAttributeParser;
use App\Service\WagoTools\GameLocale;

/**
 * Loads one locale's `spell_description_translations_<locale>.json` - every spell's description as the
 * game client writes it in that language, rendered on a dev machine by
 * `wagotools:importspelldescriptions`.
 *
 * Files of their own rather than a relation nested under each spell, and one per locale rather than one
 * for all of them: together they are several times the size of the rest of the mapping put together, and
 * a locale's file only changes when that locale's client text does.
 */
class SpellDescriptionTranslationRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct(GameLocale $locale)
    {
        parent::__construct(self::getFileNameForLocale($locale), SpellDescriptionTranslation::class);

        $this->setAttributeParsers(collect([
            new JsonAttributeParser(['description_values']),
        ]));
    }

    public static function getFileNameForLocale(GameLocale $locale): string
    {
        return sprintf('spell_description_translations_%s.json', $locale->value);
    }
}
