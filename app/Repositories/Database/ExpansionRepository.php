<?php

namespace App\Repositories\Database;

use App\Models\Expansion;
use App\Repositories\Interfaces\ExpansionRepositoryInterface;

class ExpansionRepository extends DatabaseRepository implements ExpansionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Expansion::class);
    }

    public function getByKeyOrFail(string $key): Expansion
    {
        return Expansion::where('key', $key)->firstOrFail();
    }
}
