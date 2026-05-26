<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('combat_log_parsing_criteria', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('combat_log_version')->unsigned();
            // Full class name (e.g. Dungeon::class) — no FK can be enforced due to polymorphism
            $table->string('model_class');
            $table->bigInteger('model_id')->unsigned();
            $table->date('date');
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('threshold')->default(100);

            $table->unique(['combat_log_version', 'model_class', 'model_id', 'date'], 'clpc_version_model_class_model_id_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combat_log_parsing_criteria');
    }
};
