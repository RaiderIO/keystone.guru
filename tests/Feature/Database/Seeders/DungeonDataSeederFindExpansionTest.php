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
    public function findExpansionForDungeonDataDirectory_givenKeyWithLegacyShortname_returnsExpansion(): void
    {
        // Arrange
        $expansionId = Expansion::ALL[Expansion::EXPANSION_TWW];
        Expansion::query()->whereKey($expansionId)->update(['shortname' => sprintf('legacy_%s', Expansion::EXPANSION_TWW)]);

        try {
            // Act
            $expansion = new DungeonDataSeeder()->findExpansionForDungeonDataDirectory(Expansion::EXPANSION_TWW);

            // Assert
            $this->assertNotNull($expansion);
            $this->assertSame($expansionId, $expansion->id);
        } finally {
            Expansion::query()->whereKey($expansionId)->update(['shortname' => Expansion::EXPANSION_TWW]);
        }
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
