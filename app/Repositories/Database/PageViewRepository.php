<?php

namespace App\Repositories\Database;

use App\Models\PageView;
use App\Repositories\Interfaces\PageViewRepositoryInterface;

class PageViewRepository extends DatabaseRepository implements PageViewRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PageView::class);
    }
}
