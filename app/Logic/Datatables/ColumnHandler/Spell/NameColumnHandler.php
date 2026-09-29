<?php

namespace App\Logic\Datatables\ColumnHandler\Spell;

use App\Logic\Datatables\ColumnHandler\SimpleColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Override;

/**
 * The spell's name in the visitor's locale, falling back to English where that locale's translation is
 * empty and to the raw `spells.name` where there is no translation row at all.
 *
 * Expects the builder to have been given {@see self::joinNameTranslations()}.
 */
class NameColumnHandler extends SimpleColumnHandler
{
    public const string NAME_EXPRESSION = "COALESCE(NULLIF(spell_name_translations.translation, ''), NULLIF(spell_name_fallback_translations.translation, ''), spells.name)";

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'name', 'spell_name_translations.translation');
    }

    /**
     * @template TBuilder of Builder
     *
     * @param  TBuilder $builder
     * @return TBuilder
     */
    public static function joinNameTranslations(Builder $builder, string $locale, string $fallbackLocale): Builder
    {
        return $builder
            ->leftJoin('translations as spell_name_translations', static function (JoinClause $clause) use ($locale) {
                $clause->on('spell_name_translations.key', '=', 'spells.name')
                    ->where('spell_name_translations.locale', '=', $locale);
            })
            ->leftJoin('translations as spell_name_fallback_translations', static function (JoinClause $clause) use ($fallbackLocale) {
                $clause->on('spell_name_fallback_translations.key', '=', 'spells.name')
                    ->where('spell_name_fallback_translations.locale', '=', $fallbackLocale);
            });
    }

    /**
     * Matches the English name as well as the localized one, so a name looked up on an English site still
     * finds the spell.
     */
    #[Override]
    protected function applyFilter(
        Builder $subBuilder,
        Builder $orderBuilder,
                $columnData,
                $order,
                $generalSearch,
    ): void {
        $searchValue = sprintf('%%%s%%', $generalSearch);

        $subBuilder
            ->orWhereRaw(sprintf('%s LIKE ?', self::NAME_EXPRESSION), [$searchValue])
            ->orWhere('spell_name_fallback_translations.translation', 'LIKE', $searchValue);
    }
}
