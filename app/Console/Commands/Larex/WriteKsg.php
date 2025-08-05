<?php

namespace App\Console\Commands\Larex;

use App\Console\Commands\Larex\Traits\CorrectsLocalizationsHeader;
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
    protected $description = 'Refreshes lang/localization.csv in such a way that it can be used by Keystone.guru';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // php artisan larex:import laravel-group --exclude=npcs,spells,view_admin
        $this->shell([
            'rm -f lang/localization.csv',
            'php artisan larex:import laravel-group --exclude=datatables,npcs,spells,view_admin',
        ]);

        if (!$this->correctLocalizationHeader()) {
            return 1;
        }

        // Fix the order of languages in the CSV file
        $this->shell([
            'php artisan larex:lang:order de en',
        ]);

        // We don't need the zh-TW and ho-HO languages in the CSV file
        $this->shell([
            'php artisan larex:lang:remove zh-TW',
            'php artisan larex:lang:remove ho-HO',
        ]);

        return 0;
    }
}
