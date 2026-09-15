<?php

namespace Tests\Feature\Traits;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

/**
 * Locks the three states {@see ReadsDungeonSelect} distinguishes.
 *
 * The reader used to answer null for both "the select is missing" and "nothing is selected", which is what
 * made three MDT bumps in a row debug `Failed asserting that null is identical to <id>` from scratch. These
 * cases run against hand-written HTML rather than a rendered page, so they pin the reader itself and not
 * whatever the current seed data happens to render.
 */
#[Group('ReadsDungeonSelect')]
final class ReadsDungeonSelectTest extends PublicTestCase
{
    use ReadsDungeonSelect;

    #[Test]
    public function getSelectedDungeonId_givenSelectedOption_returnsItsId(): void
    {
        // Arrange
        $html = $this->renderSelect(['12' => false, '34' => true, '56' => false]);

        // Act
        $selectedDungeonId = $this->getSelectedDungeonId($html);

        // Assert
        self::assertSame(34, $selectedDungeonId);
    }

    #[Test]
    public function getSelectedDungeonId_givenSelectWithNoSelectedOption_throwsListingTheOfferedIds(): void
    {
        // Arrange
        $html = $this->renderSelect(['12' => false, '34' => false]);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('renders no selected option. It offers 2 option(s): [12, 34]');

        $this->getSelectedDungeonId($html);
    }

    #[Test]
    public function getSelectedDungeonId_givenMissingSelect_throwsNamingTheSelectsThatArePresent(): void
    {
        // Arrange - a page that rendered some other filter, but not the dungeon one
        $html = $this->renderSelect(['12' => true], 'compendium_filter_class');

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has no <select id="compendium_filter_dungeon">. Selects present: [compendium_filter_class]');

        $this->getSelectedDungeonId($html);
    }

    #[Test]
    public function getSelectedDungeonId_givenMultipleSelectedOptions_throws(): void
    {
        // Arrange
        $html = $this->renderSelect(['12' => true, '34' => true]);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('renders 2 selected options ([12, 34])');

        $this->getSelectedDungeonId($html);
    }

    #[Test]
    public function getSelectedDungeonId_givenNonDungeonOptionSelected_throws(): void
    {
        // Arrange - the select also carries `all`, season and expansion options, which cast to a bogus id
        $html = $this->renderSelect(['season-3' => true, '12' => false]);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has "season-3" selected, which is not a dungeon id');

        $this->getSelectedDungeonId($html);
    }

    #[Test]
    public function getOfferedDungeonIds_givenNonDungeonOptions_returnsOnlyTheDungeonIds(): void
    {
        // Arrange - `-1` is "all dungeons", and the season/expansion options are not dungeons either
        $html = $this->renderSelect(['-1' => false, 'season-3' => true, 'expansion-9' => false, '12' => false, '34' => false]);

        // Act
        $dungeonIds = $this->getOfferedDungeonIds($html);

        // Assert
        self::assertSame([12, 34], $dungeonIds);
    }

    #[Test]
    public function getOfferedDungeonIds_givenMissingSelect_throws(): void
    {
        // Arrange
        $html = $this->renderSelect(['12' => false], 'compendium_filter_class');

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has no <select id="compendium_filter_dungeon">');

        $this->getOfferedDungeonIds($html);
    }

    #[Test]
    public function assertNoDungeonSelected_givenSelectWithNoSelectedOption_passes(): void
    {
        // Arrange
        $html = $this->renderSelect(['12' => false, '34' => false]);

        // Act & Assert
        $this->assertNoDungeonSelected($html);
    }

    #[Test]
    public function assertNoDungeonSelected_givenSelectedOption_fails(): void
    {
        // Arrange
        $html = $this->renderSelect(['12' => false, '34' => true]);

        // Act & Assert
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('but [34] is selected out of 2 offered option(s)');

        $this->assertNoDungeonSelected($html);
    }

    #[Test]
    public function assertNoDungeonSelected_givenMissingSelect_throwsRatherThanPassing(): void
    {
        // Arrange - the silent case: an absent select has no selected option either, and used to pass
        $html = $this->renderSelect(['12' => true], 'compendium_filter_class');

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has no <select id="compendium_filter_dungeon">');

        $this->assertNoDungeonSelected($html);
    }

    /**
     * @param array<array-key, bool> $options Option value => whether it carries the `selected` attribute. PHP casts
     *                                        numeric-string keys to int, hence the loose key type.
     */
    private function renderSelect(array $options, string $selectId = 'compendium_filter_dungeon'): string
    {
        $renderedOptions = '';
        foreach ($options as $value => $selected) {
            $value = (string)$value;
            $renderedOptions .= sprintf(
                '<option value="%s"%s>Dungeon %s</option>',
                $value,
                $selected ? ' selected="selected"' : '',
                $value,
            );
        }

        return sprintf('<html><body><select id="%s">%s</select></body></html>', $selectId, $renderedOptions);
    }
}
