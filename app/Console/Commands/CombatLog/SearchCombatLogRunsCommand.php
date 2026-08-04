<?php

namespace App\Console\Commands\CombatLog;

use App\Models\Dungeon;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Searches Raider.IO for Mythic+ runs matching the given filters, for local detector-validation workflows
 * (find candidate runs before downloading their combat log with `combatlog:downloadruns`).
 */
class SearchCombatLogRunsCommand extends Command
{
    use ResolvesCombatLogSearchFilter;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:searchruns
        {--dungeon= : Dungeon key or name filter}
        {--class=* : Character class key(s), e.g. rogue; resolves to all specs of the class}
        {--spec=* : Specific CharacterClassSpecialization ids to filter on}
        {--min-level=10 : Minimum mythic level}
        {--from-days=7 : completedAt window start, days ago}
        {--limit=20 : Maximum number of runs to return}
        {--offset=0 : Search pagination offset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Searches Raider.IO for Mythic+ runs matching the given filters, for local detector-validation workflows.';

    public function __construct(
        private readonly RaiderIOApiServiceInterface $raiderIOApiService,
        private readonly SeasonServiceInterface      $seasonService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $season = $this->seasonService->getCurrentSeason();

        if ($season === null) {
            $this->warn('combatlog:searchruns — no current season found, skipping');

            return self::SUCCESS;
        }

        try {
            $filter = $this->buildSearchFilter($season);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $response = $this->raiderIOApiService->searchAdvancedRuns($filter);

        /** @var \Illuminate\Support\Collection<int|string, Dungeon> $dungeonsByChallengeModeId */
        $dungeonsByChallengeModeId = Dungeon::query()->whereNotNull('challenge_mode_id')->get()->keyBy('challenge_mode_id');

        $rows = [];

        foreach ($response->runs as $run) {
            /** @var ?Dungeon $dungeon */
            $dungeon = $dungeonsByChallengeModeId->get($run->challengeModeId);

            $rows[] = [
                $run->id,
                $dungeon !== null
                    ? __($dungeon->name)
                    : sprintf('challengeModeId=%d/zoneId=%d', $run->challengeModeId, $run->dungeonZoneId),
                $run->mythicLevel,
                implode(', ', $run->memberSpecIds),
            ];
        }

        $this->table(['Run ID', 'Dungeon', 'Level', 'Member Specs'], $rows);

        $this->info(sprintf(
            'combatlog:searchruns — found=%d total=%s',
            count($response->runs),
            $response->total !== null ? (string)$response->total : 'unknown',
        ));

        return self::SUCCESS;
    }
}
