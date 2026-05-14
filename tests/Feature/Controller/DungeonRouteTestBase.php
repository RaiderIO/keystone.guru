<?php

namespace Tests\Feature\Controller;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use Tests\TestCases\AjaxPublicTestCase;

abstract class DungeonRouteTestBase extends AjaxPublicTestCase
{
    protected DungeonRoute $dungeonRoute;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->make();

        $this->dungeonRoute = $dungeonRoute;
        $this->dungeonRoute->save();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->dungeonRoute->delete();

        parent::tearDown();
    }

    protected function createNonFacadeDungeonRoute(): DungeonRoute
    {
        $count = 0;
        do {
            if (++$count > 20) {
                throw new \RuntimeException('Unable to find a non-facade dungeon');
            }
            /** @var Dungeon $dungeon */
            $dungeon        = Dungeon::whereNotNull('challenge_mode_id')->inRandomOrder()->first();
            $mappingVersion = $dungeon->getCurrentMappingVersion();
        } while ($mappingVersion === null || $mappingVersion->facade_enabled || $dungeon->floors->isEmpty());

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
    }
}
