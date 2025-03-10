<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables\ColumnHandler\DungeonRoutes;

use App\Logic\Datatables\ColumnHandler\DatatablesColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;

class EnemyForcesColumnHandler extends DatatablesColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'enemy_forces');
    }

    protected function applyFilter(Builder $subBuilder, Builder $orderBuilder,  $columnData, $order, $generalSearch): void
    {
        $views = $columnData['search']['value'];
        //        if (!empty($views)) {
        //            $builder->whereHas('affixes', function ($query) use (&$affixIds) {
        //                /** @var $query Builder */
        //                $query->whereIn('affix_groups.id', $affixIds);
        //            });
        //        }

        // Only order
        if ($order !== null) {
            $orderBuilder->orderBy('enemy_forces', $order['dir'] === 'asc' ? 'asc' : 'desc');
        }
    }
}
