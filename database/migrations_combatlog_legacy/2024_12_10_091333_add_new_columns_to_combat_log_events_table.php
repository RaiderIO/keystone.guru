<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('combat_log_events', function (Blueprint $table) {
            $table->integer('wow_instance_id')->after('run_id');
            $table->string('realm_type')->after('run_id');
            $table->integer('region_id')->after('run_id');
            $table->string('season')->after('run_id');
            $table->integer('period')->after('run_id');
            $table->integer('logged_run_id')->after('run_id');
            $table->integer('keystone_run_id')->after('run_id');

            $table->integer('num_deaths')->after('duration_ms');
            $table->double('timer_fraction')->after('duration_ms');
            $table->integer('par_time_ms')->after('duration_ms');

            $table->integer('num_members')->after('pos_enemy_y');
            $table->double('average_item_level')->after('pos_enemy_y');

            // Add some indices to help searching - but not too much since we're inserting a lot of data
            $table->index('challenge_mode_id');
            $table->index('ui_map_id');
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combat_log_events', function (Blueprint $table) {
            $table->dropColumn('wow_instance_id');
            $table->dropColumn('realm_type');
            $table->dropColumn('region_id');
            $table->dropColumn('season');
            $table->dropColumn('period');
            $table->dropColumn('logged_run_id');
            $table->dropColumn('keystone_run_id');

            $table->dropColumn('num_deaths');
            $table->dropColumn('timer_fraction');
            $table->dropColumn('par_time_ms');

            $table->dropColumn('num_members');
            $table->dropColumn('average_item_level');

            $table->dropIndex('challenge_mode_id');
            $table->dropIndex('ui_map_id');
            $table->dropIndex('event_type');
        });
    }
};
