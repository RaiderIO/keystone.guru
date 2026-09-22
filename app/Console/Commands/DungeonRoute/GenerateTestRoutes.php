<?php

namespace App\Console\Commands\DungeonRoute;

use App\Models\Dungeon;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\User;
use App\Service\DungeonRoute\Exceptions\TestDungeonRouteGeneratorException;
use App\Service\DungeonRoute\TestDungeonRouteGeneratorServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class GenerateTestRoutes extends Command
{
    private const int DELETE_BATCH_SIZE = 25;

    private const int DEFAULT_AUTHOR_ID = 1;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:generatetest
        {--dungeon=* : Dungeon key(s) to generate routes for}
        {--season= : Season id to generate routes for every dungeon of, or "current"}
        {--count=5 : Routes per dungeon (1-20)}
        {--author= : User id that owns the routes, default 1. When deleting, only delete the routes of this user}
        {--published-state=world : unpublished, team, world_with_link or world}
        {--delete : Delete generated test routes instead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Local/staging only: generates throwaway routes with random pulls that reach the required enemy forces and kill every boss.';

    public function handle(
        TestDungeonRouteGeneratorServiceInterface $testDungeonRouteGeneratorService,
        SeasonServiceInterface                    $seasonService,
    ): int {
        try {
            $authorId = $this->option('author');
            $author   = User::find((int)($authorId ?? self::DEFAULT_AUTHOR_ID));
            if ($author === null) {
                $this->error(sprintf('No user with id %s.', $authorId));

                return self::FAILURE;
            }

            if ($this->option('delete')) {
                return $this->deleteGenerated($testDungeonRouteGeneratorService, $authorId === null ? null : $author);
            }

            $dungeons = $this->resolveDungeons($seasonService);
            if ($dungeons->isEmpty()) {
                $this->error('Pass --dungeon=<key> or --season=<id|current>.');

                return self::FAILURE;
            }

            $publishedState = (string)$this->option('published-state');
            if (!isset(PublishedState::ALL[$publishedState])) {
                $this->error(sprintf('Unknown published state %s.', $publishedState));

                return self::FAILURE;
            }

            foreach ($dungeons as $dungeon) {
                $dungeonRoutes = $testDungeonRouteGeneratorService->generate(
                    $dungeon,
                    $author,
                    (int)$this->option('count'),
                    PublishedState::ALL[$publishedState],
                );

                $this->info(sprintf('%s: %s', $dungeon->key, $dungeonRoutes->pluck('public_key')->implode(', ')));
            }
        } catch (TestDungeonRouteGeneratorException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @throws TestDungeonRouteGeneratorException
     */
    private function deleteGenerated(TestDungeonRouteGeneratorServiceInterface $testDungeonRouteGeneratorService, ?User $author): int
    {
        $deleted = 0;
        do {
            $result = $testDungeonRouteGeneratorService->deleteGenerated(self::DELETE_BATCH_SIZE, $author);
            $deleted += $result['deleted'];
        } while ($result['deleted'] > 0 && $result['remaining'] > 0);

        $this->info(sprintf('Deleted %d generated test routes.', $deleted));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Dungeon>
     */
    private function resolveDungeons(SeasonServiceInterface $seasonService): Collection
    {
        /** @var array<string> $dungeonKeys */
        $dungeonKeys = $this->option('dungeon');
        $seasonId    = $this->option('season');

        $dungeons = collect();
        if (!empty($dungeonKeys)) {
            $dungeons = Dungeon::query()->whereIn('key', $dungeonKeys)->get();

            foreach (array_diff($dungeonKeys, $dungeons->pluck('key')->all()) as $unknownKey) {
                $this->warn(sprintf('Unknown dungeon key %s, skipping.', $unknownKey));
            }
        }

        if ($seasonId !== null) {
            $season = $seasonId === 'current' ? $seasonService->getCurrentSeason() : Season::find((int)$seasonId);
            if ($season === null) {
                $this->warn(sprintf('Unknown season %s, skipping.', $seasonId));
            } else {
                $dungeons = $dungeons->concat($season->dungeons)->unique('id');
            }
        }

        return $dungeons->values();
    }
}
