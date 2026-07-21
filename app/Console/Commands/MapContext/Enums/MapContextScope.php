<?php

namespace App\Console\Commands\MapContext\Enums;

/**
 * The `--scope` option shared by the map context generator commands.
 */
enum MapContextScope: string
{
    case Priority = 'priority';
    case Rest     = 'rest';
    case All      = 'all';
}
