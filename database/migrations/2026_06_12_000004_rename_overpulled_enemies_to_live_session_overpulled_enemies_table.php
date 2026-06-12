<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('overpulled_enemies', 'live_session_overpulled_enemies');
    }

    public function down(): void
    {
        Schema::rename('live_session_overpulled_enemies', 'overpulled_enemies');
    }
};
