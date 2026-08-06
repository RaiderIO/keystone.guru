<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Tag')]
final class AjaxTagControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultHeaders = [
            'X-Requested-With' => 'XMLHttpRequest',
        ];
    }

    #[Test]
    public function delete_givenOwnTag_deletesIt(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $tag   = $this->createTagFor($owner);

        try {
            // Act
            $response = $this->actingAs($owner)->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertNoContent();
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        } finally {
            Tag::where('id', $tag->id)->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function delete_givenAnotherUsersTag_returnsForbidden(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $tag   = $this->createTagFor($owner);

        try {
            // Act
            $response = $this->actingAs($other)->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseHas('tags', ['id' => $tag->id]);
        } finally {
            Tag::where('id', $tag->id)->delete();
            $other->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function delete_givenGuest_returnsForbidden(): void
    {
        // The ajax route group carries no auth middleware on purpose - sandbox routes must work for
        // guests - so the policy is the only thing standing between a guest and someone else's tag.
        // Arrange
        $owner = User::factory()->create();
        $tag   = $this->createTagFor($owner);

        try {
            // Act
            $response = $this->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseHas('tags', ['id' => $tag->id]);
        } finally {
            Tag::where('id', $tag->id)->delete();
            $owner->delete();
        }
    }

    private function createTagFor(User $user): Tag
    {
        return Tag::create([
            'context_id'      => $user->id,
            'context_class'   => User::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
            'model_id'        => null,
            'model_class'     => null,
            'name'            => sprintf('test-tag-%s', fake()->uuid()),
            'color'           => null,
        ]);
    }
}
