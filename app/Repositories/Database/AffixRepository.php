<?php

namespace App\Repositories\Database;

use App\Models\Affix;
use App\Repositories\Interfaces\AffixRepositoryInterface;
use Illuminate\Support\Collection;

class AffixRepository extends DatabaseRepository implements AffixRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Affix::class);
    }

    /**
     * @return Collection<int, Affix>
     */
    public function getAllOrderedById(): Collection
    {
        return Affix::query()->orderBy('id')->get();
    }

    /**
     * @param  Collection<int, int>    $affixIds
     * @return Collection<int, string>
     */
    public function getKeysByIds(Collection $affixIds): Collection
    {
        return Affix::query()->whereIn('id', $affixIds)->pluck('key', 'id');
    }
}
