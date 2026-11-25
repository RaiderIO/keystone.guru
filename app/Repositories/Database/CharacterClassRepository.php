<?php

namespace App\Repositories\Database;

use App\Models\CharacterClass;
use App\Repositories\Interfaces\CharacterClassRepositoryInterface;
use Illuminate\Support\Collection;

class CharacterClassRepository extends DatabaseRepository implements CharacterClassRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CharacterClass::class);
    }

    public function getAllOrderedByName(): Collection
    {
        return CharacterClass::orderBy('name')->get();
    }

    /**
     * @return Collection<int, CharacterClass>
     */
    public function getAllWithSpecializationsOrderedById(): Collection
    {
        return CharacterClass::query()->with('specializations')->orderBy('id')->get();
    }
}
