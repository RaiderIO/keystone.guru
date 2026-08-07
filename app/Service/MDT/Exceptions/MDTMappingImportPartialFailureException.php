<?php

namespace App\Service\MDT\Exceptions;

use Exception;

/**
 * Thrown by {@see \App\Service\MDT\MDTMappingImportService::importMappingVersionFromMDT()} when one or more
 * per-item saves (NPCs, enemies) inside the import failed. Those failures are individually caught and
 * logged where they occur - so the loop keeps going and the rest of the items still get imported - but the
 * run as a whole must still be treated as failed: stamping the new mapping version's mdt_mapping_hash despite
 * the losses would make every subsequent run compare hashes, see no change, and silently skip retrying
 * forever while the dungeon stays missing whatever failed to import (#3755).
 */
class MDTMappingImportPartialFailureException extends Exception
{
    /**
     * @param list<Exception> $failures
     */
    public function __construct(private readonly array $failures)
    {
        $messages = array_map(static fn(Exception $failure) => $failure->getMessage(), $failures);
        $shown    = array_slice($messages, 0, 10);
        if (count($messages) > 10) {
            $shown[] = sprintf('and %d more', count($messages) - 10);
        }

        parent::__construct(
            sprintf('%d item(s) failed to import: %s', count($failures), implode('; ', $shown)),
            0,
            $failures[0] ?? null,
        );
    }

    /**
     * @return list<Exception>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }
}
