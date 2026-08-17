---
name: seeder-load
description: "Guide for adding a RelationParser so a new model or nested relation is imported from the dungeon JSON seeder files. Use when a new model/child table needs populating by `DungeonDataSeeder` (run via `db:seedone`, never `db:seed --class=`). Also covers the two load paths that bypass `RelationMapping`/`RelationParser`: `LoadsSeasonData` (affixes/seasons) and `LoadsMapIconTypeData` (map icon types)."
---

# Seeder Load

This skill covers how to wire a new model or child relation into the dungeon seeder import pipeline so it is populated from the JSON files under `database/seeders/dungeondata/`.

---

## How the import pipeline works

`DungeonDataSeeder` reads each JSON file registered in a `RelationMapping`. For every object in the file it:

1. Runs **pre-save** `RelationParser`s — these handle child rows whose FK references the **explicit `id`** already present in the JSON (the parent id is known before the parent row is inserted).
2. Inserts the parent row into a temp table.
3. Runs **post-save** `RelationParser`s — these handle child rows that need the **database-generated id** of the freshly inserted parent.

Child rows are always inserted into temp tables (via `Model::from(DatabaseSeeder::getTempTableName(Model::class))->insert(...)`). At the end of seeding the temp tables are atomically swapped with the real tables.

---

## Key files

| File | Purpose |
|---|---|
| `app/SeederHelpers/RelationImport/Mapping/RelationMapping.php` | Abstract base — register pre/post-save parsers here |
| `app/SeederHelpers/RelationImport/Parsers/Relation/RelationParserInterface.php` | Interface every parser must implement |
| `database/seeders/DungeonDataSeeder.php` | Orchestrator — registers mappings and lists affected model classes |

---

## Step 1 — Write a RelationParser

Create a class in `app/SeederHelpers/RelationImport/Parsers/Relation/`:

```php
<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpcNpc;
use Database\Seeders\DatabaseSeeder;

class DungeonSpeedrunRequiredNpcNpcsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonSpeedrunRequiredNpc::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        // $name is the snake_case JSON key of the relation
        return $name === 'dungeon_speedrun_required_npc_npcs';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $entry) {
            $entry['dungeon_speedrun_required_npc_id'] = $modelData['id'];
            DungeonSpeedrunRequiredNpcNpc::from(DatabaseSeeder::getTempTableName(DungeonSpeedrunRequiredNpcNpc::class))
                ->insert($entry);
        }

        // Return $modelData unchanged — we only handled a side-table, not the parent row itself
        return $modelData;
    }
}
```

### Pre-save vs post-save

| When to use | How to decide |
|---|---|
| **Pre-save** | The child rows reference the parent's **explicit `id`** from the JSON (common for mapping data with stable IDs) |
| **Post-save** | The child rows need the **auto-incremented id** the database assigns to the parent (common for user-generated data like DungeonRoutes) |

For `DungeonRelationMapping` (dungeons.json), the `DungeonFloorsRelationParser` is a pre-save parser — floors have stable ids embedded in the JSON.

---

## Step 2 — Register the parser in a RelationMapping

Open the relevant `RelationMapping` class and add your parser to the pre- or post-save collection:

```php
// app/SeederHelpers/RelationImport/Mapping/DungeonRelationMapping.php

$this->setPreSaveRelationParsers(collect([
    new NestedModelRelationParser(),
    new DungeonFloorsRelationParser(),        // existing
    // If your parser sits at the dungeon level, add it here.
    // If it is nested inside another parser (e.g. inside DungeonFloorsRelationParser),
    // add it there instead of at the mapping level.
]));
```

If your child rows live inside a parser (e.g. nested inside floor entries), modify the existing parser to extract and insert them directly — see `DungeonFloorsRelationParser` as the reference.

---

## Step 3 — Register the model in `DungeonDataSeeder::getAffectedModelClasses()`

Every model whose table the seeder manages (truncates, creates temp table for, swaps) must appear in this array:

```php
// database/seeders/DungeonDataSeeder.php  getAffectedModelClasses()
DungeonSpeedrunRequiredNpc::class,
DungeonSpeedrunRequiredNpcNpc::class,  // ← add your new model
```

Without this, the seeder does not create a temp table for the model and `insert()` calls will fail.

---

## Handling relations nested inside another parser

