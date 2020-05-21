<?php


namespace App\Service\DungeonRoute;

interface EnemiesListServiceInterface
{
    function listEnemies($dungeonId, $showMdtEnemies = false, $publicKey = null);
}