<?php

namespace App\Console\Commands\WagoTools;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\EnemyForces\Dtos\DungeonEnemyForcesDiff;
use App\Service\EnemyForces\Dtos\NpcEnemyForcesDiff;
use App\Service\EnemyForces\EnemyForcesDb2ServiceInterface;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use Illuminate\Console\Command;

class ImportEnemyForces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wagotools:importenemyforces
                            {--dungeon= : The dungeon to import, by key or slug}
                            {--product= : The CDN product to read DB2 data for, wow or wowt}
                            {--gameVersion=retail : The game version that product is the client of}
                            {--build= : A specific game build, e.g. 12.1.0.69497; defaults to the most recent one}
                            {--write : Write the enemy forces; without it the command only reports what it would write}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes the M+ enemy forces of one dungeon from the game client DB2 tables onto its current mapping version.';

    public function handle(EnemyForcesDb2ServiceInterface $enemyForcesDb2Service): int
    {
        $dungeonKey = $this->option('dungeon');
        $product    = $this->option('product');

        if ($dungeonKey === null) {
            $this->error('Pass the dungeon to import with --dungeon - this command imports one dungeon at a time.');

            return 1;
        }

        // The live client's files lag server side hotfixes, so reading them by default would quietly revert
        // a hotfixed value the PTR already carries.
        if ($product === null) {
            $this->error('Pass the product to read with --product=wow or --product=wowt - there is no default.');

            return 1;
        }

        $gameVersionKey = (string)$this->option('gameVersion');
        $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);

        if ($gameVersion === null) {
            $this->error(sprintf('Unknown game version %s', $gameVersionKey));

            return 1;
        }

        $dungeon = Dungeon::query()
            ->where('key', (string)$dungeonKey)
            ->orWhere('slug', (string)$dungeonKey)
            ->first();

        if ($dungeon === null) {
            $this->error(sprintf('Unknown dungeon %s', $dungeonKey));

            return 1;
        }

        $build = $this->option('build') === null ? null : (string)$this->option('build');

        try {
            $report = $enemyForcesDb2Service->diffEnemyForces((string)$product, $gameVersion, $build, $dungeon);
        } catch (WagoToolsDownloadException $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        $dungeonDiff = $report?->dungeonDiffs[$dungeon->id] ?? null;

        if ($report === null || $dungeonDiff === null) {
            $this->error('Nothing was compared - is wago.tools reachable, and does the build exist?');

            return 1;
        }

        $dungeonName    = __($dungeon->name);
        $mappingVersion = $dungeonDiff->mappingVersion;

        if (!$dungeonDiff->isResolved() || $mappingVersion === null) {
            $this->error(sprintf('%s cannot be imported from product %s build %s: %s', $dungeonName, $report->product, $report->build, $dungeonDiff->unresolvedReason));

            return 1;
        }

        $this->info(sprintf(
            '%s (scenario %d, criteria tree %d) from product %s build %s onto mapping version %d',
            $dungeonName,
            $dungeonDiff->scenarioId ?? 0,
            $dungeonDiff->criteriaTreeId ?? 0,
            $report->product,
            $report->build,
            $mappingVersion->version,
        ));

        $this->renderPlannedWrite($dungeonDiff);

        $npcDiffsToWrite = $dungeonDiff->getNpcDiffsToWrite();

        if ($dungeonDiff->hasMatchingEnemyForcesRequired() && $npcDiffsToWrite === []) {
            $this->info('Nothing to write - the mapping version already matches this build.');

            return 0;
        }

        if (!$this->option('write')) {
            $this->comment('Dry run - nothing was written. Run again with --write to write the above.');

            return 0;
        }

        $enemyForcesDb2Service->writeEnemyForces($dungeonDiff);

        $this->info(sprintf(
            'Wrote %d enemy forces rows%s. Run mapping:save to take them into the seeder files.',
            count($npcDiffsToWrite),
            $dungeonDiff->hasMatchingEnemyForcesRequired() ? '' : ' and the required total',
        ));

        // A live dungeon's routes cache their enemy forces total, which this write does not refresh
        $dungeonRouteCount = $mappingVersion->dungeonRoutes()->count();
        if ($dungeonRouteCount > 0) {
            $this->warn(sprintf(
                '%d routes use this mapping version - recalculate their enemy forces (admin tools, "Mass recalculate enemy forces for routes") once this is deployed.',
                $dungeonRouteCount,
            ));
        }

        return 0;
    }

    private function renderPlannedWrite(DungeonEnemyForcesDiff $dungeonDiff): void
    {
        $ourEnemyForcesRequired = $dungeonDiff->mappingVersion?->enemy_forces_required;

        if ($dungeonDiff->hasMatchingEnemyForcesRequired()) {
            $this->line(sprintf('  Enemy forces required: %d, unchanged', (int)$ourEnemyForcesRequired));
        } else {
            $this->line(sprintf('  Enemy forces required: %d -> %d', (int)$ourEnemyForcesRequired, (int)$dungeonDiff->db2EnemyForcesRequired));
        }

        $npcDiffsToWrite = $dungeonDiff->getNpcDiffsToWrite();

        if ($npcDiffsToWrite !== []) {
            $this->table(
                ['npc', 'name', 'ours', 'DB2'],
                array_map(static fn(NpcEnemyForcesDiff $npcDiff): array => [
                    $npcDiff->npcId,
                    $npcDiff->npcName === null ? '-' : __($npcDiff->npcName),
                    $npcDiff->ourEnemyForces ?? '-',
                    $npcDiff->db2EnemyForces,
                ], $npcDiffsToWrite),
            );
        }

        foreach ($dungeonDiff->getNpcDiffsOnlyWeHold() as $npcDiff) {
            $this->line(sprintf('  Kept npc %d (%d forces): the build awards none for it', $npcDiff->npcId, (int)$npcDiff->ourEnemyForces));
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
    }
}
