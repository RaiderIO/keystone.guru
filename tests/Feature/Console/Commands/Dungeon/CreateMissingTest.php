<?php

namespace Tests\Feature\Console\Commands\Dungeon;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Expansion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Takes the seeded Kalimdor row out of the database so the command has one missing dungeon to add, and puts it back.
 */
#[Group('CreateMissing')]
final class CreateMissingTest extends TestCase
{
    #[Test]
    public function handle_givenOtherExpansion_createsNothing(): void
    {
        // Arrange
        $original = $this->removeKalimdor();

        try {
            // Act
            $this->artisan('dungeon:createmissing', ['expansion' => Expansion::EXPANSION_MOP])->assertSuccessful();

            // Assert
            $this->assertFalse(Dungeon::query()->where('key', DungeonKey::KALIMDOR->value)->exists());
        } finally {
            $this->restoreKalimdor($original);
        }
    }

    #[Test]
    public function handle_givenDungeonExpansion_createsMissingDungeon(): void
    {
        // Arrange
        $original = $this->removeKalimdor();

        try {
            // Act
            $this->artisan('dungeon:createmissing', ['expansion' => Expansion::EXPANSION_CLASSIC])->assertSuccessful();

            // Assert
            $created = Dungeon::query()->where('key', DungeonKey::KALIMDOR->value)->first();
            $this->assertNotNull($created);
            $this->assertSame(Expansion::ALL[Expansion::EXPANSION_CLASSIC], $created->expansion_id);
            $this->assertSame('dungeons.classic.kalimdor.name', $created->name);
            $this->assertFalse((bool)$created->active);
        } finally {
            $this->restoreKalimdor($original);
        }
    }

    #[Test]
    public function handle_givenUnknownExpansion_returnsFailure(): void
    {
        // Arrange
        $dungeonCount = Dungeon::query()->count();

        // Act
        $result = $this->artisan('dungeon:createmissing', ['expansion' => 'not_an_expansion']);

        // Assert
        $result->assertFailed();
        $this->assertSame($dungeonCount, Dungeon::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function removeKalimdor(): array
    {
        $original = Dungeon::query()->where('key', DungeonKey::KALIMDOR->value)->firstOrFail()->getAttributes();

        // Dungeon::deleting() vetoes model deletes, so go through the query builder
        Dungeon::query()->whereKey($original['id'])->delete();

        return $original;
    }

    /**
     * @param array<string, mixed> $original
     */
    private function restoreKalimdor(array $original): void
    {
        Dungeon::query()->where('key', DungeonKey::KALIMDOR->value)->delete();
        Dungeon::query()->insert($original);
    }
}
