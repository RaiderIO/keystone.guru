<?php

namespace App\Console\Commands\Localization\Larex;

use App\Console\Commands\Localization\Larex\Traits\CorrectsLocalizationsHeader;
use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class WriteKsgFromCrowdin extends Command
{
    use ExecutesShellCommands;
    use CorrectsLocalizationsHeader;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larex:write-ksg-from-crowdin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes lang/localization.csv and uses Crowdin as a source';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // php artisan larex:import laravel-group --exclude=npcs,spells,view_admin
        $this->shell([
            'rm -f lang/localization.csv',
            'php artisan larex:import crowdin',
        ]);

        if (!$this->convertLocalizationHeaderToCrowdinFormat()) {
            return 1;
        }

//        // Fix the order of languages in the CSV file
//        $this->shell([
//            'php artisan larex:lang:order de en',
//        ]);
//
//        // We don't need the zh-TW and ho-HO languages in the CSV file
//        $this->shell([
//            'php artisan larex:lang:remove zh-TW',
//            'php artisan larex:lang:remove ho-HO',
//        ]);

        return 0;
    }
}