When child rows are embedded inside a parent's array entry (rather than at the top level of the JSON), handle them inline inside the existing parser:

```php
// Inside DungeonFloorsRelationParser::parseRelation()
foreach ($floor['dungeon_speedrun_required_npcs25_man'] ?? [] as $speedrunNpc) {
    // Peel off nested child rows before inserting the parent
    $npcEntries = $speedrunNpc['dungeon_speedrun_required_npc_npcs'] ?? [];
    unset($speedrunNpc['dungeon_speedrun_required_npc_npcs']);

    DungeonSpeedrunRequiredNpc::from(DatabaseSeeder::getTempTableName(DungeonSpeedrunRequiredNpc::class))
        ->insert($speedrunNpc);

    foreach ($npcEntries as $entry) {
        $entry['dungeon_speedrun_required_npc_id'] = $speedrunNpc['id'];
        DungeonSpeedrunRequiredNpcNpc::from(DatabaseSeeder::getTempTableName(DungeonSpeedrunRequiredNpcNpc::class))
            ->insert($entry);
    }
}
```

---

## `NestedModelRelationParser` — built-in helper

`NestedModelRelationParser` handles a common case automatically: if the JSON contains a `belongsTo` relation as a nested object `{ id: N, ... }` instead of a raw FK integer, it converts `relation: { id: N }` → `relation_id: N` on the parent row. You don't need a custom parser for that pattern.

---

## Checklist

1. Create a `RelationParser` class with the three interface methods.
2. Add the parser to the correct `RelationMapping` (`setPreSaveRelationParsers` or `setPostSaveRelationParsers`).
3. Add the model class to `DungeonDataSeeder::getAffectedModelClasses()`.
4. Run `php artisan db:seedone --database=migrate DungeonDataSeeder` inside Docker (see below —
   **never** `db:seed --class=...`) and verify row counts.
5. Confirm the companion `seeder-save` export produces the JSON structure this parser expects.

See also: `seeder-save` skill for the corresponding export side.

## Never run `db:seed --class=<Seeder>` — it destroys data

`--class` makes Laravel call the seeder's `run()` **directly**, bypassing `DatabaseSeeder::run()`.
That wrapper is what does the prepare → apply → cleanup dance: it creates the `<table>_temp`
staging tables, lets the seeder fill them, then atomically `RENAME`s them into place. Skip it and
the seeder writes into `*_temp` tables that were never created, dying on
`Base table or view not found: … 'floors_temp' doesn't exist`.

The failure is **not** clean. `DungeonDataSeeder::rollback()` runs first and issues real,
committed deletes before the staged import that would restore them:

- demo `DungeonRoute`s (`where demo = true`), with their killzones/paths/brushlines,
- every `map_icons` row with a non-null `mapping_version_id`,
- every `polylines` row with `model_class = EnemyPatrol`.

So a `--class` run leaves the database missing all three with nothing loaded back. Recovery is a
correct seed run, which restores them from the JSON.

Use one of these instead — both go through `DatabaseSeeder::run()`:

```sh
# one seeder (or several, comma-separated — bare class names under Database\Seeders)
docker compose exec -T app php artisan db:seedone --database=migrate DungeonDataSeeder

# everything (the full documented path; several minutes)
docker compose exec -T app php artisan db:seed --database=migrate --force
```

`db:seedone` exists precisely because `--class` is unsafe here
(`app/Console/Commands/Database/SeedOne.php`, signature `db:seedone {--database=} {className}`).

> Seeder JSON edits are invisible in the app until a seed run lands them in the database — the
> site renders from the DB, not from `database/seeders`.

## `SeederModel` rows: recoverable from seeders

Models using the `App\Models\Traits\SeederModel` trait stage their table as `<table>_temp` via
`DatabaseSeeder::getTempTableName()` while seeding. For the mapping/season/expansion-style models
(dungeons, seasons, expansions, mapping objects, …), rows are authored in `database/seeders` (some
as `.json` files under there), so a delete is recoverable directly from those files.

Until #4062, the trait was also applied to six combat-log-derived models as a vestigial marker —
no seeder ever referenced them, so `db:seed` never staged or swapped their tables. The trait was
removed from all six; see "Combat-log-derived rows" below for what still applies to them.

## Combat-log-derived rows: NOT recoverable from seeders

