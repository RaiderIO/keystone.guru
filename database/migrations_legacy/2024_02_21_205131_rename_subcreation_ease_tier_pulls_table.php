<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('subcreation_ease_tier_pulls', 'affix_group_ease_tier_pulls');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('affix_group_ease_tier_pulls', 'subcreation_ease_tier_pulls');
    }
};
