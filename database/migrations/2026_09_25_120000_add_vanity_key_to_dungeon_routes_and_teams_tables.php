<?php

use App\Models\Patreon\PatreonBenefit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->string('vanity_key', 48)->nullable()->after('public_key');
            $table->unique('vanity_key', 'dungeon_routes_vanity_key_unique');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->string('vanity_key', 48)->nullable()->after('public_key');
            $table->unique('vanity_key', 'teams_vanity_key_unique');
        });

        // The seeder rebuilds this table from PatreonBenefit::ALL, but the benefit has to exist before the
        // next seed run for the new gate to grant anything at all
        DB::table('patreon_benefits')->insertOrIgnore([
            'id'   => PatreonBenefit::ALL[PatreonBenefit::CUSTOM_URLS],
            'name' => sprintf('patreonbenefits.%s', PatreonBenefit::CUSTOM_URLS),
            'key'  => PatreonBenefit::CUSTOM_URLS,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('patreon_benefits')
            ->where('key', PatreonBenefit::CUSTOM_URLS)
            ->delete();

        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropUnique('dungeon_routes_vanity_key_unique');
            $table->dropColumn('vanity_key');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique('teams_vanity_key_unique');
            $table->dropColumn('vanity_key');
        });
    }
};
