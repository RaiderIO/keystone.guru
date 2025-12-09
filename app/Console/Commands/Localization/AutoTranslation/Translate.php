<?php

namespace App\Console\Commands\Localization\AutoTranslation;

use App\Console\Commands\Localization\Traits\ExportsTranslations;
use Arr;
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
                if (in_array(explode('.', (string)$key)[0], $validKeys)) {
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

        $groupedTexts = Arr::undot($translatedTexts);

        foreach ($groupedTexts as $filename => $texts) {
            if (!$this->exportTranslations($targetLang, $filename, $texts, true)) {
                return -1;
            }
        }

        return 0;
    }
}
