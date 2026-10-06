<?php

namespace Tests\Fixtures\Seeders;

use Illuminate\Database\Eloquent\Model;

/**
 * Affected model of StubTableSeeder. Its table never exists: StepResultDatabaseSeeder stubs the temp-table
 * prepare/apply steps, and cleanup only drops the temp table if it exists.
 */
class StubSeededModel extends Model
{
    protected $table = 'stub_seeded_models';
}
