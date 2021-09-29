<?php

namespace App\Console\Commands\Handlebars;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class Refresh extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handlebars:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes the handlebars compiled file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->shell([
            sprintf(
                'handlebars %s resources/assets/js/handlebars/ -f resources/assets/js/handlebars.js',
                config('app.env') === 'production' ? '-m' : ''
            ),
        ]);

        $this->info('Handlebars refreshed');

        return 0;
    }
}
