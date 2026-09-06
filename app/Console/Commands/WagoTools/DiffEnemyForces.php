<?php

namespace App\Console\Commands\WagoTools;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\EnemyForces\Dtos\DungeonEnemyForcesDiff;
use App\Service\EnemyForces\Dtos\NpcEnemyForcesDiff;
use App\Service\EnemyForces\EnemyForcesDb2ServiceInterface;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use Illuminate\Console\Command;

class DiffEnemyForces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wagotools:diffenemyforces
                            {--product=wow : The CDN product to read DB2 data for, e.g. wow or wowt}
                            {--gameVersion=retail : The game version that product is the client of}
                            {--build= : A specific game build, e.g. 12.1.0.69497; defaults to the most recent one}
                            {--dungeon= : Limit the diff to one dungeon, by key or slug}
                            {--all : List every NPC, not just the ones that differ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compares the M+ enemy forces of the game client DB2 tables against ours. Reads only, writes nothing.';

    public function handle(EnemyForcesDb2ServiceInterface $enemyForcesDb2Service): int
    {
        $product        = (string)$this->option('product');
        $build          = $this->option('build') === null ? null : (string)$this->option('build');
        $gameVersionKey = (string)$this->option('gameVersion');
        $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);

        if ($gameVersion === null) {
            $this->error(sprintf('Unknown game version %s', $gameVersionKey));

            return 1;
        }

        $dungeon = null;
        if ($this->option('dungeon') !== null) {
            $dungeonKey = (string)$this->option('dungeon');
            $dungeon    = Dungeon::query()
                ->where('key', $dungeonKey)
                ->orWhere('slug', $dungeonKey)
                ->first();

            if ($dungeon === null) {
                $this->error(sprintf('Unknown dungeon %s', $dungeonKey));

                return 1;
            }
        }

        $this->comment('The DB2 tables are around 8MB in total - the first run for a build downloads them.');

        try {
            $report = $enemyForcesDb2Service->diffEnemyForces($product, $gameVersion, $build, $dungeon);
        } catch (WagoToolsDownloadException $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        if ($report === null) {
            $this->error('Nothing was compared - is wago.tools reachable, and does the build exist?');

            return 1;
        }

        $this->info(sprintf('Comparing our enemy forces against product %s build %s', $report->product, $report->build));
        $this->newLine();

        foreach ($report->dungeonDiffs as $dungeonDiff) {
            $this->renderDungeonDiff($dungeonDiff);
        }

        // A build that resolves none of our dungeons is a build we read wrong, not a build our data diverges
        // from - saying "everything matches" about it would be the more dangerous answer.
        if ($report->getResolvedDungeonDiffs() === []) {
            $this->error('Not one dungeon could be resolved in this build - refusing to report on it.');

            return 1;
        }

        $this->renderSummary(
            $report->getResolvedDungeonDiffs(),
            $report->getDivergingDungeonDiffs(),
            $report->getDungeonDiffsWithDivergingEnemyForcesRequired(),
        );

        return 0;
    }

    private function renderDungeonDiff(DungeonEnemyForcesDiff $dungeonDiff): void
    {
        $dungeonName    = __($dungeonDiff->dungeon->name);
        $mappingVersion = $dungeonDiff->mappingVersion;

        if (!$dungeonDiff->isResolved() || $mappingVersion === null) {
            $this->warn(sprintf('%s: %s', $dungeonName, $dungeonDiff->unresolvedReason));

            return;
        }

        $header = sprintf(
            '%s (scenario %d, criteria tree %d, mapping version %d): DB2 %d - ours %d',
            $dungeonName,
            $dungeonDiff->scenarioId ?? 0,
            $dungeonDiff->criteriaTreeId ?? 0,
            $mappingVersion->version,
            $dungeonDiff->db2EnemyForcesRequired ?? 0,
            $mappingVersion->enemy_forces_required,
        );

        if ($dungeonDiff->matches()) {
            $this->info(sprintf('%s - identical', $header));
        } else {
            $this->warn($header);
        }

        $npcDiffs = $this->option('all') ? $dungeonDiff->npcDiffs : $dungeonDiff->getMismatchedNpcDiffs();

        if ($npcDiffs !== []) {
            $this->table(
                ['npc', 'name', 'DB2', 'ours'],
                array_map(static fn(NpcEnemyForcesDiff $npcDiff): array => [
                    $npcDiff->npcId,
                    $npcDiff->npcName === null ? '-' : __($npcDiff->npcName),
                    $npcDiff->db2EnemyForces ?? '-',
                    $npcDiff->ourEnemyForces ?? '-',
                ], $npcDiffs),
            );
        }

        foreach ($dungeonDiff->unmappedNpcEnemyForces as $npcId => $enemyForces) {
            $this->line(sprintf('  Skipped npc %d (%d forces): this mapping version has no enemy of it', $npcId, $enemyForces));
        }

        foreach ($dungeonDiff->nonCreatureCriteria as $criteriaRow) {
            $this->line(sprintf(
                '  Skipped criteria %d (%d forces): type %d awards forces for asset %d, not for killing a creature',
                $criteriaRow->criteriaId,
                $criteriaRow->amount,
                $criteriaRow->type,
                $criteriaRow->asset,
            ));
        }

        $this->newLine();
    }

    /**
     * @param array<int, DungeonEnemyForcesDiff> $resolvedDungeonDiffs
     * @param array<int, DungeonEnemyForcesDiff> $divergingDungeonDiffs
     * @param array<int, DungeonEnemyForcesDiff> $dungeonDiffsWithDivergingEnemyForcesRequired
     */
    private function renderSummary(
        array $resolvedDungeonDiffs,
        array $divergingDungeonDiffs,
        array $dungeonDiffsWithDivergingEnemyForcesRequired,
    ): void {
        if ($divergingDungeonDiffs === []) {
            $this->info(sprintf('All %d resolved dungeons match this build.', count($resolvedDungeonDiffs)));

            return;
        }

        $this->warn(sprintf(
            '%d of %d resolved dungeons diverge from this build: %s',
            count($divergingDungeonDiffs),
            count($resolvedDungeonDiffs),
            $this->listDungeons($divergingDungeonDiffs),
        ));

        // A moved total is the one that changes what a route shows; a per NPC row that only we have is
        // usually a seasonal affix enemy the client never awarded forces for.
        if ($dungeonDiffsWithDivergingEnemyForcesRequired !== []) {
            $this->warn(sprintf(
                'Of those, %d require a different total: %s',
                count($dungeonDiffsWithDivergingEnemyForcesRequired),
                $this->listDungeons($dungeonDiffsWithDivergingEnemyForcesRequired),
            ));
        }

        $this->comment('The live client lags server side hotfixes - compare --product=wowt before concluding ours is wrong.');
    }

    /** @param array<int, DungeonEnemyForcesDiff> $dungeonDiffs */
    private function listDungeons(array $dungeonDiffs): string
    {
        return implode(', ', array_map(
            static fn(DungeonEnemyForcesDiff $dungeonDiff): string => __($dungeonDiff->dungeon->name),
            $dungeonDiffs,
        ));
    }
}
