<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by DatabaseSeeder when a temp-table step for a seeder fails, so `db:seed` exits non-zero and the
 * deploy's migrate task fails instead of rolling the services onto a partially seeded database.
 */
class SeederStepFailedException extends RuntimeException
{
    public function __construct(string $seederClass, string $step)
    {
        parent::__construct(sprintf('%s temp table for %s failed!', $step, $seederClass));
    }
}
