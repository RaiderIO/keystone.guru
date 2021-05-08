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
use App\Models\DungeonRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ViewsColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'views');
    }

    protected function _applyFilter(Builder $builder, $columnData, $order, $generalSearch)
    {
        $views = $columnData['search']['value'];
        if (!empty($views)) {
//            $builder->whereHas('affixes', function ($query) use (&$affixIds) {
//                /** @var $query Builder */
//                $query->whereIn('affix_groups.id', $affixIds);
//            });
        }

        // Only order
        if ($order !== null) {
            $builder->orderBy('views', $order['dir'] === 'asc' ? 'asc' : 'desc');
        }
    }
}