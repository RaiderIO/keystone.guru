<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\Dungeon;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsNpcControllerTest extends PublicTestCase
{
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
