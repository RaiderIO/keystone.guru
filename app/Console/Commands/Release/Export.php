<?php

namespace App\Console\Commands\Release;

use App\Models\Release;
use App\Models\ReleaseReportLog;
use App\Service\Discord\DiscordApiService;
use App\Service\Reddit\RedditApiService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class Export extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports all releases to the seeders folder.';

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
     *
     * @throws Exception
     */
    public function handle(): void
    {
        Release::each(function (Release $release) {
            file_put_contents(
                database_path(sprintf('seeders/releases/%s.json', $release->version)),
                json_encode($release->toArray(), JSON_PRETTY_PRINT)
            );
        }, 10);
    }
}
