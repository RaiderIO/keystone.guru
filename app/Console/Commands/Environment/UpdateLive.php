<?php

namespace App\Console\Commands\Environment;

class UpdateLive extends Update
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the environment for a live environment';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:live';

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
