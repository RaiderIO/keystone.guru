<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Marks a model whose rows come from the seeders in database/seeders rather than from users, so that
 * DatabaseSeeder::getTempTableName() knows to stage it in a `_temp` table while seeding. These models are
 * read-mostly - see "Seeded models" in CLAUDE.md.
 *
 * This used to also register a `deleting` listener returning false for everyone but an admin. It guarded
 * nothing - every route that deletes one of these models already sits behind `role:admin` - while doing two
 * kinds of damage: it silently turned `$model->delete()` into a no-op wherever there is no authenticated
 * user (Artisan commands, queued jobs, tests), and because Eloquent fires `deleting` through the
 * dispatcher's `until()`, which halts on the first non-null result, it swallowed every `deleting` listener
 * a model registered in `booted()` - including Npc's, so deleting an NPC as an admin left its spells,
 * characteristics, bolstering whitelists, enemy forces and dungeon couplings behind.
 *
 * @mixin Model
 */
trait SeederModel
{
}
