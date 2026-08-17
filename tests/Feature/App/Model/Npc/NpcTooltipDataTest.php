<?php

namespace Tests\Feature\App\Model\Npc;

use App\Models\Characteristic;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcHealth;
use App\Models\Npc\NpcType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The payload behind the NPC hover tooltips (#4096).
 *
 * Every NPC here is built in memory with its relations set by hand - nothing is written to the
 * database, so there is nothing to clean up either.
 */
#[Group('Compendium')]
final class NpcTooltipDataTest extends PublicTestCase
{
    #[Test]
    public function tooltipData_givenNpcWithEverything_returnsEveryRow(): void
    {
        // Arrange
        $npc = $this->makeNpc([
            'name'      => 'Forgemaster Garfrost',
            'level'     => 82,
            'dangerous' => true,
        ], NpcClassification::NPC_CLASSIFICATION_ELITE);

        $this->setHealth($npc, 1_234_567);
        $this->setCharacteristics($npc, ['Stun', 'Slow']);

        // Act
        $result = $npc->tooltip_data;

        // Assert
        $this->assertSame(__('Forgemaster Garfrost'), $result['name']);
        $this->assertSame(ksgAsset($npc->enemy_portrait_url), $result['portraitUrl']);
        $this->assertSame(__(sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_ELITE)), $result['classification']);
        $this->assertSame(NpcClassification::NPC_CLASSIFICATION_ELITE, $result['classificationKey']);
        $this->assertSame(82, $result['level']);
        $this->assertSame(1_234_567, $result['health']);
        $this->assertSame('Humanoid', $result['type']);
        $this->assertSame(__('npcaggressiveness.aggressive'), $result['aggressiveness']);
        $this->assertSame([__('view_admin.npc.edit.dangerous')], $result['flags']);
        $this->assertSame(['Stun', 'Slow'], array_column($result['characteristics'], 'name'));
    }

    #[Test]
    public function tooltipData_givenNpcWithoutCharacteristics_omitsCharacteristics(): void
    {
        // Arrange - an NPC no crowd control was ever observed on. Absence is "not observed" rather
        // than "immune" (#4028), so the tooltip says nothing at all instead of an empty block.
        $npc = $this->makeNpc();

        $this->setHealth($npc, 500_000);
        $this->setCharacteristics($npc, []);

        // Act
        $result = $npc->tooltip_data;

        // Assert
        $this->assertArrayNotHasKey('characteristics', $result);
    }

    #[Test]
    public function tooltipData_givenPlaceholderHealth_omitsHealth(): void
    {
        // Arrange - a fair few dungeons still carry the placeholder rather than a real health (#4094)
        $npc = $this->makeNpc();

        $this->setHealth($npc, NpcHealth::HEALTH_PLACEHOLDER);
        $this->setCharacteristics($npc, []);

        // Act
        $result = $npc->tooltip_data;

        // Assert
        $this->assertArrayNotHasKey('health', $result);
    }

    #[Test]
    public function tooltipData_givenNpcWithoutHealthForTheGameVersion_omitsHealth(): void
    {
        // Arrange
        $npc = $this->makeNpc();

        $npc->setRelation('npcHealths', new EloquentCollection());
        $this->setCharacteristics($npc, []);

        // Act
        $result = $npc->tooltip_data;

        // Assert
        $this->assertArrayNotHasKey('health', $result);
    }

    #[Test]
    public function tooltipData_givenNpcWithoutAffixBehaviour_omitsFlags(): void
    {
        // Arrange
        $npc = $this->makeNpc(['dangerous' => false, 'truesight' => false, 'runs_away_in_fear' => false]);

        $this->setHealth($npc, 500_000);
        $this->setCharacteristics($npc, []);

        // Act
        $result = $npc->tooltip_data;

        // Assert
        $this->assertArrayNotHasKey('flags', $result);
    }

    #[Test]
    public function tooltipData_givenBoss_returnsTheKeyTheBadgeColourIsPickedBy(): void
    {
        // Arrange
        $npc = $this->makeNpc([], NpcClassification::NPC_CLASSIFICATION_BOSS);

        $this->setHealth($npc, 500_000);
        $this->setCharacteristics($npc, []);

        // Act
        $result = $npc->tooltip_data;

        // Assert - the JS colours the badge off this key, exactly as the NPC's own page does
        $this->assertSame(NpcClassification::NPC_CLASSIFICATION_BOSS, $result['classificationKey']);
    }

    #[Test]
    public function tooltipData_givenNpcWithoutAClassificationRow_omitsTheBadge(): void
    {
        // Arrange - a handful of seeded NPCs carry a classification_id that matches no row at all
        $npc = $this->makeNpc();

        $npc->setRelation('classification', null);
        $this->setHealth($npc, 500_000);
        $this->setCharacteristics($npc, []);

        // Act
        $result = $npc->tooltip_data;

        // Assert
        $this->assertArrayNotHasKey('classification', $result);
        $this->assertArrayNotHasKey('classificationKey', $result);
    }

    #[Test]
    public function toArray_givenNpc_doesNotIncludeTooltipData(): void
    {
        // Arrange - NPCs are serialized in bulk into the map context, which renders no tooltips at
        // all; appending this would put the whole payload in every map's context for nothing.
        $npc = Npc::query()->firstOrFail();

        // Act
        $result = $npc->toArray();

        // Assert
        $this->assertArrayNotHasKey('tooltip_data', $result);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeNpc(array $attributes = [], string $classificationKey = NpcClassification::NPC_CLASSIFICATION_ELITE): Npc
    {
        $npc = new Npc(array_merge([
            'id'             => 123123,
            'name'           => 'npc.test.npc',
            'level'          => 80,
            'aggressiveness' => Npc::AGGRESSIVENESS_AGGRESSIVE,
        ], $attributes));

        $npc->setRelation('classification', new NpcClassification([
            'id'   => NpcClassification::ALL[$classificationKey],
            'key'  => $classificationKey,
            'name' => sprintf('npcclassifications.%s', $classificationKey),
        ]));
        $npc->classification_id = NpcClassification::ALL[$classificationKey];

        $npc->setRelation('type', new NpcType(['id' => 1, 'type' => 'Humanoid']));

        return $npc;
    }

    private function setHealth(Npc $npc, int $health): void
    {
        $npc->setRelation('npcHealths', new EloquentCollection([
            new NpcHealth([
                'npc_id'          => $npc->id,
                'game_version_id' => GameVersion::getUserOrDefaultGameVersion()->id,
                'health'          => $health,
            ]),
        ]));
    }

    /**
     * @param array<int, string> $names
     */
    private function setCharacteristics(Npc $npc, array $names): void
    {
        $npc->setRelation('characteristics', new EloquentCollection(
            array_map(static fn(string $name): Characteristic => new Characteristic([
                'name'      => $name,
                'icon_name' => strtolower($name),
            ]), $names),
        ));
    }
}
