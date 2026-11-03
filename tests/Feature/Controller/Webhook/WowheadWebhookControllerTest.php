<?php

namespace Tests\Feature\Controller\Webhook;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcHealth;
use App\Models\Npc\NpcType;
use App\Service\Wowhead\WowheadServiceInterface;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCase;

/**
 * Both endpoints write to spells and npcs and are reachable without any authentication, so the only thing
 * keeping them closed in production is the debug gate. Nothing covered it before.
 */
#[Group('Controller')]
#[Group('Webhook')]
final class WowheadWebhookControllerTest extends TestCase
{
    use CreatesNpc;

    #[Test]
    #[DataProvider('debugDisabledProvider')]
    public function wowheadSpell_givenDebugNotEnabled_returnsForbidden(mixed $debug): void
    {
        // Arrange
        config(['app.debug' => $debug]);

        // Act
        $response = $this->post('/webhook/wowhead/spell', [
            'url'  => 'https://www.wowhead.com/spell=12345',
            'html' => '<div></div>',
        ]);

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    #[DataProvider('debugDisabledProvider')]
    public function wowheadNpc_givenDebugNotEnabled_returnsForbidden(mixed $debug): void
    {
        // Arrange
        config(['app.debug' => $debug]);

        // Act
        $response = $this->post('/webhook/wowhead/npc', [
            'url'  => 'https://www.wowhead.com/npc=12345',
            'html' => '<div></div>',
        ]);

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function wowheadNpc_givenDebugEnabled_storesTheParsedDisplayId(): void
    {
        // Arrange
        config(['app.debug' => true]);
        $npc            = $this->createNpcInDatabase(['display_id' => null]);
        $wowheadService = Mockery::mock(WowheadServiceInterface::class);
        $this->instance(WowheadServiceInterface::class, $wowheadService);
        /** @var \Mockery\Expectation $expectation */
        $expectation = $wowheadService->shouldReceive('getNpcDisplayId');
        $expectation->once()->andReturn(424242);

        // Act
        $response = $this->post('/webhook/wowhead/npc', [
            'url'  => sprintf('https://www.wowhead.com/npc=%d', $npc->id),
            'html' => '<div></div>',
        ]);

        // Assert
        $response->assertOk();
        $this->assertSame(424242, $npc->fresh()->display_id);
    }

    #[Test]
    public function wowheadNpc_givenUnknownNpc_createsTheNpcFromThePage(): void
    {
        // Arrange
        config(['app.debug' => true]);
        $dungeon = Dungeon::query()->whereNotNull('zone_id')->where('zone_id', '>', 0)->firstOrFail();
        $npcId   = $this->getUnusedNpcId();
        $html    = self::npcPageHtml($npcId, sprintf(
            '{"boss":1,"classification":1,"location":[%d],"name":"Brew Master Aldryr","type":6}',
            $dungeon->zone_id,
        ));

        try {
            // Act
            $response = $this->post('/webhook/wowhead/npc', [
                'url'  => sprintf('https://www.wowhead.com/npc=%d/brew-master-aldryr', $npcId),
                'html' => $html,
            ]);

            // Assert
            $response->assertOk();
            $npc = Npc::query()->with(['npcDungeons', 'npcHealths'])->findOrFail($npcId);
            $this->assertSame('Brew Master Aldryr', $npc->name);
            $this->assertSame(118999, $npc->display_id);
            $this->assertSame(NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS], $npc->classification_id);
            $this->assertSame(NpcType::UNDEAD, $npc->npc_type_id);
            $this->assertSame(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL)->id, $npc->game_version_id);
            $this->assertContains($dungeon->id, $npc->npcDungeons->pluck('dungeon_id')->all());
            $this->assertSame([NpcHealth::HEALTH_PLACEHOLDER], $npc->npcHealths->pluck('health')->all());
        } finally {
            $this->deleteNpcWithHealth($npcId);
        }
    }

    #[Test]
    public function wowheadNpc_givenUnknownNpcOnPageWithoutNpcData_returnsUnprocessableAndCreatesNothing(): void
    {
        // Arrange
        config(['app.debug' => true]);
        $npcId = $this->getUnusedNpcId();

        try {
            // Act
            $response = $this->post('/webhook/wowhead/npc', [
                'url'  => sprintf('https://www.wowhead.com/npc=%d', $npcId),
                'html' => "<html><body>NPC #{$npcId} doesn't exist. It may have been removed from the game.</body></html>",
            ]);

            // Assert
            $response->assertUnprocessable();
            $this->assertNull(Npc::find($npcId));
        } finally {
            $this->deleteNpcWithHealth($npcId);
        }
    }

    #[Test]
    public function wowheadNpc_givenExistingNpc_doesNotOverwriteItsOtherData(): void
    {
        // Arrange
        config(['app.debug' => true]);
        $npc  = $this->createNpcInDatabase(['display_id' => null, 'name' => 'Curated Name']);
        $html = self::npcPageHtml($npc->id, '{"boss":1,"classification":1,"name":"Wowhead Name","type":6}');

        // Act
        $response = $this->post('/webhook/wowhead/npc', [
            'url'  => sprintf('https://www.wowhead.com/npc=%d', $npc->id),
            'html' => $html,
        ]);

        // Assert
        $response->assertOk();
        $npc = $npc->fresh();
        $this->assertSame(118999, $npc->display_id);
        $this->assertSame('Curated Name', $npc->name);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function debugDisabledProvider(): array
    {
        return [
            'explicitly disabled' => [false],
            'null'                => [null],
        ];
    }

    private function getUnusedNpcId(): int
    {
        return max(900_000_000, (int)Npc::query()->max('id') + 1);
    }

    private function deleteNpcWithHealth(int $npcId): void
    {
        Npc::find($npcId)?->delete();
        NpcHealth::query()->where('npc_id', $npcId)->delete();
    }

    /**
     * The lines of a real Wowhead NPC page that carry its data, around some unrelated markup.
     */
    private static function npcPageHtml(int $npcId, string $npcJson): string
    {
        return implode(PHP_EOL, [
            '<html><body>',
            sprintf('$.extend(g_npcs[%d], %s);', $npcId, $npcJson),
            '    linksButton.dataset.displayId = 118999;',
            '</body></html>',
        ]);
    }
}
