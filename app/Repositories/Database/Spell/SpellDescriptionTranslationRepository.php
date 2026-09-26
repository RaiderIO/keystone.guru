<?php

namespace App\Repositories\Database\Spell;

use App\Models\Spell\SpellDescriptionTranslation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Spell\SpellDescriptionTranslationRepositoryInterface;
use App\Service\WagoTools\GameLocale;

class SpellDescriptionTranslationRepository extends DatabaseRepository implements SpellDescriptionTranslationRepositoryInterface
{
    private const int PERSIST_CHUNK_SIZE = 500;

    public function __construct()
    {
        parent::__construct(SpellDescriptionTranslation::class);
    }

    public function replaceForLocale(GameLocale $locale, array $spellIds, array $formatsAndValues): int
    {
        $rows = [];

        foreach ($formatsAndValues as $spellId => $formatAndValues) {
            $rows[] = [
                'spell_id'           => $spellId,
                'locale'             => $locale->value,
                'description_format' => $formatAndValues['format'],
                'description_values' => json_encode($formatAndValues['values']),
            ];
        }

        foreach (array_chunk($rows, self::PERSIST_CHUNK_SIZE) as $chunk) {
            SpellDescriptionTranslation::query()->upsert($chunk, ['spell_id', 'locale'], ['description_format', 'description_values']);
        }

        // An upsert never deletes, so a spell this build leaves undescribed in this locale would keep
        // the description it holds - and ride into every environment through the seeder
        foreach (array_chunk(array_keys(array_diff_key($spellIds, $formatsAndValues)), self::PERSIST_CHUNK_SIZE) as $chunk) {
            SpellDescriptionTranslation::query()
                ->where('locale', $locale->value)
                ->whereIn('spell_id', $chunk)
                ->delete();
        }

        return count($rows);
    }
}
