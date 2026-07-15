<?php

namespace App\Service\MDT;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\File;

class MDTAddonVersionService implements MDTAddonVersionServiceInterface
{
    private const RELATIVE_DATA_PATH = 'data/mdt/addon_versions.json';

    /** @var array<int, CarbonInterface>|null Lazily-loaded map of addonVersion => release date. */
    private ?array $releaseDates = null;

    public function getReleaseDate(int $addonVersion): ?CarbonInterface
    {
        return $this->getReleaseDates()[$addonVersion] ?? null;
    }

    public function getAddonVersionForDate(CarbonInterface $date): ?int
    {
        $bestAddonVersion = null;
        $bestReleaseDate  = null;

        foreach ($this->getReleaseDates() as $addonVersion => $releaseDate) {
            if ($releaseDate->lessThanOrEqualTo($date) &&
                ($bestReleaseDate === null || $releaseDate->greaterThan($bestReleaseDate))) {
                $bestAddonVersion = $addonVersion;
                $bestReleaseDate  = $releaseDate;
            }
        }

        return $bestAddonVersion;
    }

    public function getCurrentAddonVersion(): int
    {
        return self::versionStringToAddonVersion(config('keystoneguru.mdt.version'));
    }

    /**
     * @return array<int, CarbonInterface>
     */
    private function getReleaseDates(): array
    {
        if ($this->releaseDates === null) {
            $this->releaseDates = [];

            $path = database_path(self::RELATIVE_DATA_PATH);
            if (File::exists($path)) {
                /** @var array<string, string> $decoded */
                $decoded = json_decode(File::get($path), true) ?? [];
                foreach ($decoded as $addonVersion => $publishedAt) {
                    $this->releaseDates[(int)$addonVersion] = Carbon::parse($publishedAt);
                }
            }
        }

        return $this->releaseDates;
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
