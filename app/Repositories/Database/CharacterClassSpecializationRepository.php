<?php

namespace App\Repositories\Database;

use App\Models\CharacterClassSpecialization;
use App\Repositories\Interfaces\CharacterClassSpecializationRepositoryInterface;

class CharacterClassSpecializationRepository extends DatabaseRepository implements CharacterClassSpecializationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CharacterClassSpecialization::class);
    }

    public function getCharacterClassIdById(int $characterClassSpecializationId): ?int
    {
        $characterClassId = CharacterClassSpecialization::query()->whereKey($characterClassSpecializationId)->value('character_class_id');

        return $characterClassId === null ? null : (int)$characterClassId;
    }
}
