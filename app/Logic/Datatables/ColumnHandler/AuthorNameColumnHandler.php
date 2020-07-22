<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables\ColumnHandler;

use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;

class AuthorNameColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'author.name');
    }

    protected function _applyFilter(Builder $builder, $columnData, $order)
    {
        // Only order
        if ($order !== null) {
            $builder->leftJoin('users', 'dungeon_routes.author_id', '=', 'users.id');
            $builder->orderBy('users.name', $order['dir'] === 'asc' ? 'asc' : 'desc');
        }
    }
}