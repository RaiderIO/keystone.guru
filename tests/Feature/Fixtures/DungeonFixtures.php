<?php

namespace Tests\Feature\Fixtures;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;

class DungeonFixtures
{
    public static function getDungeonWithCurrentMappingVersionWithEnemies(
        int $gameVersionId = GameVersion::ALL[GameVersion::DEFAULT_GAME_VERSION],
    ): Dungeon {
        return Dungeon::whereNotNull('challenge_mode_id')
            ->where('challenge_mode_id', '>', 0)
            ->whereHas('floors')
            ->whereHas('mappingVersions', static fn($q) => $q->where('game_version_id', $gameVersionId)
                ->whereHas('enemies'))
            ->orderByDesc('id')
            ->first();
    }
}
