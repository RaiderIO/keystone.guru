<?php

namespace Tests\Unit\App\Service\Spell;

use App\Models\CharacterClass;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Fixtures\LoggingFixtures;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

class SpellServiceGetCategoryNameFromRowClassNameTest extends PublicTestCase
{
    /** @var Collection<CharacterClass> */
    private Collection $characterClasses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterClasses = CharacterClass::all()->keyBy('key');
    }


    /**
     * Scenario: Happy path, if we have a valid row class name we should find the character class for it, and return
     * the corresponding category for it.
     *
     * @throws Exception
     */
    #[Test]
    #[Group('SpellService')]
    #[DataProvider('getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidClassBasedRowClassName_DataProvider')]
    public function getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidClassBasedRowClassName(
        string $rowClassName,
        string $characterClassName,
        string $expected
    ): void {
        // Arrange
        $log          = LoggingFixtures::createSpellServiceLogging($this);
        $spellService = ServiceFixtures::getSpellServiceMock(
            $this,
            ['getCharacterClassFromClassName'],
            $log
        );

        $spellService
            ->expects($this->once())
            ->method('getCharacterClassFromClassName')
            ->willReturn($this->characterClasses->get($characterClassName));

        $log
            ->expects($this->never())
            ->method('getCategoryNameFromClassNameUnableToFindCharacterClass');

        $log
            ->expects($this->never())
            ->method('getCategoryNameFromClassNameUnableToFindCategory');

        // Act
        $result = $spellService->getCategoryNameFromRowClassName($rowClassName);

        // Assert
        Assert::assertEquals($expected, $result);
    }

    public static function getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidClassBasedRowClassName_DataProvider(): array
    {
        return [
            [
                'Warrior Protection',
                CharacterClass::CHARACTER_CLASS_WARRIOR,
                'spells.category.warrior',
            ],
            [
                'Hunter Survival',
                CharacterClass::CHARACTER_CLASS_HUNTER,
                'spells.category.hunter',
            ],
            [
                'Death Knight Blood',
                CharacterClass::CHARACTER_CLASS_DEATH_KNIGHT,
                'spells.category.death_knight',
            ],
            [
                'Mage Fire',
                CharacterClass::CHARACTER_CLASS_MAGE,
                'spells.category.mage',
            ],
            [
                'Priest Discipline',
                CharacterClass::CHARACTER_CLASS_PRIEST,
                'spells.category.priest',
            ],
            [
                'Monk Mistweaver',
                CharacterClass::CHARACTER_CLASS_MONK,
                'spells.category.monk',
            ],
            [
                'Rogue Assassination',
                CharacterClass::CHARACTER_CLASS_ROGUE,
                'spells.category.rogue',
            ],
            [
                'Warlock Destruction',
                CharacterClass::CHARACTER_CLASS_WARLOCK,
                'spells.category.warlock',
            ],
            [
                'Shaman Enhancement',
                CharacterClass::CHARACTER_CLASS_SHAMAN,
                'spells.category.shaman',
            ],
            [
                'Paladin Retribution',
                CharacterClass::CHARACTER_CLASS_PALADIN,
                'spells.category.paladin',
            ],
            [
                'Druid Restoration',
                CharacterClass::CHARACTER_CLASS_DRUID,
                'spells.category.druid',
            ],
            [
                'Demon Hunter Havoc',
                CharacterClass::CHARACTER_CLASS_DEMON_HUNTER,
                'spells.category.demon_hunter',
            ],
            [
                'Evoker Preservation',
                CharacterClass::CHARACTER_CLASS_EVOKER,
                'spells.category.evoker',
            ],
        ];
    }


    /**
     * Scenario: Happy path, if we have a valid non-class based row class name, we should find the category immediately
     * without diving into character classes at all
     *
     * @throws Exception
     */
    #[Test]
    #[Group('SpellService')]
    #[DataProvider('getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidNonClassBasedRowClassName_DataProvider')]
    public function getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidNonClassBasedRowClassName(
        string $rowClassName,
        string $expected
    ): void {
        // Arrange
        $log          = LoggingFixtures::createSpellServiceLogging($this);
        $spellService = ServiceFixtures::getSpellServiceMock(
            $this,
            ['getCharacterClassFromClassName'],
            $log
        );

        $spellService
            ->expects($this->never())
            ->method('getCharacterClassFromClassName');

        $log
            ->expects($this->never())
            ->method('getCategoryNameFromClassNameUnableToFindCharacterClass');

        $log
            ->expects($this->never())
            ->method('getCategoryNameFromClassNameUnableToFindCategory');

        // Act
        $result = $spellService->getCategoryNameFromRowClassName($rowClassName);

        // Assert
        Assert::assertEquals($expected, $result);
    }

    public static function getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidNonClassBasedRowClassName_DataProvider(): array
    {
        return [
            [
                'General',
                'spells.category.general',
            ],
        ];
    }


    /**
     * Scenario: Unhappy path, if we have an invalid row class name, and we cannot find the class for it, then we return
     * null.
     *
     * @throws Exception
     */
    #[Test]
    #[Group('SpellService')]
    public function getCategoryNameFromRowClassName_ShouldReturnNull_GivenInvalidRowClassName(): void
    {
        // Arrange
        $rowClassName = 'Some random new category';

        $log          = LoggingFixtures::createSpellServiceLogging($this);
        $spellService = ServiceFixtures::getSpellServiceMock(
            $this,
            ['getCharacterClassFromClassName'],
            $log
        );

        $spellService
            ->expects($this->once())
            ->method('getCharacterClassFromClassName')
            ->willReturn(null);

        $log
            ->expects($this->once())
            ->method('getCategoryNameFromClassNameUnableToFindCharacterClass');

        $log
            ->expects($this->never())
            ->method('getCategoryNameFromClassNameUnableToFindCategory');

        // Act
        $result = $spellService->getCategoryNameFromRowClassName($rowClassName);

        // Assert
        Assert::assertNull($result);
    }


    /**
     * Scenario: Unhappy path, if we have a added a new class but there wasn't a new category added for that class yet.
     *
     * @throws Exception
     */
    #[Test]
    #[Group('SpellService')]
    public function getCategoryNameFromRowClassName_ShouldReturnNull_GivenNewClassButNoCategory(): void
    {
        // Arrange
        $rowClassName = 'Tinker Explosivist';

        // Just make up a new class here
        $characterClassTinker       = $this->characterClasses->first();
        $characterClassTinker->name = 'classes.tinker';

        $log          = LoggingFixtures::createSpellServiceLogging($this);
        $spellService = ServiceFixtures::getSpellServiceMock(
            $this,
            ['getCharacterClassFromClassName'],
            $log
        );

        $spellService
            ->expects($this->once())
            ->method('getCharacterClassFromClassName')
            ->willReturn($characterClassTinker);

        $log
            ->expects($this->never())
            ->method('getCategoryNameFromClassNameUnableToFindCharacterClass');

        $log
            ->expects($this->once())
            ->method('getCategoryNameFromClassNameUnableToFindCategory');

        // Act
        $result = $spellService->getCategoryNameFromRowClassName($rowClassName);

        // Assert
        Assert::assertNull($result);
    }

}
