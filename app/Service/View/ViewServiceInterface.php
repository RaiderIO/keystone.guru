<?php


namespace App\Service\View;

interface ViewServiceInterface
{
    /**
     * @param bool $useCache
     * @return array
     */
    public function getCache(bool $useCache = true): array;
}
