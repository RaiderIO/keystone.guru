<?php

namespace App\Logic\Datatables\ColumnHandler\Compendium;

use App\Logic\Datatables\ColumnHandler\SimpleColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use Illuminate\Database\Eloquent\Builder;
use Override;

class DungeonColumnHandler extends SimpleColumnHandler
{
    /**
     * @param string $searchExpression the SQL expression the displayed dungeon name is built from, so a search
     *                                 matches what the visitor reads
     */
    public function __construct(
        DatatablesHandler       $dtHandler,
        private readonly string $searchExpression = 'dungeon_translations.translation',
    ) {
        parent::__construct($dtHandler, 'dungeon_id', 'dungeon_names');
    }

    #[Override]
    protected function applyFilter(
        Builder $subBuilder,
        Builder $orderBuilder,
                $columnData,
                $order,
                $generalSearch,
    ): void {
        $subBuilder->orWhereRaw(sprintf('%s LIKE ?', $this->searchExpression), [sprintf('%%%s%%', $generalSearch)]);
    }
}
