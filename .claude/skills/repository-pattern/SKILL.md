---
name: repository-pattern
description: Complete guide to the repository pattern used in this project — file locations, naming conventions, interface shape, provider registration, and how to add a new repository for a model.
---

# Repository Pattern

## Overview

Every Eloquent model has a matching repository. The repository abstracts all database access and is injected via its interface, keeping controllers and services free of direct Eloquent calls.

## Directory Structure

```
app/Repositories/
├── BaseRepositoryInterface.php           — base interface (CRUD + exists + all)
├── BaseRepository.php                    — abstract base class
├── Database/
│   ├── DatabaseRepository.php            — concrete base (implements BaseRepositoryInterface)
│   └── {Domain}/
│       └── {ModelName}Repository.php     — one file per model
└── Interfaces/
    └── {Domain}/
        └── {ModelName}RepositoryInterface.php
```

`{Domain}` mirrors the model's subdirectory under `app/Models/` (e.g. `CombatLog`, `DungeonRoute`, `Npc`, `Floor`).

## Naming Convention

| Artefact | Name | Example |
|---|---|---|
| Interface | `{ModelName}RepositoryInterface` | `CombatLogNpcEventRepositoryInterface` |
| Implementation | `{ModelName}Repository` | `CombatLogNpcEventRepository` |
| Interface namespace | `App\Repositories\Interfaces\{Domain}` | `App\Repositories\Interfaces\CombatLog` |
| Implementation namespace | `App\Repositories\Database\{Domain}` | `App\Repositories\Database\CombatLog` |

## Interface File

`app/Repositories/Interfaces/{Domain}/{ModelName}RepositoryInterface.php`

```php
<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\MyModel;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MyModel             create(array $attributes)
 * @method MyModel|null        find(int $id, array|string $columns = ['*'])
 * @method MyModel             findOrFail(int $id, array|string $columns = ['*'])
 * @method MyModel             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                save(MyModel $model)
 * @method bool                update(MyModel $model, array $attributes = [], array $options = [])
 * @method bool                delete(MyModel $model)
 * @method Collection<MyModel> all()
 * @method bool                exists(array $columns)
 */
interface MyModelRepositoryInterface extends BaseRepositoryInterface
{
    // Declare custom query methods here when needed.
    // Leave empty for models that only need CRUD.
}
```

The `@method` docblock re-declares the inherited base methods with the concrete model type so IDEs provide accurate type hints.

## Implementation File

`app/Repositories/Database/{Domain}/{ModelName}Repository.php`

```php
<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\MyModel;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\MyModelRepositoryInterface;

class MyModelRepository extends DatabaseRepository implements MyModelRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MyModel::class);
    }
}
```

- Pass the model class string to `parent::__construct()`.
- No need to handle the database connection explicitly — Eloquent picks it up from the model's `$connection` property (important for models on the `combatlog` connection).
- Add custom methods only when callers need domain-specific queries beyond basic CRUD.

## Provider Registration

`app/Providers/RepositoryServiceProvider.php`

Add **two** `use` statements (one for the interface, one for the implementation) in alphabetical order within their domain groups:

```php
use App\Repositories\Database\CombatLog\MyModelRepository;
use App\Repositories\Interfaces\CombatLog\MyModelRepositoryInterface;
```

Then add a `bind` call inside `register()`, grouped under the domain comment:

```php
// CombatLog
$this->app->bind(MyModelRepositoryInterface::class, MyModelRepository::class);
```

## Usage

Inject the interface — never the concrete class:

```php
public function myAction(
    MyRequest                      $request,
    MyModelRepositoryInterface     $myModelRepository,
): JsonResponse {
    $record = $myModelRepository->findOrFail($request->validated('id'));
    // ...
}
```

## Adding a Repository Checklist

1. Create `app/Repositories/Interfaces/{Domain}/{ModelName}RepositoryInterface.php` with `@method` docblock.
2. Create `app/Repositories/Database/{Domain}/{ModelName}Repository.php` extending `DatabaseRepository`.
3. Add `use` imports and `$this->app->bind(...)` in `RepositoryServiceProvider`.
4. Stage all three changes with `git add`.
5. Run `composer run fix` and `composer run analyse` — both must pass.
