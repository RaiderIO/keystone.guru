<?php

namespace App\Repositories\Database;

use App\Models\CharacterRace;
use App\Repositories\Interfaces\CharacterRaceRepositoryInterface;
use Illuminate\Support\Collection;

class CharacterRaceRepository extends DatabaseRepository implements CharacterRaceRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CharacterRace::class);
    }

    /**
     * @return Collection<int, CharacterRace>
     */
    public function getAllWithFactionOrderedById(): Collection
    {
        return CharacterRace::query()->with('faction')->orderBy('id')->get();
    }
}
