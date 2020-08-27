<?php

namespace App\Console\Commands\Environment;

class UpdateStaging extends Update
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keystoneguru:update staging';

    protected $compileAs = 'dev';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        parent::handle();

        return 0;
    }
}
