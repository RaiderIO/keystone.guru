---
name: seeder-load
description: Guide for adding a RelationParser so a new model or nested relation is correctly imported from the dungeon JSON seeder files. Use when a new model/child table needs to be populated during `php artisan db:seed --class=DungeonDataSeeder`.
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
4. Run `php artisan db:seed --class=DungeonDataSeeder` inside Docker and verify row counts.
5. Confirm the companion `seeder-save` export produces the JSON structure this parser expects.

See also: `seeder-save` skill for the corresponding export side.
