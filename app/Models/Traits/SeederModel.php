<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Marks a model whose rows come from the seeders in database/seeders rather than from users, so that
 * DatabaseSeeder::getTempTableName() knows to stage it in a `_temp` table while seeding. These models are
 * read-mostly - see "Seeded models" in CLAUDE.md.
 *
 * @mixin Model
 */
trait SeederModel
{
}
