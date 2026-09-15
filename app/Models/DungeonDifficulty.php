<?php

namespace App\Models;

/**
 * The speedrun difficulties (raid sizes) a dungeon can be run at. Previously `Dungeon::DIFFICULTY_*`.
 *
 * The backing value is the database ID as stored in `dungeon_routes`.`difficulty` and
 * `dungeon_speedrun_difficulties`.`difficulty`.
 */
enum DungeonDifficulty: int
{
    case TEN_MAN         = 1;
    case TWENTY_FIVE_MAN = 2;
    case TWENTY_MAN      = 3;
    case FORTY_MAN       = 4;

    /**
     * The slug of this difficulty, which doubles as the suffix of its translation key.
     */
    public function slug(): string
    {
        return match ($this) {
            self::TEN_MAN         => '10_man',
            self::TWENTY_FIVE_MAN => '25_man',
            self::TWENTY_MAN      => '20_man',
            self::FORTY_MAN       => '40_man',
        };
    }

    /**
     * Gets the human-readable, translated name of this difficulty (e.g. "10-man").
     */
    public function translatedName(): string
    {
        return __(sprintf('view_common.dungeon.difficulty.%s', $this->slug()));
    }

    /**
     * The database IDs of all difficulties.
     *
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
