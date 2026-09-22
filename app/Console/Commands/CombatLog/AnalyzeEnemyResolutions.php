<?php

namespace App\Console\Commands\CombatLog;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogRouteEnemyResolutionAnalysisServiceInterface;
use App\Service\CombatLog\Dtos\EnemyResolutionAnalysis\EnemyResolutionGroup;
use Illuminate\Console\Command;

/**
 * The "move this pack" rundown for a dungeon's long Auto Route Creator enemy resolutions: every mapped pack the builder
 * keeps matching from far away, with a verdict on whether it is mapped in the wrong place, most actionable first. The
 * same data the admin distance heatmap page draws as arrows - this is the form that goes into a GitHub issue.
 */
class AnalyzeEnemyResolutions extends Command
{
    private const string FORMAT_TABLE = 'table';

    private const string FORMAT_MARKDOWN = 'markdown';

    private const string FORMAT_JSON = 'json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:analyzeenemyresolutions
        {dungeon : Dungeon key}
        {--mapping-version= : Mapping version id to analyse (default: the dungeon\'s current one)}
        {--min-distance= : Only resolutions at least this far off, on the kill priority weighted distance}
        {--hide-low-volume : Leave low-volume groups out entirely}
        {--format=table : table, markdown or json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Groups and classifies the long combat log route enemy resolutions of a dungeon per mapped pack into a ranked list of packs to review.';

    public function handle(CombatLogRouteEnemyResolutionAnalysisServiceInterface $analysisService): int
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

        $minDistance = $this->option('min-distance') === null ? null : (float)$this->option('min-distance');
        $result      = $analysisService->analyze($dungeon, $mappingVersion, null, $minDistance)->setUseFacade(false);

        $groups = $this->option('hide-low-volume')
            ? array_values(array_filter($result->groups, static fn(EnemyResolutionGroup $group) => !$group->lowVolume))
            : $result->groups;

        if ($format === self::FORMAT_JSON) {
            $array = $result->toArray();
            if ($this->option('hide-low-volume')) {
                $array['data'] = array_values(array_filter($array['data'], static fn(array $group) => !$group['low_volume']));
            }
            $this->line((string)json_encode($array, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $headers = ['#', 'Verdict', 'Pack', 'Enemies', 'NPCs', 'Floor', 'Resolutions', 'Routes', 'Share', 'Off (yd)', 'Direction', 'Shape', 'Engaged lat', 'Engaged lng', 'Suggestion'];
        $rows    = [];
        foreach ($groups as $index => $group) {
            $rows[] = [
                sprintf('%d%s', $index + 1, $group->lowVolume ? '*' : ''),
                $group->verdict->label(),
                $group->enemyPackId === null ? '-' : sprintf('%s (%d)', $group->enemyPackGroup ?? '-', $group->enemyPackId),
                implode(', ', $group->enemyIds),
                $group->npcNames,
                $group->floorId,
                $group->count,
                $group->routeCount,
                sprintf('%d%%', round($group->routeShare * 100)),
                number_format($group->displacement),
                number_format($group->directionConsistency, 2),
                $group->shapeRatio === null ? '-' : number_format($group->shapeRatio, 2),
                $group->engagedCentroid->getLat(1),
                $group->engagedCentroid->getLng(1),
                $group->suggestion,
            ];
        }

        $this->line(sprintf(
            '%s — mapping version %d: %d groups over %d routes (low-volume below %d routes / %d%% of routes, marked *), %d resolutions skipped',
            __($dungeon->name, [], 'en_US'),
            $mappingVersion->id,
            count($groups),
            $result->routeCount,
            $result->minRoutes,
            round($result->minRouteShare * 100),
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
