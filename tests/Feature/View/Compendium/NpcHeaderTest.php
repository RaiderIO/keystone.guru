<?php

namespace Tests\Feature\View\Compendium;

use App\Models\Npc\Npc;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Compendium')]
final class NpcHeaderTest extends PublicTestCase
{
    #[Test]
    public function render_givenNpc_linksToItsWowheadPage(): void
    {
        // Arrange
        $npc = Npc::with(['classification'])->firstOrFail();

        // Act
        $result = view('compendium.npc.sections.header', ['npc' => $npc, 'currentNpcHealth' => null])->render();

        // Assert
        $this->assertStringContainsString(sprintf('https://www.wowhead.com/npc=%d/', $npc->id), $result);
    }

    #[Test]
    public function wowheadUrl_givenNpc_slugsTheName(): void
    {
        // Arrange - an NPC id means the same creature on every client, so the link needs no domain
        $npc     = new Npc(['name' => 'Forgemaster Garfrost']);
        $npc->id = 36494;

        // Act
        $result = $npc->wowhead_url;

        // Assert
        $this->assertSame('https://www.wowhead.com/npc=36494/forgemaster-garfrost', $result);
    }
}
