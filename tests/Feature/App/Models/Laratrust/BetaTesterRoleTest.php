<?php

namespace Tests\Feature\App\Models\Laratrust;

use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Laratrust')]
#[Group('BetaTesterRole')]
final class BetaTesterRoleTest extends PublicTestCase
{
    private const string MIGRATION = 'database/migrations/2026_10_25_000000_add_beta_tester_role.php';

    #[Test]
    public function betaTesterRole_exists_withInternalTeamsPermissions(): void
    {
        // Act
        /** @var Role|null $betaTester */
        $betaTester = Role::query()->where('name', Role::ROLE_BETA_TESTER)->first();
        /** @var Role $internalTeam */
        $internalTeam = Role::query()->where('name', Role::ROLE_INTERNAL_TEAM)->firstOrFail();

        // Assert
        $this->assertNotNull($betaTester);
        $this->assertNotEmpty($internalTeam->permissions);
        $this->assertEqualsCanonicalizing(
            $internalTeam->permissions->pluck('name')->all(),
            $betaTester->permissions->pluck('name')->all(),
        );
    }

    #[Test]
    public function up_givenRoleMissing_createsRoleWithInternalTeamsPermissions(): void
    {
        // Arrange
        $migration          = require base_path(self::MIGRATION);
        $originalRole       = DB::table('roles')->where('name', Role::ROLE_BETA_TESTER)->first();
        $originalRoleId     = (int)$originalRole->id;
        $originalPermIds    = DB::table('permission_role')->where('role_id', $originalRoleId)->pluck('permission_id')->all();
        $internalTeamRoleId = (int)DB::table('roles')->where('name', Role::ROLE_INTERNAL_TEAM)->value('id');
        $expectedPermIds    = DB::table('permission_role')->where('role_id', $internalTeamRoleId)->pluck('permission_id')->all();

        DB::table('permission_role')->where('role_id', $originalRoleId)->delete();
        DB::table('roles')->where('id', $originalRoleId)->delete();
        $newRoleId = null;

        try {
            // Act
            $migration->up();

            // Assert
            $newRoleId = (int)DB::table('roles')->where('name', Role::ROLE_BETA_TESTER)->value('id');
            $this->assertGreaterThan(0, $newRoleId);
            $this->assertEqualsCanonicalizing(
                $expectedPermIds,
                DB::table('permission_role')->where('role_id', $newRoleId)->pluck('permission_id')->all(),
            );
        } finally {
            DB::table('permission_role')->where('role_id', $newRoleId ?? $originalRoleId)->delete();
            DB::table('roles')->where('name', Role::ROLE_BETA_TESTER)->delete();
            DB::table('roles')->insert((array)$originalRole);
            DB::table('permission_role')->insert(array_map(
                static fn($permissionId): array => ['permission_id' => $permissionId, 'role_id' => $originalRoleId],
                $originalPermIds,
            ));
        }
    }

    #[Test]
    public function up_givenRoleAlreadyExists_isIdempotent(): void
    {
        // Arrange
        $migration = require base_path(self::MIGRATION);
        $roleId    = (int)DB::table('roles')->where('name', Role::ROLE_BETA_TESTER)->value('id');
        $before    = DB::table('permission_role')->where('role_id', $roleId)->count();

        // Act
        try {
            $migration->up();
        } catch (QueryException $exception) {
            $this->fail(sprintf('Re-running the migration must not re-insert rows: %s', $exception->getMessage()));
        }

        // Assert
        $this->assertSame(1, DB::table('roles')->where('name', Role::ROLE_BETA_TESTER)->count());
        $this->assertGreaterThan(0, $before);
        $this->assertSame($before, DB::table('permission_role')->where('role_id', $roleId)->count());
    }

    #[Test]
    public function hasRole_givenBetaTester_countsAsEarlyAccessButNotAsInternal(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_BETA_TESTER);

            // Act & Assert
            $this->assertTrue($user->hasRole(Role::ROLES_EARLY_ACCESS));
            $this->assertTrue($user->hasRole(Role::ROLE_ALL));
            $this->assertFalse($user->hasRole(Role::ROLES_INTERNAL));
            $this->assertFalse($user->hasRole(Role::ROLE_ADMIN));
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function hasRole_givenInternalTeam_countsAsEarlyAccess(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_INTERNAL_TEAM);

            // Act & Assert
            $this->assertTrue($user->hasRole(Role::ROLES_EARLY_ACCESS));
            $this->assertTrue($user->hasRole(Role::ROLES_INTERNAL));
        } finally {
            $user->delete();
        }
    }
}
