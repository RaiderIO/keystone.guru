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

class NameColumnHandler extends SimpleColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'name', 'npc_name_translations.translation');
    }

    protected function applyFilter(
        Builder $subBuilder,
        Builder $orderBuilder,
                $columnData,
                $order,
                $generalSearch,
    ): void {
        // Only order
        $subBuilder
            ->leftJoin('translations as npc_name_translations', function (JoinClause $clause) {
                $clause->on('npc_name_translations.key', '=', 'npcs.name')
                    ->where('npc_name_translations.locale', '=', 'en_US');
            })->orWhere('npc_name_translations.translation', 'LIKE', sprintf('%%%s%%', $generalSearch));
    }
}
