<?php

namespace App\Service\Creator\Enums;

/**
 * How the creator directory orders its creators.
 */
enum CreatorDirectorySort: string
{
    /** Creators with routes in the current season first, by how much those routes are viewed lately */
    case ActiveThisSeason = 'season';

    /** Most world-published routes of all time first */
    case MostRoutes = 'routes';

    public function label(): string
    {
        return match ($this) {
            self::ActiveThisSeason => __('view_creator.directory.sort_active_this_season'),
            self::MostRoutes       => __('view_creator.directory.sort_most_routes'),
        };
    }
}
