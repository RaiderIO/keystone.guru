<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Models\Dungeon;
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
}
