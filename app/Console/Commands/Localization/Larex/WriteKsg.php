<?php

namespace App\Console\Commands\Localization\Larex;

use App\Console\Commands\Localization\Larex\Traits\CorrectsLocalizationsHeader;
use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class WriteKsg extends Command
{
    use ExecutesShellCommands;
    use CorrectsLocalizationsHeader;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larex:write-ksg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes lang/localization.csv in such a way that it can be used by Crowdin. This will exclude certain groups from being written to localization.csv/uploaded to Crowdin.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // php artisan larex:import laravel-group --exclude=npcs,spells,view_admin
        $this->shell([
            'rm -f lang/localization.csv',
            'php artisan larex:import laravel-group --exclude=datatables,dungeons,npcs,spells,view_admin,validation',
        ]);

        if (!$this->convertLocalizationHeaderToCrowdinFormat()) {
            return 1;
        }

        // Fix the order of languages in the CSV file
        $this->shell([
            'php artisan larex:lang:order de en',
        ]);

        $crowdinToKsgMapping = array_flip(self::KSG_TO_CROWDIN_MAPPING);
        foreach (config('language.all') as $lang) {
            if (!isset($lang['crowdin']) || $lang['crowdin']) {
                continue;
            }

            $kebabLang = str_replace('_', '-', $lang['long']);

            $this->shell([
                sprintf('php artisan larex:lang:remove %s', $crowdinToKsgMapping[$kebabLang] ?? $kebabLang),
            ]);
        }

        return 0;
    }
}
