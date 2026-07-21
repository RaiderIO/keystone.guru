<?php

namespace App\Repositories\Database;

use App\Models\MDTAddonVersion;
use App\Repositories\Interfaces\MDTAddonVersionRepositoryInterface;
use Carbon\CarbonInterface;

class MDTAddonVersionRepository extends DatabaseRepository implements MDTAddonVersionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MDTAddonVersion::class);
    }

    public function findReleaseDate(int $addonVersion): ?CarbonInterface
    {
        /** @var MDTAddonVersion|null $mdtAddonVersion */
        $mdtAddonVersion = MDTAddonVersion::query()->find($addonVersion, ['released_at']);

        return $mdtAddonVersion?->released_at;
    }

    public function findLatestAddonVersionAtDate(CarbonInterface $date): ?int
    {
        /** @var MDTAddonVersion|null $mdtAddonVersion */
        $mdtAddonVersion = MDTAddonVersion::query()
            ->where('released_at', '<=', $date)
            ->orderByDesc('released_at')
            ->first(['addon_version']);

        return $mdtAddonVersion?->addon_version;
    }
}
