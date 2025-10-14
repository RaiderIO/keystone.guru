<?php

namespace App\Repositories\Database;

use App\Models\Release;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReleaseRepository extends DatabaseRepository implements ReleaseRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Release::class);
    }

    public function getLatestUnreleasedRelease(): ?Release
    {
        // If released column exists
        if (Schema::hasColumn('releases', 'released')) {
            return Release::where('released', false)
                ->disableCache()
                ->orderBy('id', 'desc')
                ->first();
        } else {
            return null;
        }
    }

    public function releaseSuccessful(): void
    {
        // ALL releases are marked as successful!
        if (Schema::hasColumn('releases', 'released')) {
            /** @noinspection SqlWithoutWhere */
            DB::update('UPDATE `releases` SET `released` = 1');
        }
    }

    public function findReleaseByVersion(?string $version): ?Release
    {
        if ($version === null) {
            $release = Release::latest()->disableCache()->first();
        } else {
            if (!str_starts_with($version, 'v')) {
                $version = 'v' . $version;
            }

            /** @var Release $release */
            $release = Release::where('version', $version)->disableCache()->first();
        }

        return $release;
    }
}
