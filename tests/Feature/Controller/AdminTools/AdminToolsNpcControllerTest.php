<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\Dungeon;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcType;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsNpcControllerTest extends PublicTestCase
{
    use CreatesNpc;

    private const int ADMIN_USER_ID     = 1;
    private const int NON_ADMIN_USER_ID = 3;

    // Comfortably above any real Wowhead NPC id, to avoid colliding with seeded NPCs.
    private const int TEST_NPC_ID = 999999999;

    #[Test]
    public function npcimportsubmit_givenSameNpcImportedTwice_doesNotDuplicateNpcDungeonRow(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        $dungeon    = Dungeon::firstOrFail();
        $importData = json_encode([
            'data' => [
                [
                    'id'             => self::TEST_NPC_ID,
                    'location'       => [$dungeon->zone_id],
                    'type'           => 1,
                    'name'           => 'Test Npc Import Dedup',
                    'classification' => 0,
                    'boss'           => 0,
                    'react'          => [-1],
                ],
            ],
        ]);

        try {
            // Act: import the same NPC/dungeon combination twice, as would happen when
            // re-submitting a Wowhead import string for an NPC already in the database.
            $this->post(route('admin.tools.npc.import.submit'), ['import_string' => $importData]);
            $this->post(route('admin.tools.npc.import.submit'), ['import_string' => $importData]);

            // Assert
            $this->assertSame(1, NpcDungeon::query()
                ->where('npc_id', self::TEST_NPC_ID)
                ->where('dungeon_id', $dungeon->id)
                ->count());
        } finally {
            Npc::find(self::TEST_NPC_ID)?->delete();
            MappingChangeLog::query()->where('model_id', self::TEST_NPC_ID)->where('model_class', Npc::class)->delete();
        }
    }

    #[Test]
    public function npcimportsubmit_givenNewNpc_createsItWithTheMappedAttributes(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        $dungeon    = Dungeon::firstOrFail();
        $importData = json_encode([
            'data' => [
                [
                    'id'             => self::TEST_NPC_ID,
                    'location'       => [$dungeon->zone_id],
                    'type'           => 7,
                    'name'           => 'Test Npc Import Boss',
                    'classification' => 1,
                    'boss'           => 1,
                    'react'          => [0],
                ],
            ],
        ]);

        try {
            // Act
            $this->post(route('admin.tools.npc.import.submit'), ['import_string' => $importData]);

            // Assert
            /** @var Npc $npc */
            $npc = Npc::query()->findOrFail(self::TEST_NPC_ID);
            $this->assertSame(NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS], $npc->classification_id);
            $this->assertSame(NpcType::HUMANOID, $npc->npc_type_id);
            $this->assertSame('neutral', $npc->aggressiveness);
            $this->assertTrue((bool)$npc->dangerous);
            $this->assertTrue(NpcDungeon::query()->where('npc_id', self::TEST_NPC_ID)->where('dungeon_id', $dungeon->id)->exists());
        } finally {
            Npc::find(self::TEST_NPC_ID)?->delete();
            MappingChangeLog::query()->where('model_id', self::TEST_NPC_ID)->where('model_class', Npc::class)->delete();
        }
    }

    #[Test]
    public function npcimportsubmit_givenZoneWithoutDungeon_doesNotCreateTheNpc(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        $importData = json_encode([
            'data' => [
                [
                    'id'       => self::TEST_NPC_ID,
                    'location' => [(int)Dungeon::query()->max('zone_id') + 1],
                    'type'     => 7,
                    'name'     => 'Test Npc Import Unknown Zone',
                ],
            ],
        ]);

        try {
            // Act
            $this->post(route('admin.tools.npc.import.submit'), ['import_string' => $importData]);

            // Assert
            $this->assertNull(Npc::find(self::TEST_NPC_ID));
        } finally {
            Npc::find(self::TEST_NPC_ID)?->delete();
            MappingChangeLog::query()->where('model_id', self::TEST_NPC_ID)->where('model_class', Npc::class)->delete();
        }
    }

    #[Test]
    public function manageSpellVisibilitySubmit_givenDungeon_redirectsToThatDungeonsPage(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $dungeon = Dungeon::firstOrFail();

        // Act
        $response = $this->post(route('admin.tools.npc.managespellvisibility.submit'), ['dungeon_id' => $dungeon->id]);

        // Assert
        $response->assertRedirect(route('admin.tools.npc.managespellvisibility', ['dungeon' => $dungeon]));
    }

    #[Test]
    public function manageSpellVisibilitySubmit_givenAllDungeons_redirectsToTheUnfilteredPage(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->post(route('admin.tools.npc.managespellvisibility.submit'), ['dungeon_id' => -1]);

        // Assert
        $response->assertRedirect(route('admin.tools.npc.managespellvisibility'));
    }

    #[Test]
    public function manageSpellVisibilitySubmit_givenUnknownDungeon_returnsNotFound(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->post(route('admin.tools.npc.managespellvisibility.submit'), [
            'dungeon_id' => (int)Dungeon::query()->max('id') + 1000,
        ]);

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function npcsShowMissingDisplayId_givenNpcWithoutDisplayId_listsIt(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $npc = $this->createNpcInDatabase(['display_id' => null]);

        // Act
        $response = $this->get(route('admin.tools.npcs.showmissingdisplayid'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('npcs', static fn($npcs): bool => $npcs->contains('id', $npc->id));
    }

    #[Test]
    public function npcsSaveToSeeder_givenAuthenticatedAdmin_returnsJsonAttachment(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.npcs.savetoseeder'));

        // Assert
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString('attachment', (string)$response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('filename="npcs.json"', (string)$response->headers->get('Content-Disposition'));

        $decoded = json_decode($response->getContent(), true);
        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);

        $firstNpc = $decoded[0];
        // Sanity: entity data is present (relations serialize snake_case via $snakeAttributes).
        $this->assertArrayHasKey('id', $firstNpc);
        $this->assertArrayHasKey('npc_dungeons', $firstNpc);

        // Combat-log-derived behavior must not leak into the download - only hand-curated entity data.
        foreach ($decoded as $npc) {
            $this->assertArrayNotHasKey('npc_spells', $npc);
            $this->assertArrayNotHasKey('npc_characteristics', $npc);
        }
    }

    #[Test]
    public function npcsSaveToSeeder_givenNonAdmin_isForbidden(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.npcs.savetoseeder'));

        // Assert
        $response->assertForbidden();
    }
}
