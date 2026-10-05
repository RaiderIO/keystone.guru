<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables\ColumnHandler\Npc;

use App\Logic\Datatables\ColumnHandler\SimpleColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Override;

/**
 * The NPC's name in the visitor's locale, falling back to English where that locale's translation is
 * empty and to the raw `npcs.name` where there is no translation row at all.
 *
 * Expects the builder to have been given {@see self::joinNameTranslations()}.
 */
class NameColumnHandler extends SimpleColumnHandler
{
    public const string NAME_EXPRESSION = "COALESCE(NULLIF(npc_name_translations.translation, ''), NULLIF(npc_name_fallback_translations.translation, ''), npcs.name)";

    /**
     * NAME_EXPRESSION for a query grouped by npcs.id: translations has no unique (locale, key), so MySQL cannot tell
     * that the joined translations are functionally dependent on the grouped id.
     */
    public const string GROUPED_NAME_EXPRESSION = 'ANY_VALUE(' . self::NAME_EXPRESSION . ')';

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'name', 'npc_name_translations.translation');
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
            ->leftJoin('translations as npc_name_translations', static function (JoinClause $clause) use ($locale) {
                $clause->on('npc_name_translations.key', '=', 'npcs.name')
                    ->where('npc_name_translations.locale', '=', $locale);
            })
            ->leftJoin('translations as npc_name_fallback_translations', static function (JoinClause $clause) use ($fallbackLocale) {
                $clause->on('npc_name_fallback_translations.key', '=', 'npcs.name')
                    ->where('npc_name_fallback_translations.locale', '=', $fallbackLocale);
            });
    }

    /**
     * Matches the English name as well as the localized one, so a name looked up on an English site still
     * finds the NPC.
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
            ->orWhere('npc_name_fallback_translations.translation', 'LIKE', $searchValue);
    }
}
