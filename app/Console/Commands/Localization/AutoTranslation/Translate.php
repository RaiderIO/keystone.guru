<?php

namespace App\Console\Commands\Localization\AutoTranslation;

use App\Console\Commands\Localization\Larex\Traits\CorrectsLocalizationsHeader;
use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;
use VildanBina\LaravelAutoTranslation\Services\TranslationEngineService;
use VildanBina\LaravelAutoTranslation\TranslationWorkflowService;

class Translate extends Command
{
    use ExecutesShellCommands;
    use CorrectsLocalizationsHeader;

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
    ): int
    {
        if (!file_exists(base_path('lang/texts_to_translate.json'))) {
            $this->error('lang/localization.csv does not exist - run this first: php artisan translate:scan --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation');

            return 1;
        }

        $texts = json_decode(file_get_contents(base_path('lang/texts_to_translate.json')), true);

        $validKeys  = [
            'affixes',
            'characteristics',
            'classes',
        ];
        $validTexts = [];
        foreach ($texts as $key => $text) {
            if (in_array(explode('.', $key)[0], $validKeys)) {
                $validTexts[$key] = $text;
            }
        }

        $translationWorkflowService = new TranslationWorkflowService($translationEngineService);
        $translationWorkflowService->setInMemoryTexts($validTexts);

//        dd([
//            config('auto-translations.source_language'),
//            $this->argument('targetLang'),
//            $this->argument('driver'),
//        ]);
        dump($validTexts);

        $translatedTexts = $translationWorkflowService->translate(
            config('auto-translations.source_language'),
            $this->argument('targetLang'),
            $this->argument('driver'),
        );
        dd($translatedTexts);

        return 0;
    }
}
