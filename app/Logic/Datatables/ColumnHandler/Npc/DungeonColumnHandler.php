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
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class DungeonColumnHandler extends SimpleColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'dungeon_names', 'dungeons.name');
    }

    protected function applyFilter(Builder $subBuilder, Builder $orderBuilder,  $columnData, $order, $generalSearch): void
    {
        // Only order
//        $this->getDtHandler()->getBuilder()->leftJoin('translations', static function (JoinClause $clause) {
//            $clause->on('translations.key', 'dungeons.name')
//                ->on('translations.locale', DB::raw('"en_US"'));
//        });

        $subBuilder->orWhere('translations.translation', 'LIKE', sprintf('%%%s%%', $generalSearch));
    }
}
