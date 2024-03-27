<?php

namespace Tests\Unit\App\Service\Spell;

use JetBrains\PhpStorm\NoReturn;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\LoggingFixtures;
use Tests\Unit\Fixtures\ServiceFixtures;

class SpellServiceTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[NoReturn]
    #[Test]
    #[Group('SpellService')]
    #[DataProvider('getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidRowClassName_DataProvider')]
    public function getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidRowClassName(
        string $rowClassName,
        string $expected
    ): void {
        // Arrange
        $log          = LoggingFixtures::createSpellServiceLogging($this);
        $spellService = ServiceFixtures::getSpellServiceMock(
            $this,
            [],
            $log
        );

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

    public static function getCategoryNameFromRowClassName_ShouldReturnCategoryName_GivenValidRowClassName_DataProvider(): array
    {
        return [
            [
                'Warrior',
                'spells.category.warrior',
            ],
            [
                'Hunter',
                'spells.category.hunter',
            ],
            [
                'Death Knight',
                'spells.category.death_knight',
            ],
            [
                'Mage',
                'spells.category.mage',
            ],
            [
                'Priest',
                'spells.category.priest',
            ],
            [
                'Monk',
                'spells.category.monk',
            ],
            [
                'Rogue',
                'spells.category.rogue',
            ],
            [
                'Warlock',
                'spells.category.warlock',
            ],
            [
                'Shaman',
                'spells.category.shaman',
            ],
            [
                'Paladin',
                'spells.category.paladin',
            ],
            [
                'Druid',
                'spells.category.druid',
            ],
            [
                'Demon Hunter',
                'spells.category.demon_hunter',
            ],
            [
                'Evoker',
                'spells.category.evoker',
            ],
        ];
    }

}
