<?php

namespace App\Logic\Datatables\ColumnHandler\Spell;

use App\Logic\Datatables\ColumnHandler\SimpleColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class NameColumnHandler extends SimpleColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'name', 'spell_name_translations.translation');
    }

    #[\Override]
    protected function applyFilter(
        Builder $subBuilder,
        Builder $orderBuilder,
                $columnData,
                $order,
                $generalSearch,
    ): void {
        $subBuilder
            ->leftJoin('translations as spell_name_translations', function (JoinClause $clause) {
                $clause->on('spell_name_translations.key', '=', 'spells.name')
                    ->where('spell_name_translations.locale', '=', 'en_US');
            })->orWhere('spell_name_translations.translation', 'LIKE', sprintf('%%%s%%', $generalSearch));
    }
}
