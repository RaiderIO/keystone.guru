<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('walk_graphs', function (Blueprint $table) {
            $table->id();
            $table->integer('ui_map_id')->unique();
            $table->float('cell_size');
            $table->integer('run_count');
            $table->integer('node_count');
            $table->integer('edge_count');
            $table->integer('largest_component_node_count');
            $table->binary('edges');
            $table->timestamp('built_at');
            $table->timestamps();
        });

        // A plain binary column is a 64 KB BLOB
        DB::connection($this->getConnection())->statement('ALTER TABLE walk_graphs MODIFY edges MEDIUMBLOB NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('walk_graphs');
    }
};
