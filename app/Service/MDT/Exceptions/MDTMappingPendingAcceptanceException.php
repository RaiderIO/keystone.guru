<?php

namespace App\Service\MDT\Exceptions;

use Exception;

/**
 * Thrown by {@see \App\Service\MDT\MDTMappingImportService::importMappingVersionFromMDT()} when the
 * dungeon's current mapping version is one of our own corrections awaiting MDT acceptance
 * (`mdt_changes_pending`, #4280) and MDT's mapping has since changed.
 *
 * Creating a new mapping version there is almost always wrong: the usual reason MDT's mapping changed is
 * that they accepted our correction, so the new mapping version would be identical in content to the
 * pending one and would invalidate every route on it with an upgrade notice for nothing (#4281). Accepting
 * MDT's mapping onto the pending mapping version instead is an explicit operator step
 * (`mdt:acceptmapping`), because the alternative - MDT shipping changes of their own on top of ours - does
 * warrant a real new mapping version, and only a human can tell the two apart. `--force` still creates one.
 */
class MDTMappingPendingAcceptanceException extends Exception
{
    public function __construct(string $dungeonKey, int $version)
    {
        parent::__construct(
            sprintf(
                'Refusing to create a new mapping version for %s: its current mapping version (v%d) is awaiting MDT ' .
                'acceptance of our own changes. If MDT accepted them, run `php artisan mdt:acceptmapping %s <gameVersion>` ' .
                'to stamp MDT\'s mapping hash onto v%d and keep routes where they are. If MDT shipped changes of their ' .
                'own on top of ours, re-run this import with --force to create a new mapping version after all.',
                $dungeonKey,
                $version,
                $dungeonKey,
                $version,
            ),
        );
    }
}
