<?php

namespace App\Console\Commands\CombatLog;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogRouteEnemyFailureAnalysisServiceInterface;
use App\Service\CombatLog\Dtos\EnemyFailureAnalysis\EnemyFailureCluster;
use Illuminate\Console\Command;

/**
 * The "look here" rundown for a dungeon's Auto Route Creator enemy failures: every cluster of failures with a verdict on
 * what is most likely wrong with the mapping there, most urgent first. The same data the admin heatmap page draws as
 * markers - this is the form that goes into a GitHub issue.
 */
class AnalyzeEnemyFailures extends Command
{
    private const string FORMAT_TABLE = 'table';

    private const string FORMAT_MARKDOWN = 'markdown';

    private const string FORMAT_JSON = 'json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:analyzeenemyfailures
        {dungeon : Dungeon key}
        {--mapping-version= : Mapping version id to analyse (default: the dungeon\'s current one)}
        {--min-count= : Clusters with fewer failures are flagged low-volume (default from config)}
        {--hide-low-volume : Leave low-volume clusters out entirely}
        {--format=table : table, markdown or json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clusters and classifies the combat log route enemy failures of a dungeon into a ranked list of mapping locations to review.';

    public function handle(CombatLogRouteEnemyFailureAnalysisServiceInterface $analysisService): int
    {
        $dungeonKey = (string)$this->argument('dungeon');
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()->where('key', $dungeonKey)->first();
        if ($dungeon === null) {
            $this->error(sprintf('Unknown dungeon key "%s"', $dungeonKey));

            return self::FAILURE;
        }

        $mappingVersionId = $this->option('mapping-version');
        $mappingVersion   = $mappingVersionId === null
            ? $dungeon->getCurrentMappingVersion()
            : MappingVersion::query()->where('dungeon_id', $dungeon->id)->where('id', (int)$mappingVersionId)->first();
        if ($mappingVersion === null) {
            $this->error(sprintf('No mapping version %s for dungeon %s', $mappingVersionId ?? '(current)', $dungeonKey));

            return self::FAILURE;
        }

        $format = (string)$this->option('format');
        if (!in_array($format, [self::FORMAT_TABLE, self::FORMAT_MARKDOWN, self::FORMAT_JSON], true)) {
            $this->error(sprintf('Unknown format "%s" - use table, markdown or json', $format));

            return self::FAILURE;
        }

        $minCount = $this->option('min-count') === null ? null : (int)$this->option('min-count');
        $result   = $analysisService->analyze($dungeon, $mappingVersion, null, $minCount)->setUseFacade(false);

        $clusters = $this->option('hide-low-volume')
            ? array_values(array_filter($result->clusters, static fn(EnemyFailureCluster $cluster) => !$cluster->lowVolume))
            : $result->clusters;

        if ($format === self::FORMAT_JSON) {
            $this->line((string)json_encode($result->toArray(), JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $headers = ['#', 'Verdict', 'NPC', 'Floor', 'Failures', 'Routes', 'Avg/route', 'Nearest enemy', 'Dist (yd)', 'In range', 'Lat', 'Lng', 'Suggestion'];
        $rows    = [];
        foreach ($clusters as $index => $cluster) {
            $rows[] = [
                sprintf('%d%s', $index + 1, $cluster->lowVolume ? '*' : ''),
                $cluster->verdict->label(),
                sprintf('%s (%d)', $cluster->npcName, $cluster->npcId),
                $cluster->floorId,
                $cluster->count,
                $cluster->routeCount,
                number_format($cluster->avgFailuresPerRoute, 1),
                $cluster->nearestEnemyId ?? '-',
                $cluster->nearestEnemyDistance === null ? '-' : number_format($cluster->nearestEnemyDistance),
                $cluster->enemiesWithinRange,
                $cluster->centroid->getLat(1),
                $cluster->centroid->getLng(1),
                $cluster->suggestion,
            ];
        }

        $this->line(sprintf(
            '%s — mapping version %d: %d clusters (radius %d yd, low-volume below %d failures / %d routes, marked *), %d failures skipped',
            __($dungeon->name, [], 'en_US'),
            $mappingVersion->id,
            count($clusters),
            $result->clusterRadiusYd,
            $result->minCount,
            $result->minRoutes,
            $result->skippedCount,
        ));

        if ($format === self::FORMAT_MARKDOWN) {
            $this->line('');
            $this->line(sprintf('| %s |', implode(' | ', $headers)));
            $this->line(sprintf('|%s', str_repeat('---|', count($headers))));
            foreach ($rows as $row) {
                $this->line(sprintf('| %s |', implode(' | ', array_map(static fn($cell): string => str_replace('|', '\\|', (string)$cell), $row))));
            }
        } else {
            $this->table($headers, $rows);
        }

        return self::SUCCESS;
    }
}
