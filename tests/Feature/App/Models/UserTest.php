<?php

namespace Tests\Feature\App\Models;

use App\Models\Laratrust\Role;
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
}
