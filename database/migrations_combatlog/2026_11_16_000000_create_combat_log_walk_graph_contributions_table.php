<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_walk_graph_contributions', function (Blueprint $table) {
            $table->id();
            $table->integer('ui_map_id');
            $table->string('run_key');
            $table->string('combat_log_path');
            $table->integer('edge_count');
            $table->binary('edges');
            $table->timestamps();

            $table->index(['ui_map_id', 'created_at'], 'clwgc_ui_map_created_index');
            $table->index(['ui_map_id', 'run_key'], 'clwgc_ui_map_run_index');
        });

        // A plain binary column is a 64 KB BLOB
        DB::connection($this->getConnection())->statement('ALTER TABLE combat_log_walk_graph_contributions MODIFY edges MEDIUMBLOB NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_walk_graph_contributions');
    }
};
