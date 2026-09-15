<?php

namespace Tests\Unit\App\Logic\MDT;

use App\Logic\MDT\Entity\MDTNpc;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('MDT')]
final class MDTNpcTest extends TestCase
{
    #[Test]
    public function isBoss_givenIsBossTrue_returnsTrue(): void
    {
        // Arrange
        $raw           = $this->minimalRawMdtNpc();
        $raw['isBoss'] = true;

        // Act
        $npc = new MDTNpc(1, $raw);

        // Assert
        $this->assertTrue($npc->isBoss());
    }

    #[Test]
    public function isBoss_givenIsBossFalse_returnsFalse(): void
    {
        // Arrange
        $raw           = $this->minimalRawMdtNpc();
        $raw['isBoss'] = false;

        // Act
        $npc = new MDTNpc(1, $raw);

        // Assert
        $this->assertFalse($npc->isBoss());
    }

    #[Test]
    public function isBoss_givenIsBossNotSet_returnsFalse(): void
    {
        // Arrange
        $raw = $this->minimalRawMdtNpc();

        // Act
        $npc = new MDTNpc(1, $raw);

        // Assert
        $this->assertFalse($npc->isBoss());
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalRawMdtNpc(): array
    {
        return [
            'clones' => [],
            'id'     => 246404,
            'scale'  => 1.0,
            'health' => 21891970,
            'count'  => 0,
        ];
    }
}
