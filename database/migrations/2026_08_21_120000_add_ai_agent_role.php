<?php

use App\Models\Laratrust\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the ai_agent role (#4227) with exactly internal_team's permissions. Laratrust's tables are only ever seeded by
 * LaratrustSeeder, which refuses to run outside local environments - so a new role has to arrive through a migration to
 * exist on staging/production. Idempotent: re-running it neither duplicates the role nor its permissions.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $roleId = DB::table('roles')->where('name', Role::ROLE_AI_AGENT)->value('id');
        if ($roleId === null) {
            $roleId = DB::table('roles')->insertGetId([
                'name'         => Role::ROLE_AI_AGENT,
                'display_name' => 'Ai Agent',
                'description'  => 'Ai Agent',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // Copy internal_team's permissions - an environment without internal_team (or without permissions) just gets
        // the role, which is all the api_role middleware needs.
        $internalTeamRoleId = DB::table('roles')->where('name', Role::ROLE_INTERNAL_TEAM)->value('id');
        if ($internalTeamRoleId === null) {
            return;
        }

        $permissionIds = DB::table('permission_role')->where('role_id', $internalTeamRoleId)->pluck('permission_id');
        $existing      = DB::table('permission_role')->where('role_id', $roleId)->pluck('permission_id');

        $rows = $permissionIds->diff($existing)
            ->map(static fn($permissionId): array => ['permission_id' => $permissionId, 'role_id' => $roleId])
            ->values()
            ->all();
        if (!empty($rows)) {
            DB::table('permission_role')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Deliberately leaves the role, its permissions and its user assignments in place: the role is purely additive
     * (older code simply never checks for it), while deleting role_user rows on a rollback would silently strip every
     * agent account's access with no way to get it back by migrating forward again.
     */
    public function down(): void
    {
        // Intentionally a no-op - see above
    }
};
