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
        Schema::create('spell_description_translations', function (Blueprint $table) {
            // The same description as `spells`.`description_format`/`description_values`, rendered from
            // the game client's data for one non-English locale. English stays on the spell itself:
            // every other consumer of a description - calibration, the tuning diff, the seeder round
            // trip - reads it from there, and a row per locale per spell is data enough as it is.
            $table->increments('id');
            $table->unsignedInteger('spell_id');
            // The game client's own locale code, e.g. `deDE`; several of our locales map onto one of
            // these, so this is not one of ours ({@see App\Service\WagoTools\GameLocale}).
            $table->string('locale', 8);
            $table->text('description_format');
            $table->json('description_values')->nullable();

            $table->unique(['spell_id', 'locale'], 'spell_description_translations_spell_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spell_description_translations');
    }
};
