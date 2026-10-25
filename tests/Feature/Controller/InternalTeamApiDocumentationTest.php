<?php

namespace Tests\Feature\Controller;

use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('BetaTesterRole')]
final class InternalTeamApiDocumentationTest extends PublicTestCase
{
    #[Test]
    public function api_givenInternalTeam_isAllowed(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_INTERNAL_TEAM);

            // Act
            $response = $this->actingAs($user)->get(route('l5-swagger.internal_team.api'));

            // Assert
            $response->assertOk();
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function api_givenBetaTester_isForbidden(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_BETA_TESTER);

            // Act
            $response = $this->actingAs($user)->get(route('l5-swagger.internal_team.api'));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function footer_givenInternalTeam_linksTheInternalDocumentation(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_INTERNAL_TEAM);

            // Act
            $response = $this->actingAs($user)->get(route('misc.about'));

            // Assert
            $response->assertOk();
            $response->assertSee(route('l5-swagger.internal_team.api'), false);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function footer_givenBetaTester_omitsTheInternalDocumentation(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_BETA_TESTER);

            // Act
            $response = $this->actingAs($user)->get(route('misc.about'));

            // Assert
            $response->assertOk();
            $response->assertSee(route('l5-swagger.public.api'), false);
            $response->assertDontSee(route('l5-swagger.internal_team.api'), false);
        } finally {
            $user->delete();
        }
    }
}
