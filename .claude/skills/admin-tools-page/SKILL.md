---
name: admin-tools-page
description: >
  Conventions for building a new admin page in keystone.guru — routing, controllers, blade views,
  the tools landing card, the admin nav dropdown, and count badges. Use when adding a read/list or
  simple form admin page. For long chunked-AJAX operations (progress bar, start/pause/stop) use the
  admin-batch-page skill instead.
---

# Admin tools page conventions

How admin pages are wired in this project. Reference implementation: the "combat log parse failures"
page (controller `AdminToolsCombatLogParseFailureController`, view
`resources/views/admin/tools/combatlog/parsefailures.blade.php`).

## Routing

All admin routes live in `routes/web.php` inside two nested groups:

```php
Route::middleware(['auth', 'role:admin'])->group(function () {   // admin gate
    Route::prefix('admin')->group(function () {                  // admin/ URL prefix
        // tool routes, all named admin.tools.*
    });
});
```

- Protection is the `role:admin` middleware — no per-page policy needed.
- Use the **first-class-callable** style, not the array style:
  `Route::get('combatlog/parse-failures', new AdminToolsCombatLogParseFailureController()->index(...))->name('admin.tools.combatlog.parsefailures.view');`
- Naming: `admin.tools.<group>.<action>`, with `.view` / `.submit` suffixes for GET/POST pairs.
- Import the controller at the top of `routes/web.php` (alphabetically among the other
  `App\Http\Controllers\AdminTools\*` imports).
- Route-model binding works for combatlog-connection models too; name the parameter to match the
  controller argument (e.g. `{parseFailure}` → `CombatLogParseFailure $parseFailure`).

## Controllers

- Live in `app/Http/Controllers/AdminTools/`, extend `App\Http\Controllers\Controller`.
- Index/list methods return `Illuminate\View\View`; cap large lists with a `MAX_RESULTS` const.
- JSON endpoints return `Illuminate\Http\JsonResponse` (`response()->json([...], $status)`).
- After a mutating POST: `Session::flash('status', __('controller.admintools.flash.<key>'))` then
  `return redirect()->route('admin.tools.<group>.<action>.view');`.
- Model actions that need finer authorization can call `Gate::authorize(...)`, but list pages rely on
  the group's `role:admin` middleware.

## Views

- Live under `resources/views/admin/tools/`, mirroring the controller/route grouping.
- Extend `@extends('layouts.sitepage', ['showAds' => false, 'title' => __('...')])`, define
  `@section('header-title', __('...'))` and `@section('content')`.
- Two table styles:
  - **Plain Bootstrap table** — `<table class="table table-sm table-striped">` with a `@foreach`.
    Standard for tools pages. Copy `admin/tools/combatlog/rundata.blade.php` or `parsefailures.blade.php`.
  - **DataTables** — `<table class="tablesorter default_table ...">` initialised in
    `@section('scripts')` with `$('#id').DataTable({...})`. Used by model CRUD lists like
    `admin/expansion/list.blade.php`.
- Small inline JS goes in `@section('scripts')` with `@parent` first (jQuery `$` is available).
  Bootstrap modals (`$('#id').modal('show')`) and `$.getJSON` are the norm. For larger scripts, use the
  `@include('common.general.inline', ['path' => ..., 'options' => [...]])` pattern (see
  `javascript-in-blade-files` skill).

## Discoverability

- **Tools landing page** `admin.tools` → `resources/views/admin/tools/list.blade.php` renders Bootstrap
  `card` blocks grouped by domain. Add an `<li class="list-group-item">` with an `<a>` link + a
  `<small class="text-muted d-block">` description to the relevant card.
- **Admin nav dropdown** `resources/views/common/layout/nav/user.blade.php`, gated by
  `@if($user->hasRole(Role::ROLE_ADMIN))`. Add an `<a class="dropdown-item">` here only for pages that
  warrant top-level visibility. A **count badge** is fed by a shared view variable set in
  `app/Http/View/Composers/GlobalComposer.php` (admin-guarded), rendered as
  `@if($count > 0)<span class="badge badge-warning badge-pill">{{ $count }}</span>@endif` — see
  `numUserReports` / `numCombatLogParseFailures`.

## Translations

- Edit only `lang/en_US/` (other locales are external).
- View strings: `view_admin.tools.<group>.<action>.*` in `lang/en_US/view_admin.php`; tools-card
  labels under the `tools.list.*` block.
- Controller flash/error strings: `controller.admintools.flash.*` and `controller.admintools.error.*`
  in `lang/en_US/controller.php`.
- Nav labels: `view_common.layout.nav.user.*` in `lang/en_US/view_common.php`.

## Combatlog-connection data

Combat-log tables use `protected $connection = 'combatlog'` (a separate DB). Reads cannot join/eager
load across connections — resolve main-DB relations on demand. See the `combatlog-data-pipeline` skill.

## New files checklist

Create files from the host (not via `docker compose exec`), LF endings, and `git add` them. Every new
model also needs a repository — see the `repository-pattern` skill.
