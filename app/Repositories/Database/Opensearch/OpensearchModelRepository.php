<?php

namespace App\Repositories\Database\Opensearch;

use App\Models\Opensearch\OpensearchModel;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Opensearch\OpensearchModelRepositoryInterface;

class OpensearchModelRepository extends DatabaseRepository implements OpensearchModelRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(OpensearchModel::class);
    }
}
