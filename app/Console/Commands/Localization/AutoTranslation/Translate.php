<?php

namespace App\Console\Commands\Localization\AutoTranslation;

use App\Console\Commands\Localization\Traits\ExportsTranslations;
use Illuminate\Console\Command;
use VildanBina\LaravelAutoTranslation\Services\TranslationEngineService;
use VildanBina\LaravelAutoTranslation\TranslationWorkflowService;

class Translate extends Command
{
    use ExportsTranslations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:ksg {targetLang} {driver=openai}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads lang/texts_to_translate.json and translates them using the given OpenAI model.';

    /**
     * Execute the console command.
     */
    public function handle(
        TranslationEngineService $translationEngineService,
    ): int {
        if (!file_exists(base_path('lang/texts_to_translate.json'))) {
            $this->error('lang/localization.csv does not exist - run this first: php artisan translate:scan --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation');

            return 1;
        }

        $targetLang = $this->argument('targetLang');
        $driver     = $this->argument('driver');

        $texts = json_decode(file_get_contents(base_path('lang/texts_to_translate.json')), true);

        $validKeys = [
            //            'affixes',
            //            'characteristics',
            //            'classes',
        ];
        if (!empty($validKeys)) {
            $validTexts = [];
            foreach ($texts as $key => $text) {
                if (in_array(explode('.', $key)[0], $validKeys)) {
                    $validTexts[$key] = $text;
                }
            }
        } else {
            $validTexts = $texts;
        }

        $translationWorkflowService = new TranslationWorkflowService($translationEngineService);
        $translationWorkflowService->setInMemoryTexts($validTexts);

        $translatedTexts = $translationWorkflowService->translate(
            config('auto-translations.source_language'),
            $targetLang,
            $driver,
        );

        $groupedTexts = $this->dotToNestedArray($translatedTexts);

        foreach ($groupedTexts as $filename => $texts) {
            if (!$this->exportTranslations($targetLang, $filename, $texts)) {
                return -1;
            }
        }

        return 0;
    }

    /**
     * Convert an array of dotted keys => values into a nested array structure.
     *
     * @param  array $flat ['a.b.c' => 'value', ...]
     * @return array
     */
    private function dotToNestedArray(array $flat): array
    {
        $nested = [];

        foreach ($flat as $dottedKey => $value) {
            $parts = explode('.', $dottedKey);
            $this->setNestedValue($nested, $parts, $value);
        }

        return $nested;
    }

    /**
     * Recursively walks the target array and assigns the value at the correct depth.
     *
     * @param array $array Reference to the array being built
     * @param array $keys  Remaining keys
     * @param mixed $value Value to set
     */
    private function setNestedValue(array &$array, array $keys, mixed $value): void
    {
        $key = array_shift($keys);

        if ($keys === []) {
            // Last level → assign the actual value
            $array[$key] = $value;

            return;
        }

        // Ensure intermediate level exists
        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }

        $this->setNestedValue($array[$key], $keys, $value);
    }
}
