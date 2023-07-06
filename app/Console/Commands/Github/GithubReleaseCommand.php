<?php


namespace App\Console\Commands\Github;

use App\Models\Release;
use Illuminate\Console\Command;

abstract class GithubReleaseCommand extends Command
{
    /**
     * @param string|null $version
     * @return Release|null
     */
    public function findReleaseByVersion(?string $version): ?Release
    {
        if ($version === null) {
            $release = Release::latest()->disableCache()->first();
        } else {
            if (substr($version, 0, 1) !== 'v') {
                $version = 'v' . $version;
            }

            /** @var Release $release */
            $release = Release::where('version', $version)->disableCache()->first();
        }

        return $release;
    }
}