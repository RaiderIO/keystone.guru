<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

/**
 * @mixin TableSeederInterface
 */
trait TableSeederTrait
{
    public function rollback(): void
    {
        foreach (self::getAffectedModelClasses() as $affectedTable) {
            DB::table($affectedTable)->truncate();
        }
    }
}
