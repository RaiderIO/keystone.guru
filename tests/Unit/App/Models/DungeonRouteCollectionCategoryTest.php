<?php

namespace Tests\Unit\App\Models;

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\DungeonRoute\DungeonRouteCollectionCategoryType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class DungeonRouteCollectionCategoryTest extends PublicTestCase
{
    /**
     * The ids the enum reports are what the seeder writes and what the form posts back, so a drift
     * between the enum and the table means a category silently changes meaning.
     */
    #[Test]
    public function cases_givenTheSeededTable_matchTheEnum(): void
    {
        // Arrange
        $expected = [];
        foreach (DungeonRouteCollectionCategoryType::cases() as $categoryType) {
            $expected[$categoryType->value] = $categoryType->id();
        }

        // Act
        $seeded = DungeonRouteCollectionCategory::query()
            ->pluck('id', 'name')
            ->all();

        // Assert - assertEquals rather than assertSame: row order carries no meaning here
        $this->assertEquals($expected, $seeded);
    }

    #[Test]
    public function getTranslatedName_givenASeededCategory_returnsItsTranslation(): void
    {
        // Arrange
        $category = DungeonRouteCollectionCategory::query()
            ->where('name', DungeonRouteCollectionCategoryType::Beginner->value)
            ->firstOrFail();

        // Act
        $result = $category->getTranslatedName();

        // Assert
        $this->assertSame(
            __(sprintf('dungeonroutecollectioncategories.%s', DungeonRouteCollectionCategoryType::Beginner->value)),
            $result,
        );
        $this->assertNotSame(
            sprintf('dungeonroutecollectioncategories.%s', DungeonRouteCollectionCategoryType::Beginner->value),
            $result,
            'A missing translation would render the key itself',
        );
    }
}
