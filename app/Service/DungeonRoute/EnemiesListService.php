<?php

namespace App\Service\DungeonRoute;


use App\Http\Controllers\Traits\ListsEnemies;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;

/**
 * Provides a shortcut for listing enemies.
 *
 * @package App\Service
 * @author Wouter
 * @since 09/05/2020
 */
class EnemiesListService implements EnemiesListServiceInterface
{
    use PublicKeyDungeonRoute;
    use ListsEnemies;
}