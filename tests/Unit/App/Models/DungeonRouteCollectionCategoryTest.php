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
            ->pluck('id', 'key')
            ->all();

        // Assert - assertEquals rather than assertSame: row order carries no meaning here
        $this->assertEquals($expected, $seeded);
    }

    #[Test]
    public function getTranslatedName_givenASeededCategory_returnsItsTranslation(): void
    {
        // Arrange
        $category = DungeonRouteCollectionCategory::query()
            ->where('key', DungeonRouteCollectionCategoryType::Beginner->value)
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

    #[Test]
    public function DungeonRouteCollectionCategory_givenSeededRows_haveKeyMatchingName(): void
    {
        // Arrange
        $expectedKeysById = [];
        foreach (DungeonRouteCollectionCategoryType::cases() as $categoryType) {
            $expectedKeysById[$categoryType->id()] = $categoryType->value;
        }

        // Act
        $rows = DungeonRouteCollectionCategory::query()->orderBy('id')->get(['id', 'key', 'name']);

        // Assert
        $this->assertCount(count($expectedKeysById), $rows);
        foreach ($rows as $row) {
            $this->assertSame($expectedKeysById[$row->id], $row->key, sprintf('%s %d has the wrong key', DungeonRouteCollectionCategory::class, $row->id));
            $this->assertSame($row->name, $row->key, sprintf('%s %d has a key that differs from its name', DungeonRouteCollectionCategory::class, $row->id));
        }
    }

    #[Test]
    public function getTranslatedName_givenACategoryWithOnlyAKey_returnsTheTranslationOfItsKey(): void
    {
        // Arrange - the label must survive the contract release dropping the name column
        $category = new DungeonRouteCollectionCategory(['key' => DungeonRouteCollectionCategoryType::Expert->value]);

        // Act
        $result = $category->getTranslatedName();

        // Assert
        $this->assertSame(
            __(sprintf('dungeonroutecollectioncategories.%s', DungeonRouteCollectionCategoryType::Expert->value)),
            $result,
        );
        $this->assertNotSame(
            sprintf('dungeonroutecollectioncategories.%s', DungeonRouteCollectionCategoryType::Expert->value),
            $result,
            'A missing translation would render the key itself',
        );
    }
}
