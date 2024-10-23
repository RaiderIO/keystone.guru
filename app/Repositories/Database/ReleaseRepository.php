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
}
