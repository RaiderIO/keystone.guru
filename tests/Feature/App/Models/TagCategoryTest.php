<?php

namespace Tests\Feature\App\Models;

use App\Models\Tags\TagCategory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('TagCategory')]
final class TagCategoryTest extends PublicTestCase
{
    #[Test]
    public function TagCategory_givenSeededRows_haveKeyMatchingName(): void
    {
        // Arrange
        $expectedKeysById = array_flip(TagCategory::ALL);

        // Act
        $rows = TagCategory::query()->orderBy('id')->get(['id', 'key', 'name']);

        // Assert
        $this->assertCount(count($expectedKeysById), $rows);
        foreach ($rows as $row) {
            $this->assertSame($expectedKeysById[$row->id], $row->key, sprintf('%s %d has the wrong key', TagCategory::class, $row->id));
            $this->assertSame($row->name, $row->key, sprintf('%s %d has a key that differs from its name', TagCategory::class, $row->id));
        }
    }

    #[Test]
    public function resolveRouteBinding_givenKeyWhoseLegacyNameDiffers_returnsThatCategory(): void
    {
        // Arrange
        $id = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM];
        TagCategory::query()->whereKey($id)->update(['name' => 'legacy_dungeon_route_team']);

        try {
            // Act
            $tagCategory = new TagCategory()->resolveRouteBinding(TagCategory::DUNGEON_ROUTE_TEAM);

            // Assert
            $this->assertInstanceOf(TagCategory::class, $tagCategory);
            $this->assertSame($id, $tagCategory->id);
        } finally {
            TagCategory::query()->whereKey($id)->update(['name' => TagCategory::DUNGEON_ROUTE_TEAM]);
        }
    }
}
