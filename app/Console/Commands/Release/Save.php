<?php

namespace App\Console\Commands\Release;

use App\Models\Release;
use App\Traits\SavesArrayToJsonFile;
use Illuminate\Console\Command;

class Save extends Command
{
    use SavesArrayToJsonFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Saves the current releases to file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $rootDirPath = database_path('/seeders/releases/');

        foreach (Release::all() as $release) {
            $release->makeHidden(['reddit_body', 'discord_body', 'github_body']);
            $releaseArr = $release->toArray();

            /** @var $release Release */
            $this->saveDataToJsonFile($releaseArr, $rootDirPath, sprintf('%s.json', $release->version));
        }

        return 0;
    }
}
