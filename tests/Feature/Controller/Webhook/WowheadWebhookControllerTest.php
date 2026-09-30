<?php

namespace Tests\Feature\Controller\Webhook;

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
}
