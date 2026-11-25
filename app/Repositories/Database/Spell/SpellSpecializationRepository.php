<?php

namespace App\Repositories\Database\Spell;

use App\Models\Spell\SpellSpecialization;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Spell\SpellSpecializationRepositoryInterface;
use Illuminate\Support\Facades\DB;

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

            $changedSpellIds = [];
            $rows            = [];

            foreach ($chunk as $spellId => $specializationIds) {
                $wanted = array_values(array_unique($specializationIds));
                $had    = $current[$spellId] ?? [];
                sort($wanted);
                sort($had);

                if ($wanted === $had) {
                    continue;
                }

                $changedSpellIds[] = $spellId;

                foreach ($wanted as $specializationId) {
                    $rows[] = [
                        'spell_id'                          => $spellId,
                        'character_class_specialization_id' => $specializationId,
                    ];
                }
            }

            if ($changedSpellIds === []) {
                continue;
            }

            // A delete without its insert would leave the spells looking open to every specialization
            DB::transaction(static function () use ($changedSpellIds, $rows): void {
                SpellSpecialization::query()->whereIn('spell_id', $changedSpellIds)->delete();

                if ($rows !== []) {
                    SpellSpecialization::query()->insert($rows);
                }
            });

            $changedCount += count($changedSpellIds);
        }

        return $changedCount;
    }
}
