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

class DungeonColumnHandler extends SimpleColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'dungeon_id', 'dungeons.name');
    }
}