<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * users.changed_username is a NOT NULL flag with no default, so an insert that omits it only
     * succeeds because the connection is non-strict and MySQL substitutes 0. The `migrate`
     * connection is strict, so the same insert dies there with "Field 'changed_username' doesn't
     * have a default value" - which is what LaratrustSeeder does (#4498).
     *
     * A raw ALTER is used rather than ->change(): Laravel's change() restates the whole column
     * definition and silently drops any attribute not repeated, while this only attaches the
     * default.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `users` ALTER COLUMN `changed_username` SET DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `users` ALTER COLUMN `changed_username` DROP DEFAULT');
    }
};
