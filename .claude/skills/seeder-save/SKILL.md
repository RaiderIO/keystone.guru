---
name: seeder-save
description: Guide for adding a model or nested relation to the `mapping:save` Artisan command so it is exported to the dungeon JSON seeder files. Use when a new model or child relation needs to be persisted into `database/seeders/dungeondata/` as part of the mapping export.
---

# Seeder Save

This skill covers how to wire a new model or child relation into `app/Console/Commands/Mapping/Save.php` so it is exported to the dungeon JSON seeder files under `database/seeders/dungeondata/`.

---

## How the Save command works

`php artisan mapping:save` calls private methods like `saveDungeons()`, `saveNpcs()`, `saveSpells()`, etc.

Each method:
1. Queries the model(s) with eager-loaded relations using `->with([...])`
2. Calls `makeVisible([...])` / `makeHidden([...])` to control which columns appear in the JSON
3. Dumps the result to a JSON file with `$this->saveDataToJsonFile($data, $dir, 'filename.json')`

The JSON files land in `$dungeonDataDir` which resolves to `database/seeders/dungeondata/`.

---

## Adding a top-level model (new file)

When a new model needs its own JSON file:

```php
private function saveMyModels(string $dungeonDataDir): void
{
    $this->info('Saving MyModels');

    $models = MyModel::with(['childRelation', 'otherRelation'])->get();

    foreach ($models as $model) {
        // Strip computed/display properties that should not be in the JSON
        $model->makeHidden(['computed_attr']);
    }

    $this->saveDataToJsonFile($models->toArray(), $dungeonDataDir, 'my_models.json');
}
```

Then call it from `handle()`:
```php
$this->saveMyModels($dungeonDataDir);
```

---

## Adding a nested relation to an existing export (most common case)

When child rows should be nested inside an already-exported parent:

### 1. Add the relation to the eager-load chain

In the parent model's save method, add the nested relation:

```php
// Before
->with([
    'floors.floorcouplings',
    'floors.dungeonSpeedrunRequiredNpcs10Man',
])

// After — add the child relation via dot notation
->with([
    'floors.floorcouplings',
    'floors.dungeonSpeedrunRequiredNpcs10Man.dungeonSpeedrunRequiredNpcNpcs',
])
```

### 2. Control visibility on the child model

The child model's `$visible` / `$hidden` arrays control what appears in the JSON.

- **Always hide** the parent FK (`dungeon_speedrun_required_npc_id`) — it is reconstructed on import from context, not stored in the JSON.
- **Always hide** `id` if the child rows don't need stable IDs across environments.
- **Expose** only the payload columns (`npc_id`, `spell_id`, etc).

```php
// Child model (e.g. DungeonSpeedrunRequiredNpcNpc)
protected $hidden = ['id', 'dungeon_speedrun_required_npc_id'];
// Only npc_id will appear in the JSON
```

For the child relation to appear in the **parent's** JSON array, the parent model's `$visible` must include the relation name (snake_case):

```php
// Parent model (e.g. DungeonSpeedrunRequiredNpc)
protected $visible = [
    'id',
    'floor_id',
    'difficulty',
    'count',
    'dungeon_speedrun_required_npc_npcs', // ← relation name in snake_case
];
```

### 3. Verify the resulting JSON shape

After running `php artisan mapping:save`, confirm the JSON contains the expected nested structure:

```json
{
  "id": 1, "floor_id": 138, "difficulty": 2, "count": 16,
  "dungeon_speedrun_required_npc_npcs": [
    { "npc_id": 16017 },
    { "npc_id": 16029 }
  ]
}
```

---

## `makeVisible` / `makeHidden` tips

- `$model->makeVisible([...])` adds columns that are normally in `$hidden` back into the JSON.
- `$model->makeHidden([...])` suppresses columns or computed properties from serialization.
- For collection-level calls use `$collection->makeVisible([...])`.
- For relation names that are camelCase (e.g. `npcCharacteristics`), the JSON key is the snake_case form (`npc_characteristics`). Put the snake_case name in `$visible`.

---

## Checklist

1. Confirm the relation is eager-loaded (avoid N+1).
2. Set `$visible` / `$hidden` on the child model to expose only what's needed.
3. Expose the relation name (snake_case) in the parent's `$visible` if needed.
4. Run `php artisan mapping:save` inside Docker and inspect the JSON diff.
5. Confirm the companion `seeder-load` parser handles the new structure.

See also: `seeder-load` skill for the corresponding import side.
