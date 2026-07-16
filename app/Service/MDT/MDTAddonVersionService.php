<?php

namespace App\Service\MDT;

use App\Repositories\Interfaces\MDTAddonVersionRepositoryInterface;
use Carbon\CarbonInterface;

class MDTAddonVersionService implements MDTAddonVersionServiceInterface
{
    public function __construct(private readonly MDTAddonVersionRepositoryInterface $mdtAddonVersionRepository)
    {
    }

    public function getReleaseDate(int $addonVersion): ?CarbonInterface
    {
        return $this->mdtAddonVersionRepository->findReleaseDate($addonVersion);
    }

    public function getAddonVersionForDate(CarbonInterface $date): ?int
    {
        return $this->mdtAddonVersionRepository->findLatestAddonVersionAtDate($date);
    }

    public function getCurrentAddonVersion(): int
    {
        return self::versionStringToAddonVersion(config('keystoneguru.mdt.version'));
    }

    /**
     * Convert an MDT version string ("v6.1.20", "4.0.31.0") to the addonVersion integer MDT stamps
     * into export strings by stripping every non-digit character (mirrors MDT's `version:gsub("%.", "")`).
     */
    public static function versionStringToAddonVersion(string $version): int
    {
        return (int)preg_replace('/\D/', '', $version);
    }
}
