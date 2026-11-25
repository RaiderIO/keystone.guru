<?php

namespace Tests\Feature\App\Repository;

use App\Models\CharacterClassSpecialization;
use App\Models\Spell\SpellSpecialization;
use App\Repositories\Interfaces\Spell\SpellSpecializationRepositoryInterface;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSpell;
use Tests\TestCases\PublicTestCase;

#[Group('SpellCooldown')]
final class SpellSpecializationRepositoryTest extends PublicTestCase
{
    use CreatesSpell;

    #[Test]
    public function replaceForSpells_givenNewSpecializations_replacesTheOldOnes(): void
    {
        // Arrange
        $repository        = app(SpellSpecializationRepositoryInterface::class);
        $specializationIds = $this->getSpecializationIds();
        $spell             = $this->createSpell();

        try {
            SpellSpecialization::create(['spell_id' => $spell->id, 'character_class_specialization_id' => $specializationIds[0]]);

            // Act
            $changedCount = $repository->replaceForSpells([$spell->id => [$specializationIds[1]]]);

            // Assert
            $this->assertSame(1, $changedCount);
            $this->assertSame([$specializationIds[1]], $this->getStoredSpecializationIds($spell->id));
        } finally {
            SpellSpecialization::query()->where('spell_id', $spell->id)->delete();
        }
    }

    #[Test]
    public function replaceForSpells_givenAnInsertThatFails_keepsTheOldSpecializations(): void
    {
        // Arrange - an id beyond the column's range makes the insert fail after the delete has run
        $repository        = app(SpellSpecializationRepositoryInterface::class);
        $specializationIds = $this->getSpecializationIds();
        $spell             = $this->createSpell();

        try {
            SpellSpecialization::create(['spell_id' => $spell->id, 'character_class_specialization_id' => $specializationIds[0]]);

            // Act
            $thrown = null;

            try {
                $repository->replaceForSpells([$spell->id => [PHP_INT_MAX]]);
            } catch (QueryException $exception) {
                $thrown = $exception;
            }

            // Assert
            $this->assertNotNull($thrown, 'The insert did not fail - this test no longer guards anything');
            $this->assertSame([$specializationIds[0]], $this->getStoredSpecializationIds($spell->id));
        } finally {
            SpellSpecialization::query()->where('spell_id', $spell->id)->delete();
        }
    }

    /**
     * @return list<int>
     */
    private function getSpecializationIds(): array
    {
        return CharacterClassSpecialization::query()->orderBy('id')->limit(2)->pluck('id')->map(static fn($id) => (int)$id)->all();
    }

    /**
     * @return list<int>
     */
    private function getStoredSpecializationIds(int $spellId): array
    {
        return SpellSpecialization::query()
            ->where('spell_id', $spellId)
            ->orderBy('character_class_specialization_id')
            ->pluck('character_class_specialization_id')
            ->map(static fn($id) => (int)$id)
            ->all();
    }
}
