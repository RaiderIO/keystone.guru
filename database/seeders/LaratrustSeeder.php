<?php

namespace Database\Seeders;

use App\Permission;
use App\Role;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LaratrustSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return  void
     */
    public function run()
    {
        $this->command->info('Truncating User, Role and Permission tables');
        $this->truncateLaratrustTables();

        $config        = config('laratrust_seeder.roles_structure');
        $mapPermission = collect(config('laratrust_seeder.permissions_map'));

        foreach ($config as $key => $modules) {

            // Create a new role
            $role        = Role::firstOrCreate([
                'name'         => $key,
                'display_name' => ucwords(str_replace('_', ' ', $key)),
                'description'  => ucwords(str_replace('_', ' ', $key)),
            ]);
            $permissions = [];

            $this->command->info('Creating Role ' . strtoupper($key));

            // Reading role permission modules
            foreach ($modules as $module => $value) {

                foreach (explode(',', $value) as $p => $perm) {

                    $permissionValue = $mapPermission->get($perm);

                    $permissions[] = Permission::firstOrCreate([
                        'name'         => $permissionValue . '-' . $module,
                        'display_name' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                        'description'  => ucfirst($permissionValue) . ' ' . ucfirst($module),
                    ])->id;

                    $this->command->info('Creating Permission to ' . $permissionValue . ' for ' . $module);
                }
            }

            // Attach all permissions to the role
            $role->permissions()->sync($permissions);

            if (Config::get('laratrust_seeder.create_users')) {
                $this->command->info("Creating '{$key}' user");
                // Create default user for each role
                $user = User::create([
                    'name'       => ucwords(str_replace('_', ' ', $key)),
                    'public_key' => User::generateRandomPublicKey(),
                    'email'      => $key . '@app.com',
                    'password'   => bcrypt('password'),
                ]);
                $user->attachRole($role);
            }

        }
    }

    /**
     * Truncates all the laratrust tables and the users table
     *
     * @return    void
     */
    public function truncateLaratrustTables()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('permission_role')->truncate();
        DB::table('permission_user')->truncate();
        DB::table('role_user')->truncate();
        if (Config::get('laratrust_seeder.truncate_tables')) {
            Role::truncate();
            Permission::truncate();
        }
        if (Config::get('laratrust_seeder.truncate_tables') && Config::get('laratrust_seeder.create_users')) {
            User::truncate();
        }
        Schema::enableForeignKeyConstraints();
    }
}
