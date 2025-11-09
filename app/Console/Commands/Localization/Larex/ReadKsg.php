<?php

namespace App\Console\Commands\Localization\Larex;

use App\Console\Commands\Localization\Larex\Traits\CorrectsLocalizationsHeader;
use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class ReadKsg extends Command
{
    use ExecutesShellCommands;
    use CorrectsLocalizationsHeader;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larex:read-ksg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads lang/localization.csv in such a way that it can be used by Keystone.guru. A fetched localization.csv file will need to have its header corrected manually before it can be used by Keystone.guru.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!file_exists(base_path('lang/localization.csv'))) {
            $this->error('lang/localization.csv does not exist - run this first: larex:write-ksg-from-crowdin');

            return 1;
        }

        if (!$this->convertLocalizationHeaderToKsgFormat()) {
            return 1;
        }

        $this->shell([
            'php artisan larex:export',
            './vendor/bin/php-cs-fixer fix lang'
        ]);

        return 0;
    }
}
