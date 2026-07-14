<?php

/**
 * Generate route thumbnails locally for development (see SKILL.md for the full runbook —
 * the ThumbnailService local-env guard must be temporarily bypassed and several env
 * overrides are required).
 *
 * Run from the worktree/checkout root:
 *   docker compose exec -T \
 *     -e APP_URL=http://nginx \
 *     -e PUPPETEER_EXECUTABLE_PATH=/var/www/.chrome-tmp/chrome-headless-shell-linux64/chrome-headless-shell \
 *     -e THUMBNAIL_KEYS=key1,key2 \
 *     app php artisan tinker .claude/skills/seed-dev-routes/generate_thumbnails.php
 *
 * Env parameters (one of the two is required):
 * - THUMBNAIL_KEYS (optional) comma-separated dungeon route public keys
 * - DUNGEON_KEY    (optional) generate for ALL routes of this dungeon key instead
 */

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Service\DungeonRoute\ThumbnailServiceInterface;

$publicKeys = array_filter(explode(',', getenv('THUMBNAIL_KEYS') ?: ''));
$dungeonKey = getenv('DUNGEON_KEY') ?: null;

$query = DungeonRoute::with(['dungeon', 'mappingVersion']);
if (!empty($publicKeys)) {
    $query->whereIn('public_key', $publicKeys);
} elseif ($dungeonKey !== null) {
    $dungeon = Dungeon::where('key', $dungeonKey)->firstOrFail();
    $query->where('dungeon_id', $dungeon->id);
} else {
    echo 'Pass THUMBNAIL_KEYS=key1,key2 or DUNGEON_KEY=<key>' . PHP_EOL;

    return;
}

$thumbnailService = app(ThumbnailServiceInterface::class);

foreach ($query->get() as $route) {
    foreach ($route->dungeon->floorsForMapFacade($route->mappingVersion, true)->active()->get() as $floor) {
        /** @var Floor $floor */
        $start  = microtime(true);
        $result = $thumbnailService->createThumbnail($route, $floor->index);
        echo sprintf(
            '%s floor %d: %s (%.1fs)%s',
            $route->public_key,
            $floor->index,
            $result !== null ? sprintf('OK -> %s', $result->file?->getURL()) : 'FAILED',
            microtime(true) - $start,
            PHP_EOL,
        );
    }
}
