<?php

namespace Tests\Feature\App\Models;

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use App\Models\Laratrust\Role;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('User')]
final class UserTest extends PublicTestCase
{
    #[Test]
    public function hasRole_givenUserHydratedInMultiRowCollection_doesNotLazyLoadRolesRelation(): void
    {
        // Arrange - fetching more than one row arms Eloquent's preventLazyLoading for these models
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        try {
            $user = User::query()->whereIn('id', [$userA->id, $userB->id])->get()->firstOrFail();

            // Act & Assert - would throw LazyLoadingViolationException if `roles` isn't explicitly loaded first
            $this->assertFalse($user->hasRole(Role::ROLE_ADMIN));
        } finally {
            $userA->delete();
            $userB->delete();
        }
    }

    #[Test]
    public function hasPermission_givenUserHydratedInMultiRowCollection_doesNotLazyLoadRolesRelation(): void
    {
        // Arrange - fetching more than one row arms Eloquent's preventLazyLoading for these models
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        try {
            $user = User::query()->whereIn('id', [$userA->id, $userB->id])->get()->firstOrFail();

            // Act & Assert - would throw LazyLoadingViolationException if `roles` isn't explicitly loaded first
            $this->assertFalse($user->hasPermission('some-permission-that-does-not-exist'));
        } finally {
            $userA->delete();
            $userB->delete();
        }
    }

    #[Test]
    public function delete_givenUserWithPopulatedDungeonRoute_firesTheDungeonRouteDeletingHook(): void
    {
        // Arrange - a route carrying the relations DungeonRoute::deleting is responsible for. A mass
        // delete on the relation skips that hook entirely and leaves every one of these behind (#3869).
        $user         = User::factory()->create();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $user->id]);

        $file = File::create([
            'model_id'    => $dungeonRoute->id,
            'model_class' => DungeonRoute::class,
            'disk'        => 'local',
            'path'        => sprintf('test/%s.png', $dungeonRoute->public_key),
        ]);

        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $dungeonRoute->dungeon->floors->first()->id,
            'file_id'          => $file->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Standard,
        ]);

        $tag = Tag::create([
            'context_id'      => $user->id,
            'context_class'   => User::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
            'model_id'        => $dungeonRoute->id,
            'model_class'     => DungeonRoute::class,
            'name'            => 'test-tag',
        ]);

        // Lives on the combatlog connection - the hook switches connection specifically to delete it
        $challengeModeRun = ChallengeModeRun::create([
            'dungeon_id'       => $dungeonRoute->dungeon_id,
            'dungeon_route_id' => $dungeonRoute->id,
            'level'            => 10,
            'success'          => true,
            'total_time_ms'    => 1000,
            'duplicate'        => false,
        ]);

        try {
            // Act
            $user->delete();

            // Assert - the route is gone, and so is everything hanging off it
            $this->assertDatabaseMissing('dungeon_routes', ['id' => $dungeonRoute->id]);
            $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $thumbnail->id]);
            $this->assertDatabaseMissing('files', ['id' => $file->id]);
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
            $this->assertDatabaseMissing(
                'challenge_mode_runs',
                ['id' => $challengeModeRun->id],
                'combatlog',
            );
        } finally {
            ChallengeModeRun::query()->where('id', $challengeModeRun->id)->delete();
            Tag::query()->where('id', $tag->id)->delete();
            DungeonRouteThumbnail::query()->where('id', $thumbnail->id)->delete();
            File::query()->where('id', $file->id)->delete();
            DungeonRoute::query()->where('id', $dungeonRoute->id)->delete();
            User::query()->where('id', $user->id)->delete();
        }
    }
}
