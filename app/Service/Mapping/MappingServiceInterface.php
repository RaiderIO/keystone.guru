<?php


namespace App\Service\Mapping;

use Illuminate\Support\Collection;

interface MappingServiceInterface
{
    /**
     * @return bool True if the mapping has changed since last time we synchronized it, and we need to synchronize it again.
     */
    function shouldSynchronizeMapping(): bool;

    /**
     * @param bool $ignoreMostRecentCommit
     * @return Collection A list of all changes to the mapping that have not been synchronized yet.
     */
    function getUnsynchronizedMappingChanges(bool $ignoreMostRecentCommit = false): Collection;

    /**
     * @param bool $ignoreMostRecentCommit
     * @return Collection Gets a list of dungeons of which the mapping has changed since the last time a synchronization was done.
     */
    function getRecentlyChangedDungeons(bool $ignoreMostRecentCommit = false): Collection;
}