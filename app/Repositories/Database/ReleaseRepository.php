<?php

namespace App\Repositories\Database;

use App\Models\Release;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ReleaseRepository extends DatabaseRepository implements ReleaseRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Release::class);
    }

    public function getLatestUnreleasedRelease(): ?Release
    {
        return Release::where('released', false)->orderBy('id', 'desc')->first();
    }

    public function releaseSuccessful(): void
    {
        // ALL releases are marked as successful!
        /** @noinspection SqlWithoutWhere */
        DB::update('UPDATE `releases` SET `released` = 1');
    }
}
