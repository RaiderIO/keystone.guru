<?php

namespace App\Console\Commands\Release;

use App\Models\Release;
use Exception;
use Illuminate\Console\Command;

class GetBody extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:body {version} {platform=github}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves the body of a platform from a release';

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
        $version  = $this->argument('version');
        $platform = $this->argument('platform');

        if (!str_starts_with($version, 'v')) {
            $version = 'v' . $version;
        }

        /** @var Release $release */
        $release = Release::where('version', $version)->first();

        match ($platform) {
            'github' => $this->line($release->github_body),
            'reddit' => $this->line($release->reddit_body),
            'discord' => $this->line($release->discord_body),
            default => throw new Exception(sprintf('Unsupport platform %s', $platform)),
        };
    }
}
