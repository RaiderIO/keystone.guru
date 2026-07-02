<?php

namespace Tests\Feature\Controller\AdminTools;

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
