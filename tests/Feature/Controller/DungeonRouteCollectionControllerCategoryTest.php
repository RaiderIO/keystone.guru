<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\DungeonRoute\DungeonRouteCollectionCategoryType;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * A collection category is a difficulty level only: the forms list exactly the difficulty levels
 * and only those are accepted.
 */
#[Group('Controller')]
final class DungeonRouteCollectionControllerCategoryTest extends PublicTestCase
{
    #[Test]
    public function create_givenTheSeededCategories_listsEveryDifficulty(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->get(route('collections.new'));

            // Assert
            $response->assertOk();
            $this->assertEqualsCanonicalizing(
                $this->difficultyCategoryIds(),
                $this->categoryIdsFrom($response->viewData('categories')),
            );
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function edit_givenTheSeededCategories_listsEveryDifficulty(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);

        try {
            // Act
            $response = $this->actingAs($creator)
                ->get(route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $this->assertEqualsCanonicalizing(
                $this->difficultyCategoryIds(),
                $this->categoryIdsFrom($response->viewData('categories')),
            );
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenACategoryThatDoesNotExist_failsValidationAndKeepsTheCategory(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'                              => $creator->id,
            'dungeon_route_collection_category_id' => DungeonRouteCollectionCategoryType::Expert->id(),
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => $dungeonRouteCollection->name,
                    'published_state' => PublishedState::WORLD,
                    'category_id'     => 99999,
                ],
            );

            // Assert
            $response->assertSessionHasErrors('category_id');
            $this->assertSame(
                DungeonRouteCollectionCategoryType::Expert->id(),
                $dungeonRouteCollection->refresh()->dungeon_route_collection_category_id,
            );
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenEveryDifficultyCategory_acceptsIt(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            foreach ($this->difficultyCategoryIds() as $categoryId) {
                // Act
                $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                    'name'            => sprintf('ZzTestDifficultyCollection%d', $categoryId),
                    'published_state' => PublishedState::WORLD,
                    'category_id'     => $categoryId,
                ]);

                // Assert
                $response->assertSessionHasNoErrors();
            }

            $this->assertEqualsCanonicalizing(
                $this->difficultyCategoryIds(),
                DungeonRouteCollection::query()
                    ->where('user_id', $creator->id)
                    ->pluck('dungeon_route_collection_category_id')
                    ->all(),
            );
        } finally {
            DungeonRouteCollection::query()->where('user_id', $creator->id)->get()->each->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    private function createCreator(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    /** @return array<int, int> */
    private function difficultyCategoryIds(): array
    {
        return [
            DungeonRouteCollectionCategoryType::Beginner->id(),
            DungeonRouteCollectionCategoryType::Intermediate->id(),
            DungeonRouteCollectionCategoryType::Expert->id(),
            DungeonRouteCollectionCategoryType::Mdi->id(),
        ];
    }

    /**
     * @param  EloquentCollection<int, DungeonRouteCollectionCategory> $categories
     * @return array<int, int>
     */
    private function categoryIdsFrom(EloquentCollection $categories): array
    {
        return $categories->pluck('id')->all();
    }
}
