<?php

namespace Tests\Feature\Console\Commands\Localization;

use App\Console\Commands\Localization\Npc\SyncNpcNames;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Localization')]
final class SyncNpcNamesTest extends PublicTestCase
{
    #[Test]
    public function mergeNpcNames_givenNpcMissingFromLocale_addsFetchedName(): void
    {
        // Arrange
        $command = new SyncNpcNames();

        // Act
        $result = $command->mergeNpcNames(
            [1 => 'Kröte'],
            [1 => 'Toad', 2 => 'Nalorakk'],
            [1 => 'Kröte', 2 => 'Nalorakk (de)'],
        );

        // Assert
        $this->assertSame([1 => 'Kröte', 2 => 'Nalorakk (de)'], $result);
    }

    #[Test]
    public function mergeNpcNames_givenFetchedNpcUnknownToEnglish_leavesItOut(): void
    {
        // Arrange
        $command = new SyncNpcNames();

        // Act
        $result = $command->mergeNpcNames(
            [1 => 'Kröte'],
            [1 => 'Toad'],
            [1 => 'Kröte', 99 => 'Unbekannt'],
        );

        // Assert
        $this->assertSame([1 => 'Kröte'], $result);
    }

    #[Test]
    public function mergeNpcNames_givenEmptyFetchedName_keepsExistingNameOrLeavesNpcAbsent(): void
    {
        // Arrange
        $command = new SyncNpcNames();

        // Act
        $result = $command->mergeNpcNames(
            [1 => 'Kröte'],
            [1 => 'Toad', 2 => 'Nalorakk'],
            [1 => '', 2 => ' '],
        );

        // Assert
        $this->assertSame([1 => 'Kröte'], $result);
    }

    #[Test]
    public function mergeNpcNames_givenExistingEmptyName_fillsItFromFetchedName(): void
    {
        // Arrange
        $command = new SyncNpcNames();

        // Act
        $result = $command->mergeNpcNames(
            [2 => '', 1 => 'Kröte'],
            [1 => 'Toad', 2 => 'Nalorakk'],
            [2 => 'Nalorakk'],
        );

        // Assert
        $this->assertSame([1 => 'Kröte', 2 => 'Nalorakk'], $result);
    }
}
