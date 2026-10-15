<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\Spell\SpellDungeon;
use App\Repositories\Database\SpellRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSpell;
use Tests\TestCases\PublicTestCase;

#[Group('SpellRepository')]
final class SpellRepositoryGameVersionTest extends PublicTestCase
{
    use CreatesSpell;

    #[Test]
    public function getAllWithGameVersionKeyedById_givenDungeon_returnsOnlySpellsSeenInThatDungeon(): void
    {
        // Arrange
        [$dungeon, $otherDungeon] = Dungeon::query()->orderBy('id')->limit(2)->get()->all();
        $inDungeon                = $this->createSpell();
        $inOtherDungeon           = $this->createSpell();
        SpellDungeon::create(['spell_id' => $inDungeon->id, 'dungeon_id' => $dungeon->id]);
        SpellDungeon::create(['spell_id' => $inOtherDungeon->id, 'dungeon_id' => $otherDungeon->id]);

        try {
            // Act
            $result = new SpellRepository()->getAllWithGameVersionKeyedById($dungeon);

            // Assert
            $this->assertTrue($result->has($inDungeon->id));
            $this->assertFalse($result->has($inOtherDungeon->id));
            $this->assertTrue($result->get($inDungeon->id)->relationLoaded('gameVersion'));
        } finally {
            SpellDungeon::query()->whereIn('spell_id', [$inDungeon->id, $inOtherDungeon->id])->delete();
        }
    }

    #[Test]
    public function getAllWithGameVersionKeyedById_givenNoDungeon_returnsSpellsOfEveryDungeon(): void
    {
        // Arrange
        $spell = $this->createSpell();

        // Act
        $result = new SpellRepository()->getAllWithGameVersionKeyedById();

        // Assert
        $this->assertTrue($result->has($spell->id));
    }
}
