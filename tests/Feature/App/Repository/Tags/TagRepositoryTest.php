<?php

namespace Tests\Feature\App\Repository\Tags;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Repositories\Interfaces\Tags\TagRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Tag')]
final class TagRepositoryTest extends PublicTestCase
{
    #[Test]
    public function getPersonalRouteTags_givenTaggedAndOrphanAndForeignTags_returnsOnlyOwnTagsOnARouteOncePerName(): void
    {
        // Arrange
        $owner = null;
        $other = null;

        try {
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $this->createTag($owner, 'Zeta', 1);
            $this->createTag($owner, 'Alpha', 1);
            $this->createTag($owner, 'Alpha', 2);
            $this->createTag($owner, 'Orphan', null);
            $this->createTag($other, 'Foreign', 1);

            // Act
            $tags = $this->app->make(TagRepositoryInterface::class)->getPersonalRouteTags($owner);

            // Assert
            $this->assertSame(['Alpha', 'Zeta'], $tags->pluck('name')->all());
        } finally {
            foreach ([$owner, $other] as $user) {
                if ($user !== null) {
                    Tag::query()->where('context_id', $user->id)->where('context_class', User::class)->delete();
                    $user->delete();
                }
            }
        }
    }

    #[Test]
    public function getPersonalRouteTags_givenUserWithoutTags_returnsNothing(): void
    {
        // Arrange
        $user = null;

        try {
            $user = User::factory()->create();

            // Act
            $tags = $this->app->make(TagRepositoryInterface::class)->getPersonalRouteTags($user);

            // Assert
            $this->assertTrue($tags->isEmpty());
        } finally {
            $user?->delete();
        }
    }

    private function createTag(User $user, string $name, ?int $modelId): Tag
    {
        return Tag::query()->create([
            'context_id'      => $user->id,
            'context_class'   => User::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
            'model_id'        => $modelId,
            'model_class'     => DungeonRoute::class,
            'name'            => $name,
            'color'           => null,
        ]);
    }
}
