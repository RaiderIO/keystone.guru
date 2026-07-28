<?php

/**
 * Seed public, non-expiring dungeon routes with pack-based pulls for local development.
 *
 * Run from any checkout/worktree:
 *   docker compose exec -T -e DUNGEON_KEY=pitofsaron [-e ROUTE_COUNT=12] app \
 *     php artisan tinker .claude/skills/seed-dev-routes/seed_routes.php
 *
 * Env parameters:
 * - DUNGEON_KEY  (required) the Dungeon `key`, e.g. pitofsaron
 * - ROUTE_COUNT  (optional) number of routes to create, default 12
 *
 * Each route gets a random target enemy forces percentage between 98% and 112%, so the
 * resulting cards exercise all three UI states: under 100% (warning), 100-105% (ok),
 * and >= 105% (over-pull warning). Pulls are created from real enemy packs of the
 * dungeon's current mapping version.
 */

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\PublishedState;
use Illuminate\Support\Carbon;

$dungeonKey = getenv('DUNGEON_KEY') ?: null;
$routeCount = (int)(getenv('ROUTE_COUNT') ?: 12);

if ($dungeonKey === null) {
    echo 'DUNGEON_KEY env parameter is required (e.g. -e DUNGEON_KEY=pitofsaron)' . PHP_EOL;

    return;
}

/** @var Dungeon|null $dungeon */
$dungeon = Dungeon::where('key', $dungeonKey)->first();
if ($dungeon === null) {
    echo sprintf('Unknown dungeon key "%s"', $dungeonKey) . PHP_EOL;

    return;
}

$mappingVersion = $dungeon->getCurrentMappingVersion();
$seasonService  = app(\App\Service\Season\SeasonServiceInterface::class);
$activeSeason   = $dungeon->getActiveSeason($seasonService);
$forcesRequired = $mappingVersion->enemy_forces_required;

$packs = Enemy::where('mapping_version_id', $mappingVersion->id)
    ->whereNotNull('enemy_pack_id')
    ->whereNotNull('floor_id')
    ->whereNotNull('npc_id')
    ->get()
    ->groupBy('enemy_pack_id');

if ($packs->isEmpty()) {
    echo sprintf('No enemy packs found for mapping version %d', $mappingVersion->id) . PHP_EOL;

    return;
}

$titleTemplates = [
    '%s +20 speedrun',
    'Easy weekly %s clear',
    '%s pug-friendly route (no skips)',
    'Tyrannical safe route - %s',
    'Fortified max forces farm - %s',
    '%s for beginners',
    'Fast two-chest route - %s',
    '%s balanced clear - low deaths',
    'Boss rush - %s',
    'Comfy %s weekly - chill pulls',
    '%s shroud skips',
    'Max forces %s farm run',
];

$createdPublicKeys = [];

for ($i = 0; $i < $routeCount; $i++) {
    $publishedAt = Carbon::now()->subDays(random_int(0, 21))->subMinutes(random_int(0, 1400));
    $views       = random_int(50, 40000);
    $levelMin    = random_int($activeSeason?->key_level_min ?? 2, ($activeSeason?->key_level_max ?? 25) - 5);

    /** @var DungeonRoute $route */
    $route = DungeonRoute::factory()->create([
        'dungeon_id'         => $dungeon->id,
        'mapping_version_id' => $mappingVersion->id,
        'season_id'          => $activeSeason?->id,
        'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        'title'              => sprintf($titleTemplates[$i % count($titleTemplates)], $dungeon->name),
        'level_min'          => $levelMin,
        'level_max'          => min($activeSeason?->key_level_max ?? 25, $levelMin + random_int(0, 10)),
        'expires_at'         => null,
        'views'              => $views,
        'views_embed'        => (int)($views / random_int(5, 20)),
        'popularity'         => random_int(0, 500),
        'rating'             => random_int(30, 50) / 10,
        'rating_count'       => random_int(0, 30),
        'created_at'         => $publishedAt,
        'published_at'       => $publishedAt,
    ]);

    // Add packs (in pack order, so the route flow is roughly sensible) until the
    // per-route target forces percentage is reached
    $targetForces = (int)($forcesRequired * (random_int(98, 112) / 100));
    $pullIndex    = 1;
    $forces       = 0;

    foreach ($packs->shuffle()->sortKeys() as $packEnemies) {
        KillZone::factory()
            ->withEnemies(...$packEnemies->all())
            ->create([
                'dungeon_route_id' => $route->id,
                'floor_id'         => $packEnemies->first()->floor_id,
                'index'            => $pullIndex++,
                'color'            => sprintf('#%06X', random_int(0, 0xFFFFFF)),
                'description'      => '',
                'lat'              => $packEnemies->avg('lat'),
                'lng'              => $packEnemies->avg('lng'),
            ]);

        $forces = $route->getEnemyForces();
        if ($forces >= $targetForces) {
            break;
        }
    }

    $route->update(['enemy_forces' => $forces]);

    $createdPublicKeys[] = $route->public_key;

    echo sprintf(
        '%d/%d %s: %d pulls, %d/%d forces (%.1f%%)%s',
        $i + 1,
        $routeCount,
        $route->public_key,
        $pullIndex - 1,
        $forces,
        $forcesRequired,
        $forcesRequired > 0 ? $forces / $forcesRequired * 100 : 0,
        PHP_EOL,
    );
}

echo 'CREATED: ' . implode(',', $createdPublicKeys) . PHP_EOL;
