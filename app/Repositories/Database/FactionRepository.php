<?php

namespace App\Repositories\Database;

use App\Models\Faction;
use App\Repositories\Interfaces\FactionRepositoryInterface;
use Illuminate\Support\Collection;

class FactionRepository extends DatabaseRepository implements FactionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Faction::class);
    }

    /**
     * @return Collection<int, Faction>
     */
    public function getAllOrderedById(): Collection
    {
        return Faction::query()->orderBy('id')->get();
    }
}
