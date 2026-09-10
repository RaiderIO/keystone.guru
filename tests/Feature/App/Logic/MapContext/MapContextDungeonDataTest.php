<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Support\Facades\Cache;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MapContext')]
final class MapContextDungeonDataTest extends PublicTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // RemembersToFile writes to the `tmp_file` file store, which survives between test runs -
        // without this the assertions below could run against a payload built by older code.
        Cache::store('tmp_file')->flush();
    }

    #[Test]
    public function toArray_givenDungeonNpcs_doesNotSerializeTheirTooltipData(): void
    {
        // Arrange - the map renders no NPC hover tooltips, and every NPC of the dungeon travels in
        // this payload, so an appended tooltip_data would be paid for on every map page (#4096)
        $dungeon = Dungeon::query()->whereHas('npcs')->firstOrFail();

        // Act - round-tripped through json, since that is what JavascriptController hands the client
        $dungeonNpcs = json_decode(json_encode(
            app(MapContextServiceInterface::class)->createMapContextDungeonData($dungeon, 'en_US')->toArray()['dungeonNpcs'],
        ), true);

        // Assert
        $this->assertNotEmpty($dungeonNpcs);

        foreach ($dungeonNpcs as $dungeonNpc) {
            $this->assertArrayNotHasKey('tooltip_data', $dungeonNpc);
        }
    }

    #[Test]
    public function toArray_givenFloorUnions_serializesTheirTargetFloorWithIngameBounds(): void
    {
        // Arrange - the front-end converts a facade location down to an ingame location, which needs
        // the target floor's ingame bounds; visibleFloors only carries the facade floor (#4509)
        $mappingVersion = MappingVersion::query()
            ->where('facade_enabled', true)
            ->whereHas('floorUnions')
            ->firstOrFail();

        // Act - round-tripped through json, since that is what JavascriptController hands the client
        $dungeonData = json_decode(json_encode(
            app(MapContextServiceInterface::class)->createMapContextMappingVersionData(
                $mappingVersion->dungeon,
                $mappingVersion,
                User::MAP_FACADE_STYLE_FACADE,
            )->toArray()['dungeon'],
        ), true);

        // Assert
        $this->assertNotEmpty($dungeonData['floorUnions']);

        foreach ($dungeonData['floorUnions'] as $floorUnion) {
            $this->assertArrayHasKey('target_floor', $floorUnion);
            $this->assertSame($floorUnion['target_floor_id'], $floorUnion['target_floor']['id']);

            foreach (['ingame_min_x', 'ingame_min_y', 'ingame_max_x', 'ingame_max_y'] as $ingameBound) {
                $this->assertNotNull($floorUnion['target_floor'][$ingameBound]);
            }
        }
    }
}
