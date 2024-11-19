<?php

namespace App\Logic\Datatables\ColumnHandler\Users;

use App\Logic\Datatables\ColumnHandler\SimpleColumnHandler;
use App\Logic\Datatables\DatatablesHandler;

class IdColumnHandler extends SimpleColumnHandler
{
    public function __construct(DatatablesHandler $dtHandler)
    {
        parent::__construct($dtHandler, 'id', 'users.id');
    }
}
