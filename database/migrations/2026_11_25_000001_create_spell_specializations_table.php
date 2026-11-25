<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('spell_specializations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spell_id');
            $table->unsignedInteger('character_class_specialization_id');

            $table->unique(['spell_id', 'character_class_specialization_id'], 'spell_specializations_spell_spec_unique');
            $table->index('character_class_specialization_id', 'spell_specializations_spec_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spell_specializations');
    }
};
