<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('subcreation_ease_tier_pulls', 'affix_group_ease_tier_pulls');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('affix_group_ease_tier_pulls', 'subcreation_ease_tier_pulls');
    }
};
