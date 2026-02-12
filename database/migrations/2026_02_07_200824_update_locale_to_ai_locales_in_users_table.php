<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const array LOCALE_MAPPING = [
        'de_DE' => 'de_DE_ai',
        'es_ES' => 'es_ES_ai',
        'es_MX' => 'es_MX_ai',
        'fr_FR' => 'fr_FR_ai',
        'it_IT' => 'it_IT_ai',
        'ko_KR' => 'ko_KR_ai',
        'pt_BR' => 'pt_BR_ai',
        'ru_RU' => 'ru_RU_ai',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::LOCALE_MAPPING as $locale => $aiLocale) {
            DB::update("UPDATE users SET locale = '$aiLocale' WHERE locale = '$locale'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_flip(self::LOCALE_MAPPING) as $aiLocale => $locale) {
            DB::update("UPDATE users SET locale = '$locale' WHERE locale = '$aiLocale'");
        }
    }
};
