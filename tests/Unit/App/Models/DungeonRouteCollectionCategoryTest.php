<?php

namespace Tests\Unit\App\Models;

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class DungeonRouteCollectionCategoryTest extends PublicTestCase
{
    /**
     * The ids in ALL are what the seeder writes and what the form posts back, so a drift between
     * the constant and the table means a category silently changes meaning.
     */
    #[Test]
    public function all_givenTheSeededTable_matchesTheConstant(): void
    {
        // Act
        $seeded = DungeonRouteCollectionCategory::query()
            ->pluck('id', 'name')
            ->all();

        // Assert - assertEquals rather than assertSame: row order carries no meaning here
        $this->assertEquals(DungeonRouteCollectionCategory::ALL, $seeded);
    }

    #[Test]
    public function getTranslatedName_givenASeededCategory_returnsItsTranslation(): void
    {
        // Arrange
        $category = DungeonRouteCollectionCategory::query()
            ->where('name', DungeonRouteCollectionCategory::PUG_FRIENDLY)
            ->firstOrFail();

        // Act
        $result = $category->getTranslatedName();

        // Assert
        $this->assertSame(
            __(sprintf('dungeonroutecollectioncategories.%s', DungeonRouteCollectionCategory::PUG_FRIENDLY)),
            $result,
        );
        $this->assertNotSame(
            sprintf('dungeonroutecollectioncategories.%s', DungeonRouteCollectionCategory::PUG_FRIENDLY),
            $result,
            'A missing translation would render the key itself',
        );
    }
}
