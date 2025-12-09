<?php

namespace App\Console\Commands\Hotfix;
use App\Models\Release;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class MakeHotfix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:hotfix {version? : The version to create a hotfix for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a hotfix by collecting changed files and uploading them to S3';

    /**
     * Execute the console command.
     */
    public function handle(
        ReleaseRepositoryInterface $releaseRepository,
    ): int {
        $version = $this->argument('version');

        /** @var Release|null $release */
        $release = $releaseRepository->findReleaseByVersion($version);

        if ($release === null) {
            $this->error('Release not found!');

            return self::FAILURE;
        }

        $this->info("Creating hotfix for release: {$release->version}");

        try {
            // Get changed files from Git
            $changedFiles = $this->getChangedFiles();

            if (empty($changedFiles)) {
                $this->warn('No changed files found!');

                return self::SUCCESS;
            }

            $this->info('Found ' . count($changedFiles) . ' changed file(s):');
            foreach ($changedFiles as $file) {
                $this->line("  - {$file}");
            }

            // Upload files to S3
            $this->uploadFilesToS3($changedFiles, $release->version);

            $this->info("Hotfix created successfully for version {$release->version}!");

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error creating hotfix: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Get the list of changed files from Git
     *
     * @return array
     * @throws Exception
     */
    private function getChangedFiles(): array
    {
        // Get both staged and unstaged changes
        $result = Process::run('git diff --name-only HEAD');

        if ($result->failed()) {
            throw new Exception('Failed to get changed files from Git: ' . $result->errorOutput());
        }

        $files = array_filter(explode("\n", trim($result->output())));

        // Filter out only existing files
        return array_filter($files, fn($file) => file_exists(base_path($file)));
    }

    /**
     * Upload files to S3
     *
     * @param  array     $files
     * @param  string    $version
     * @return void
     * @throws Exception
     */
    private function uploadFilesToS3(array $files, string $version): void
    {
        $disk = Storage::disk('s3_hotfixes');

        $this->info('Uploading files to S3...');

        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $localPath = base_path($file);
            $s3Path    = "$version/$file";

            $contents = file_get_contents($localPath);

            if ($contents === false) {
                $progressBar->finish();
                $this->newLine();

                throw new Exception("Failed to read file: {$localPath}");
            }

            $disk->put($s3Path, $contents);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }
}
