<?php

namespace Tests\Feature\Console\Commands\Localization;

use App\Console\Commands\Localization\Npc\SyncNpcTypeNames;
use App\Models\Npc\NpcType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Localization')]
final class SyncNpcTypeNamesTest extends PublicTestCase
{
    #[Test]
    public function getNpcTypeNames_givenCreatureTypes_returnsLocalizedNamesByTranslationKey(): void
    {
        // Arrange
        $command = new SyncNpcTypeNames();

        // Act
        $result = $command->getNpcTypeNames(
            [1 => 'Beast', 6 => 'Undead', 10 => 'Not specified'],
            [1 => 'Wildtier', 6 => 'Untoter', 10 => 'Nicht spezifiziert'],
        );

        // Assert
        $this->assertSame(['beast' => 'Wildtier', 'undead' => 'Untoter', 'not_specified' => 'Nicht spezifiziert'], $result);
    }

    #[Test]
    public function getNpcTypeNames_givenCreatureTypeWeDoNotHave_leavesItOut(): void
    {
        // Arrange
        $command = new SyncNpcTypeNames();

        // Act
        $result = $command->getNpcTypeNames([12 => 'Non-combat Pet'], [12 => 'Haustier']);

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function getNpcTypeNames_givenEmptyLocalizedName_leavesItOut(): void
    {
        // Arrange
        $command = new SyncNpcTypeNames();

        // Act
        $result = $command->getNpcTypeNames([1 => 'Beast', 3 => 'Demon'], [1 => ' ', 3 => 'Dämon']);

        // Assert
        $this->assertSame(['demon' => 'Dämon'], $result);
    }

    #[Test]
    public function typeKey_givenEveryNpcType_hasAnEnglishTranslation(): void
    {
        // Arrange
        $englishNames = __('npctypes', [], 'en_US');

        foreach (NpcType::ALL as $typeName => $id) {
            // Act
            $typeKey = new NpcType(['type' => $typeName])->type_key;

            // Assert
            $this->assertSame($typeName, $englishNames[$typeKey] ?? null, sprintf('npctypes.%s', $typeKey));
        }
    }
}
