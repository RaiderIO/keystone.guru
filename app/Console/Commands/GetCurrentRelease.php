<?php

namespace App\Console\Commands;

use App\Models\Release;
use Illuminate\Console\Command;

class GetCurrentRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keystoneguru:release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves information about releases in Keystone.guru';

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
     * @return mixed
     */
    public function handle()
    {
        $this->line(Release::latest()->get()->first()->version);
    }
}