`SpellDungeon`, `NpcCharacteristic` and `NpcSpell` are intentionally omitted from the seeder export
(see `DungeonDataSeeder::getAffectedModelClasses()`), and `CombatLogNpcEvent`, `CombatLogSpellEvent`
and `ParsedCombatLog` hold pure runtime/audit data that was never seeder-sourced. None of the six
carries `SeederModel` (see above). A delete of one of these rows is permanent unless fresh combat
log data re-derives it.

**Nothing outside the admin panel, the mapping editor, the hourly `combatlog:detectstaledata`
sweep, and (for `ParsedCombatLog`) the daily `combatlog:pruneparsedlogs` retention sweep should
delete these rows.** That is a convention, not an enforced rule: the deleting routes sit behind
`role:admin`, and there is no model-level guard.

---

## Season data (`database/seeders/seasondata/`) — a second, parallel load path

Affixes, affix groups, seasons and season dungeons do **not** go through `RelationMapping`/`RelationParser`
at all. `AffixSeeder` and `SeasonsSeeder` instead use the `App\SeederHelpers\Traits\LoadsSeasonData` trait,
whose `loadSeasonDataFile()` reads a JSON file from `database/seeders/seasondata/`, throws if it is
missing/unreadable/unparseable/empty, and hands back a plain decoded array for the seeder's own `run()` to
insert.

This exists as a separate path rather than another `RelationMapping` because `DungeonDataSeeder` and
`SeasonsSeeder`/`AffixSeeder` each run independently and do their **own** temp-table swap — there is no
shared parent/child relationship between a dungeon and a season the way there is between, say, a dungeon
and its floors, so there is nothing for a `RelationMapping` to nest under. Reaching for `RelationMapping`
here would mean inventing a fake parent just to hang the pipeline off of.

When adding a new season/affix-related file:

1. Do **not** write a `RelationParser` for it — add a `loadSeasonDataFile('your_file.json')` call inside
   the relevant seeder's `run()` instead (`AffixSeeder` or `SeasonsSeeder`, or a new sibling seeder).
2. Insert explicitly via `Model::from(DatabaseSeeder::getTempTableName(Model::class))->insert(...)` like
   the existing seeders do — don't route through `NestedModelRelationParser` or a `RelationMapping`.
3. Still register the model in the seeder's `getAffectedModelClasses()` so the temp-table swap covers it.
4. If array order matters (see the "Season data" section of the `seeder-save` skill), preserve it through
   a single batched `insert()` — InnoDB assigns auto-increment ids in VALUES order, so one `insert()` call
   with the array in the right order re-establishes the same ordering on import.

See also: the "Season data" section in the `seeder-save` skill for why the export side (`Save.php`) also
diverges from the `dungeondata/` convention.

---

## Map icon type data (`database/seeders/mapicontypedata/`) — a third path, and the only hand-edited one

`MapIconTypesSeeder` uses `App\SeederHelpers\Traits\LoadsMapIconTypeData`, a deliberate sibling of
`LoadsSeasonData` rather than a shared base: same guards (missing/unreadable/unparseable/empty file), but
the **opposite contract**. `seasondata/` is generated by `php artisan mapping:save` and must not be
hand-edited; `mapicontypedata/map_icon_types.json` is hand-maintained and has **no** exporter, because map
icon types are not admin-panel-managed. The season guard's "run `php artisan mapping:save` to generate it"
message is actively wrong advice here, which is why the messages are not shared.

The other thing that makes this path different: **the ids do not live in the JSON.** They come from
`MapIconType::ALL`, which stays in PHP because the `MAP_ICON_TYPE_*` constants are compile-time references
all over the application (`MapContextStaticData`, `MapIcon`, `DungeonRouteService`, MDT's `ObjectImporter`
and `Conversion`). The JSON holds only `key`/`name`/`width`/`height`/optional `admin_only`.

So adding a map icon type is **two** edits that must agree, and `MapIconTypesSeeder::run()` throws when
they don't — in either direction, plus on a duplicate key in the JSON:

1. a `MAP_ICON_TYPE_*` constant and its id in `MapIconType::ALL`;
2. an object with that same `key` in `database/seeders/mapicontypedata/map_icon_types.json`.

(Icons also need a `lang/en_US/mapicontypes.php` string and a `GenerateItemIcons` entry — see the
`update-mdt-package` skill for the full list.) Row order in the JSON does not matter; the seeder resolves
each row's id by key.
