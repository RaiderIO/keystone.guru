<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Expansion;
use Database\Seeders\DungeonDataSeeder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonDataSeeder')]
final class DungeonDataSeederFindExpansionTest extends PublicTestCase
{
    #[Test]
    public function findExpansionForDungeonDataDirectory_givenExpansionKey_returnsExpansion(): void
    {
        // Arrange
        $expansionId = Expansion::ALL[Expansion::EXPANSION_TWW];

        // Act
        $expansion = new DungeonDataSeeder()->findExpansionForDungeonDataDirectory(Expansion::EXPANSION_TWW);

        // Assert
        $this->assertNotNull($expansion);
        $this->assertSame($expansionId, $expansion->id);
    }

    #[Test]
    public function findExpansionForDungeonDataDirectory_givenUnknownDirectory_returnsNull(): void
    {
        // Arrange
        $directoryName = 'not_an_expansion';

        // Act
        $expansion = new DungeonDataSeeder()->findExpansionForDungeonDataDirectory($directoryName);

        // Assert
        $this->assertNull($expansion);
    }
}
