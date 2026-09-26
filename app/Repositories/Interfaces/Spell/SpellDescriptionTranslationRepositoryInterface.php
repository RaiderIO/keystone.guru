<?php

namespace App\Repositories\Interfaces\Spell;

use App\Models\Spell\SpellDescriptionTranslation;
use App\Repositories\BaseRepositoryInterface;
use App\Service\WagoTools\GameLocale;
use Illuminate\Support\Collection;

/**
 * @method SpellDescriptionTranslation                  create(array<string, mixed> $attributes)
 * @method SpellDescriptionTranslation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellDescriptionTranslation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellDescriptionTranslation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                         save(SpellDescriptionTranslation $model)
 * @method bool                                         update(SpellDescriptionTranslation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                         delete(SpellDescriptionTranslation $model)
 * @method Collection<int, SpellDescriptionTranslation> all()
 * @method bool                                         exists(array<int, string> $columns)
 */
interface SpellDescriptionTranslationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Replace the descriptions of one locale for the given spells: every spell in `$formatsAndValues`
     * gets the row it names, and any spell in `$spellIds` this locale does not describe loses the row
     * it holds.
     *
     * @param  array<int, bool>                                                            $spellIds         the spells the import considered, as a set
     * @param  array<int, array{format: string, values: array<int, array<string, mixed>>}> $formatsAndValues keyed by spell id
     * @return int                                                                         the number of rows that were written
     */
    public function replaceForLocale(GameLocale $locale, array $spellIds, array $formatsAndValues): int;
}
