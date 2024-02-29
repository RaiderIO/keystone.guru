<?php

namespace App\Console\Commands\Release;

use App\Models\Release;
use Illuminate\Console\Command;

class GetCurrent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:current';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves information about the latest release in Keystone.guru';

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
     */
    public function handle(): void
    {
        $this->line(Release::latest()->first()->version);
    }
}
