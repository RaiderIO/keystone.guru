<?php

namespace Tests\Feature\App\Models\Laratrust;

use App\Models\Laratrust\Permission;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The ai_agent role (#4227) exists with internal_team's permissions - via the migration on staging/production, via
 * LaratrustSeeder's config locally and in CI - and its migration can be re-run without side effects.
 */
#[Group('Laratrust')]
#[Group('AiAgentRole')]
final class AiAgentRoleTest extends PublicTestCase
{
    #[Test]
    public function aiAgentRole_exists_withInternalTeamsPermissions(): void
    {
        // Act
        /** @var Role|null $aiAgent */
        $aiAgent = Role::query()->where('name', Role::ROLE_AI_AGENT)->first();
        /** @var Role $internalTeam */
        $internalTeam = Role::query()->where('name', Role::ROLE_INTERNAL_TEAM)->firstOrFail();

        // Assert
        $this->assertNotNull($aiAgent, 'The ai_agent role must exist (migration 2026_08_21_120000_add_ai_agent_role / LaratrustSeeder).');
        $this->assertEqualsCanonicalizing(
            $internalTeam->permissions->pluck('name')->all(),
            $aiAgent->permissions->pluck('name')->all(),
        );
    }

    #[Test]
    public function addAiAgentRoleMigration_givenRoleAlreadyExists_isIdempotent(): void
    {
        // Arrange
        $migration = require base_path('database/migrations/2026_08_21_120000_add_ai_agent_role.php');
        $roleId    = (int)DB::table('roles')->where('name', Role::ROLE_AI_AGENT)->value('id');
        $before    = DB::table('permission_role')->where('role_id', $roleId)->count();

        // Act
        $migration->up();

        // Assert — still one role, still the same permission rows
        $this->assertSame(1, DB::table('roles')->where('name', Role::ROLE_AI_AGENT)->count());
        $this->assertSame($before, DB::table('permission_role')->where('role_id', $roleId)->count());
        $this->assertGreaterThan(0, Permission::query()->count(), 'Sanity: permissions are seeded');
    }

    #[Test]
    public function hasRole_givenAiAgentUser_countsAsInternalButNotAsAdmin(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_AI_AGENT);

            // Assert
            $this->assertTrue($user->hasRole(Role::roles(Role::ROLES_INTERNAL)));
            $this->assertTrue($user->hasRole(Role::ROLE_ALL));
            $this->assertFalse($user->hasRole(Role::ROLE_ADMIN));
        } finally {
            $user->delete();
        }
    }
}
