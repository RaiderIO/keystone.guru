<?php

namespace App\Logic\Datatables\ColumnHandler\Compendium;

use App\Logic\Datatables\ColumnHandler\SimpleColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;

class DungeonColumnHandler extends SimpleColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'dungeon_id', 'dungeon_names');
    }

    #[\Override]
    protected function applyFilter(
        Builder $subBuilder,
        Builder $orderBuilder,
                $columnData,
                $order,
                $generalSearch,
    ): void {
        $subBuilder->orWhere('dungeon_translations.translation', 'LIKE', sprintf('%%%s%%', $generalSearch));
    }
}
