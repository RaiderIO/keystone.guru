<?php

namespace App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use App\Service\DungeonRoute\Exceptions\TestDungeonRouteGeneratorException;
use Illuminate\Support\Collection;

/**
 * Generates throwaway dungeon routes with random but complete pulls, so that pages which list or pick
 * routes can be tested without creating routes by hand. Never available in production.
 */
interface TestDungeonRouteGeneratorServiceInterface
{
    public const int MAX_ROUTES_PER_DUNGEON = 20;

    /** Personal tag (on the author) that marks a route as generated, so cleanup never touches a real route. */
    public const string TAG_NAME = 'generated-test-route';

    public function isAvailable(): bool;

    /**
     * Creates $count routes for the dungeon's current mapping version, each with pulls that reach
     * between 100% and 112% of the required enemy forces where the mapping allows it, and killing every boss.
     *
     * @return Collection<int, DungeonRoute>
     *
     * @throws TestDungeonRouteGeneratorException
     */
    public function generate(Dungeon $dungeon, User $author, int $count, int $publishedStateId): Collection;

    /**
     * Deletes at most $limit generated routes (of $author only, when given), one model at a time so every
     * delete hook runs.
     *
     * @return array{deleted: int, remaining: int}
     *
     * @throws TestDungeonRouteGeneratorException
     */
    public function deleteGenerated(int $limit, ?User $author = null): array;

    public function countGenerated(?User $author = null): int;
}
