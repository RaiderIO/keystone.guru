<?php

namespace Tests\Feature\App\Models\Tags;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Tag')]
final class TagTest extends PublicTestCase
{
    #[Test]
    public function unique_givenANameOnSeveralRoutes_returnsOneTagPerNameWithTheLowestId(): void
    {
        $user = null;

        try {
            // Arrange
            $user               = User::factory()->create();
            $personalCategoryId = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL];
            $firstAlphaTag      = $this->createTag($user, $personalCategoryId, 'alpha', 1);
            $this->createTag($user, $personalCategoryId, 'alpha', 2);
            $betaTag = $this->createTag($user, $personalCategoryId, 'beta', 1);
            $this->createTag($user, TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM], 'gamma', 1);

            // Act
            $tags = $user->tags()->unique($personalCategoryId)->orderBy('name')->get();

            // Assert
            $this->assertSame([$firstAlphaTag->id, $betaTag->id], $tags->pluck('id')->all());
        } finally {
            if ($user !== null) {
                Tag::query()->where('context_class', User::class)->where('context_id', $user->id)->delete();
            }
            $user?->delete();
        }
    }

    private function createTag(User $user, int $tagCategoryId, string $name, int $modelId): Tag
    {
        return Tag::create([
            'context_id'      => $user->id,
            'context_class'   => User::class,
            'tag_category_id' => $tagCategoryId,
            'model_id'        => $modelId,
            'model_class'     => DungeonRoute::class,
            'name'            => $name,
            'color'           => null,
        ]);
    }
}
