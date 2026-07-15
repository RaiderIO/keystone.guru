<?php

namespace App\Console\Commands\MDT;

use App\Models\Mapping\MappingVersion;
use App\Service\MDT\MDTAddonVersionService;
use App\Service\MDT\MDTAddonVersionServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SyncAddonVersions extends Command
{
    protected $signature = 'mdt:syncaddonversions {--refresh : Rebuild the addonVersion => release-date map from GitHub before backfilling}';

    protected $description = 'Backfill mapping_versions.mdt_addon_version from the MDT addonVersion => release-date map (optionally refreshing the map from GitHub first)';

    private const GITHUB_RELEASES_URL = 'https://api.github.com/repos/nnoggie/MythicDungeonTools/releases';

    private const RELATIVE_DATA_PATH = 'data/mdt/addon_versions.json';

    public function handle(MDTAddonVersionServiceInterface $mdtAddonVersionService): int
    {
        if ($this->option('refresh')) {
            $this->refreshMap();

            // The service caches the map on first read; resolve a fresh instance so the backfill sees the new file.
            $mdtAddonVersionService = app(MDTAddonVersionServiceInterface::class);
        }

        $this->backfillMappingVersions($mdtAddonVersionService);

        return self::SUCCESS;
    }

    /**
     * Rebuild database/data/mdt/addon_versions.json from the upstream GitHub releases. The addonVersion
     * integer is the release tag with every non-digit stripped (mirrors MDT's own encoding); when two
     * tags collapse to the same integer (a release and its own alpha/beta), the earliest date wins.
     */
    private function refreshMap(): void
    {
        $this->info('Refreshing addon version map from GitHub...');

        $releaseDates = [];
        for ($page = 1; ; $page++) {
            $response = Http::withHeaders(['Accept' => 'application/vnd.github+json'])
                ->get(self::GITHUB_RELEASES_URL, ['per_page' => 100, 'page' => $page]);

            if (!$response->successful()) {
                $this->error(sprintf('GitHub request failed (page %d): HTTP %d', $page, $response->status()));

                return;
            }

            /** @var array<int, array{tag_name: string, published_at: ?string}> $releases */
            $releases = $response->json();
            if (empty($releases)) {
                break;
            }

            foreach ($releases as $release) {
                $publishedAt = $release['published_at'] ?? null;
                if ($publishedAt === null) {
                    continue;
                }

                $addonVersion = MDTAddonVersionService::versionStringToAddonVersion($release['tag_name']);
                if ($addonVersion === 0) {
                    continue;
                }

                if (!isset($releaseDates[$addonVersion]) || $publishedAt < $releaseDates[$addonVersion]) {
                    $releaseDates[$addonVersion] = $publishedAt;
                }
            }
        }

        // Sort chronologically for a stable, human-readable diff.
        asort($releaseDates);

        $path = database_path(self::RELATIVE_DATA_PATH);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($releaseDates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        $this->info(sprintf('Wrote %d addon versions to %s', count($releaseDates), $path));
    }

    /**
     * Populate mdt_addon_version on mapping versions that predate the column, inferring the imported-from
     * MDT version from the release that was live when the mapping version was created.
     */
    private function backfillMappingVersions(MDTAddonVersionServiceInterface $mdtAddonVersionService): void
    {
        $updated = 0;
        $skipped = 0;

        MappingVersion::query()
            ->whereNull('mdt_addon_version')
            ->whereNotNull('created_at')
            ->chunkById(500, function ($mappingVersions) use ($mdtAddonVersionService, &$updated, &$skipped): void {
                foreach ($mappingVersions as $mappingVersion) {
                    $addonVersion = $mdtAddonVersionService->getAddonVersionForDate($mappingVersion->created_at);

                    if ($addonVersion === null) {
                        // Older than every known MDT release; leave NULL (selection falls back to created_at).
                        $skipped++;

                        continue;
                    }

                    $mappingVersion->update(['mdt_addon_version' => $addonVersion]);
                    $updated++;
                }
            });

        $this->info(sprintf('Backfilled %d mapping versions (%d left NULL, predating known releases).', $updated, $skipped));
    }
}
