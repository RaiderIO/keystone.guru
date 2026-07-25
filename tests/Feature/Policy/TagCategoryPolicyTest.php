<?php

namespace Tests\Feature\Policy;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\Tags\TagCategory;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The 'create-tag' ability is invoked as Gate::authorize('create-tag', [$tagCategory, $model]).
 * The Gate resolves the policy from the FIRST array element, so TagCategoryPolicy handles it and
 * the byte-identical TagPolicy::createTag was unreachable. These tests go through the Gate rather
 * than instantiating the policy, so they would catch the resolution silently moving elsewhere.
 */
#[Group('Policy')]
#[Group('TagCategoryPolicy')]
final class TagCategoryPolicyTest extends PublicTestCase
{
    #[Test]
    public function createTag_givenRouteOwner_returnsAllowed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertTrue($owner->can('create-tag', [
                $this->personalTagCategory(),
                $route,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function createTag_givenNonOwnerOfUnpublishedRoute_returnsDenied(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonOwner = User::factory()->create();
        $route    = $this->createRoute($owner, [
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            // Act & Assert
            $this->assertFalse($nonOwner->can('create-tag', [
                $this->personalTagCategory(),
                $route,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function createTag_givenUnknownTagCategory_returnsDenied(): void
    {
        // Arrange - the policy only recognises the two dungeon route categories
        $owner         = User::factory()->create();
        $route         = $this->createRoute($owner);
        $unknown       = new TagCategory();
        $unknown->id   = 0;
        $unknown->name = 'something_else';

        try {
            // Act & Assert
            $this->assertFalse($owner->can('create-tag', [$unknown, $route]));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    private function personalTagCategory(): TagCategory
    {
        return TagCategory::where('name', TagCategory::DUNGEON_ROUTE_PERSONAL)->firstOrFail();
    }

    /**
     * Creates a non-sandbox route owned by the given user. Sandbox routes are editable by anyone,
     * which would make an authorization assertion meaningless.
     *
     * @param array<string, mixed> $overrides
     */
    private function createRoute(User $owner, array $overrides = []): DungeonRoute
    {
        return DungeonRoute::factory()->create(array_merge([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ], $overrides));
    }
}
