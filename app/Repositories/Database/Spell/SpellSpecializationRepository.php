<?php

namespace App\Repositories\Database\Spell;

use App\Models\Spell\SpellSpecialization;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Spell\SpellSpecializationRepositoryInterface;

class SpellSpecializationRepository extends DatabaseRepository implements SpellSpecializationRepositoryInterface
{
    private const int CHUNK_SIZE = 500;

    public function __construct()
    {
        parent::__construct(SpellSpecialization::class);
    }

    public function replaceForSpells(array $specializationIdsBySpellId): int
    {
        $changedCount = 0;

        foreach (array_chunk($specializationIdsBySpellId, self::CHUNK_SIZE, true) as $chunk) {
            $current = [];

            foreach (SpellSpecialization::query()
                ->whereIn('spell_id', array_keys($chunk))
                ->get(['spell_id', 'character_class_specialization_id']) as $spellSpecialization) {
                $current[$spellSpecialization->spell_id][] = $spellSpecialization->character_class_specialization_id;
            }

            $rows = [];

            foreach ($chunk as $spellId => $specializationIds) {
                $wanted = array_values(array_unique($specializationIds));
                $had    = $current[$spellId] ?? [];
                sort($wanted);
                sort($had);

                if ($wanted === $had) {
                    continue;
                }

                $changedCount++;

                SpellSpecialization::query()->where('spell_id', $spellId)->delete();

                foreach ($wanted as $specializationId) {
                    $rows[] = [
                        'spell_id'                          => $spellId,
                        'character_class_specialization_id' => $specializationId,
                    ];
                }
            }

            if ($rows !== []) {
                SpellSpecialization::query()->insert($rows);
            }
        }

        return $changedCount;
    }
}
