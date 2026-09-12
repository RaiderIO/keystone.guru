<?php

namespace Tests\Fixtures\Traits;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Database\Factories\FloorFactory;
use Tests\TestCase;

/**
 * A dungeon of the test's own, for a test that needs one in a state the seed does not promise: inactive, without
 * seasons, without enemies on its mapping version, on a given expansion. The seed does promise active dungeons with
 * enemies - use {@see \Tests\Feature\Traits\ProvidesDungeon::findDungeon()} for those.
 *
 * Everything created here is deleted again when the test's application is torn down.
 *
 * @mixin TestCase
 */
trait CreatesDungeon
{
    /** @var array<int, Dungeon> */
    private array $createdDungeons = [];

    /**
     * @param array<string, mixed> $attributes         Any `dungeons` column. Defaults to an inactive Mythic+ dungeon on the first active expansion.
     * @param bool                 $withMappingVersion Give it a current (empty) mapping version on the default game version.
     * @param bool                 $withDefaultFloor   Give it one fully configured default floor, which every map page needs.
     */
    protected function createDungeon(array $attributes = [], bool $withMappingVersion = true, bool $withDefaultFloor = true): Dungeon
    {
        if ($this->createdDungeons === []) {
            $this->beforeApplicationDestroyed(fn() => $this->deleteCreatedDungeons());
        }

        $key = sprintf('test_dungeon_%s', uniqid());
        // Combat logs resolve a dungeon by map id, so it must not collide with a seeded one
        $mapId = max(9_000_000, (int)Dungeon::query()->disableCache()->max('map_id') + 1);

        $dungeon = Dungeon::create(array_merge([
            'expansion_id'      => Expansion::query()->where('active', 1)->firstOrFail()->id,
            'active'            => false,
            'zone_id'           => $mapId,
            'map_id'            => $mapId,
            'challenge_mode_id' => $mapId,
            'name'              => 'Test Dungeon',
            'key'               => $key,
            'slug'              => $key,
        ], $attributes));

        $this->createdDungeons[] = $dungeon;

        if ($withDefaultFloor) {
            FloorFactory::new()->create([
                'dungeon_id' => $dungeon->id,
                'index'      => 1,
                'name'       => 'Test Floor',
                'default'    => true,
            ]);
        }

        if ($withMappingVersion) {
            MappingVersion::create([
                'game_version_id'                 => GameVersion::getDefaultGameVersion()->id,
                'dungeon_id'                      => $dungeon->id,
                'version'                         => 1,
                'enemy_forces_required'           => 100,
                'enemy_forces_required_teeming'   => null,
                'enemy_forces_shrouded'           => 0,
                'enemy_forces_shrouded_zul_gamux' => 0,
                'timer_max_seconds'               => 1800,
                'facade_enabled'                  => false,
                'mdt_mapping_hash'                => null,
                'mdt_changes_pending'             => false,
            ]);
        }

        return $dungeon;
    }

    /**
     * Runs on teardown by itself; call it directly only when the dungeon must be gone before the test ends.
     */
    protected function deleteCreatedDungeons(): void
    {
        foreach ($this->createdDungeons as $dungeon) {
            // Through the models on purpose: MappingVersion::deleting removes whatever the test hung off the version
            foreach ($dungeon->mappingVersions()->get() as $mappingVersion) {
                $mappingVersion->delete();
            }
            foreach ($dungeon->floors()->get() as $floor) {
                $floor->delete();
            }

            // Dungeon::boot() refuses `deleting`, so only the query builder can remove the row - and nothing flushes
            // the model cache for a query builder delete
            Dungeon::query()->whereKey($dungeon->id)->delete();
        }

        $this->createdDungeons = [];

        new Dungeon()->flushCache();
        new Floor()->flushCache();
    }
}
