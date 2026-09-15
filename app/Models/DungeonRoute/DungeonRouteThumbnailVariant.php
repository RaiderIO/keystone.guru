<?php

namespace App\Models\DungeonRoute;

enum DungeonRouteThumbnailVariant: string
{
    case Standard  = 'standard';
    case Hero      = 'hero';
    case FrontPage = 'front_page';
    case Custom    = 'custom';
}
