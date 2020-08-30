<?php

namespace App\Console\Commands\Environment;

class UpdateLocal extends Update
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:local';

    protected $compile = false;

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
