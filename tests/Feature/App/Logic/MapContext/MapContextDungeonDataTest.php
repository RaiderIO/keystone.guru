<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcType;
use App\Models\Translation\Translation;
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
    private const int NPC_ID = 999999301;

    private const string NPC_NAME_KEY = 'npcs.999999301';

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

    #[Test]
    public function toArray_givenPayloadCachedInThePreviousShapeWithoutExpansionKey_returnsDungeonWithExpansionKey(): void
    {
        // Arrange
        $mappingVersion = MappingVersion::query()->whereHas('dungeon')->firstOrFail();
        $staleLocalKey  = sprintf(
            'local:dungeon_%d_%d_%s_v3',
            $mappingVersion->dungeon->id,
            $mappingVersion->id,
            User::MAP_FACADE_STYLE_FACADE,
        );
        Cache::store('tmp_file')->put($staleLocalKey, ['expansion' => ['shortname' => 'stale']], 3600);

        try {
            // Act
            $dungeonData = json_decode(json_encode(
                app(MapContextServiceInterface::class)->createMapContextMappingVersionData(
                    $mappingVersion->dungeon,
                    $mappingVersion,
                    User::MAP_FACADE_STYLE_FACADE,
                )->toArray()['dungeon'],
            ), true);

            // Assert
            $this->assertSame($mappingVersion->dungeon->expansion->key, $dungeonData['expansion']['key'] ?? null);
        } finally {
            Cache::store('tmp_file')->forget($staleLocalKey);
        }
    }

    #[Test]
    public function toArray_givenLocaleWithNpcNameTranslation_returnsLocalizedName(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->whereHas('npcs')->firstOrFail();

        try {
            $this->createNpcWithNameTranslations($dungeon, 'Test Kobold', 'Testkobold');

            // Act
            $name = $this->getDungeonNpcName($dungeon, 'de_DE_ai');

            // Assert
            $this->assertSame('Testkobold', $name);
        } finally {
            $this->deleteNpcWithNameTranslations();
        }
    }

    #[Test]
    public function toArray_givenLocaleWithEmptyNpcNameTranslation_returnsEnglishName(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->whereHas('npcs')->firstOrFail();

        try {
            $this->createNpcWithNameTranslations($dungeon, 'Test Kobold', '');

            // Act
            $name = $this->getDungeonNpcName($dungeon, 'de_DE_ai');

            // Assert
            $this->assertSame('Test Kobold', $name);
        } finally {
            $this->deleteNpcWithNameTranslations();
        }
    }

    #[Test]
    public function toArray_givenLocaleWithoutNpcNameTranslationRow_returnsEnglishName(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->whereHas('npcs')->firstOrFail();

        try {
            $this->createNpcWithNameTranslations($dungeon, 'Test Kobold', null);

            // Act
            $name = $this->getDungeonNpcName($dungeon, 'de_DE_ai');

            // Assert
            $this->assertSame('Test Kobold', $name);
        } finally {
            $this->deleteNpcWithNameTranslations();
        }
    }

    private function createNpcWithNameTranslations(Dungeon $dungeon, string $english, ?string $german): void
    {
        Npc::create([
            'id'                => self::NPC_ID,
            'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
            'npc_type_id'       => NpcType::HUMANOID,
            'name'              => self::NPC_NAME_KEY,
            'aggressiveness'    => 'aggressive',
        ]);
        NpcDungeon::create(['npc_id' => self::NPC_ID, 'dungeon_id' => $dungeon->id]);
        Translation::create(['locale' => 'en_US', 'key' => self::NPC_NAME_KEY, 'translation' => $english]);

        if ($german !== null) {
            Translation::create(['locale' => 'de_DE_ai', 'key' => self::NPC_NAME_KEY, 'translation' => $german]);
        }
    }

    private function deleteNpcWithNameTranslations(): void
    {
        Translation::query()->where('key', self::NPC_NAME_KEY)->delete();
        NpcDungeon::query()->where('npc_id', self::NPC_ID)->delete();
        Npc::query()->whereKey(self::NPC_ID)->delete();
    }

    private function getDungeonNpcName(Dungeon $dungeon, string $locale): ?string
    {
        /** @var array<int, array<string, mixed>> $dungeonNpcs */
        $dungeonNpcs = json_decode(json_encode(
            app(MapContextServiceInterface::class)->createMapContextDungeonData($dungeon, $locale)->toArray()['dungeonNpcs'],
        ), true);

        return collect($dungeonNpcs)->firstWhere('id', self::NPC_ID)['name'] ?? null;
    }
}
