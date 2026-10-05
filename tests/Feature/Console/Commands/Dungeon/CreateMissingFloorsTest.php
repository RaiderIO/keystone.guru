<?php

namespace Tests\Feature\Console\Commands\Dungeon;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('CreateMissing')]
final class CreateMissingFloorsTest extends TestCase
{
    private const string DUNGEON_KEY = 'create_missing_floors_test';

    #[Test]
    public function handle_givenFloorNamedAfterDungeon_createsFacadeFloor(): void
    {
        // Arrange
        $dungeon = Dungeon::create([
            'expansion_id'     => Expansion::ALL[Expansion::EXPANSION_CLASSIC],
            'active'           => 0,
            'speedrun_enabled' => false,
            'zone_id'          => 0,
            'map_id'           => 0,
            'mdt_id'           => 0,
            'name'             => 'dungeons.classic.kalimdor.name',
            'key'              => self::DUNGEON_KEY,
            'slug'             => self::DUNGEON_KEY,
        ]);

        try {
            // Act
            $this->artisan('dungeon:createmissingfloors')->assertSuccessful();

            // Assert
            $floors = Floor::query()->where('dungeon_id', $dungeon->id)->orderBy('index')->get();
            $this->assertCount(24, $floors);
            $this->assertSame(['dungeons.classic.kalimdor.floors.kalimdor'], $floors->where('facade', true)->pluck('name')->all());
            $this->assertSame(0, $floors->last()->ui_map_id);
            $this->assertTrue((bool)$floors->first()->default);
        } finally {
            Floor::query()->where('dungeon_id', $dungeon->id)->delete();
            Dungeon::query()->whereKey($dungeon->id)->delete();
        }
    }

    #[Test]
    public function handle_givenSingleFloorNamedAfterDungeon_createsItAsARegularFloor(): void
    {
        // Arrange
        $dungeon = null;

        try {
            $dungeon = $this->createDungeonWithoutFloors('dungeons.classic.ragefire_chasm.name');

            // Act
            $this->artisan('dungeon:createmissingfloors')->assertSuccessful();

            // Assert
            $floors = Floor::query()->where('dungeon_id', $dungeon->id)->get();
            $this->assertCount(1, $floors);
            $this->assertSame('dungeons.classic.ragefire_chasm.floors.ragefire_chasm', $floors->first()->name);
            $this->assertFalse((bool)$floors->first()->facade);
            $this->assertTrue((bool)$floors->first()->default);
        } finally {
            $this->deleteDungeonAndFloors($dungeon);
        }
    }

    #[Test]
    public function handle_givenDungeonThatAlreadyHasAFloor_addsNoFloors(): void
    {
        // Arrange
        $dungeon = null;

        try {
            $dungeon       = $this->createDungeonWithoutFloors('dungeons.classic.ragefire_chasm.name');
            $existingFloor = Floor::create([
                'dungeon_id' => $dungeon->id,
                'name'       => 'dungeons.classic.ragefire_chasm.floors.ragefire_chasm',
                'index'      => 1,
                'default'    => true,
            ]);

            // Act
            $this->artisan('dungeon:createmissingfloors')->assertSuccessful();

            // Assert
            $this->assertSame(
                [$existingFloor->id],
                Floor::query()->where('dungeon_id', $dungeon->id)->pluck('id')->all(),
            );
        } finally {
            $this->deleteDungeonAndFloors($dungeon);
        }
    }

    #[Test]
    public function handle_givenDungeonWithoutFloorTranslations_throwsNamingTheDungeon(): void
    {
        // Arrange
        $dungeon = null;

        try {
            $dungeon = $this->createDungeonWithoutFloors(sprintf('dungeons.classic.%s.name', self::DUNGEON_KEY));

            // Act
            $exception = null;

            try {
                $this->artisan('dungeon:createmissingfloors')->run();
            } catch (Exception $caught) {
                $exception = $caught;
            }

            // Assert
            $this->assertNotNull($exception);
            $this->assertSame(
                sprintf('Translated floors should be an array for dungeons.classic.%s.name', self::DUNGEON_KEY),
                $exception->getMessage(),
            );
            $this->assertFalse(Floor::query()->where('dungeon_id', $dungeon->id)->exists());
        } finally {
            $this->deleteDungeonAndFloors($dungeon);
        }
    }

    private function createDungeonWithoutFloors(string $name): Dungeon
    {
        return Dungeon::create([
            'expansion_id'     => Expansion::ALL[Expansion::EXPANSION_CLASSIC],
            'active'           => 0,
            'speedrun_enabled' => false,
            'zone_id'          => 0,
            'map_id'           => 0,
            'mdt_id'           => 0,
            'name'             => $name,
            'key'              => self::DUNGEON_KEY,
            'slug'             => self::DUNGEON_KEY,
        ]);
    }

    private function deleteDungeonAndFloors(?Dungeon $dungeon): void
    {
        if ($dungeon === null) {
            return;
        }

        Floor::query()->where('dungeon_id', $dungeon->id)->delete();
        Dungeon::query()->whereKey($dungeon->id)->delete();
    }
}
