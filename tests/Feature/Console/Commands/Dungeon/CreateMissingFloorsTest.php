<?php

namespace Tests\Feature\Console\Commands\Dungeon;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
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
}
