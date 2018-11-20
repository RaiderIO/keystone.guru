<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic;

use Illuminate\Database\Eloquent\Builder;

class DungeonRouteAffixesColumnHandler extends DatatablesColumnHandler
{

    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'affixes.id');
    }

    protected function _applyFilter(Builder $builder, $columnData, $order)
    {
        $affixIds = explode(',', $affixes);

        $builder->whereHas('affixes', function ($query) use (&$affixIds) {
            /** @var $query Builder */
            $query->whereIn('affix_groups.id', $affixIds);
        });
    }
}