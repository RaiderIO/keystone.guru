<?php

namespace App\Models\DungeonRoute;

/**
 * The difficulty level of the routes a collection holds - what a viewer is signing up for when they
 * open it.
 *
 * The backing value is the `name` column of the matching dungeon_route_collection_categories row.
 */
enum DungeonRouteCollectionCategoryType: string
{
    case Beginner     = 'beginner';
    case Intermediate = 'intermediate';
    case Expert       = 'expert';
    case Mdi          = 'mdi';

    /**
     * The primary key the seeder writes for this category, and the value the collection form posts
     * back.
     */
    public function id(): int
    {
        return match ($this) {
            self::Beginner     => 2,
            self::Intermediate => 3,
            self::Expert       => 4,
            self::Mdi          => 5,
        };
    }

    public function getTranslatedName(): string
    {
        return __(sprintf('dungeonroutecollectioncategories.%s', $this->value));
    }
}
