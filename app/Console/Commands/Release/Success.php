<?php

namespace App\Console\Commands\Release;

use App\Models\Release;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;
use App\Traits\SavesArrayToJsonFile;
use Illuminate\Console\Command;

class Success extends Command
{
    use SavesArrayToJsonFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:success';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reports that the release has been successful';

    /**
     * Execute the console command.
     */
    public function handle(ReleaseRepositoryInterface $releaseRepository): int
    {
        $releaseRepository->releaseSuccessful();

        return 0;
    }
}
