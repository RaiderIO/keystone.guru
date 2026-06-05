# Skill: Expose an Artisan Command in the Admin Panel

Use this skill when asked to make an artisan command runnable from the admin panel.

## What exists

A generic "Artisan Commands" section already lives in the admin tools hub at `/admin/tools`.
Long-running commands use a **frontend-driven chunked loop** (jQuery + Bootstrap progress bar) so a single HTTP request never times out.

### Key files

| Purpose | Path |
|---|---|
| Generic run endpoint | `app/Http/Controllers/AdminTools/AdminToolsArtisanCommandsController.php` |
| Whitelist | `ALLOWED_COMMANDS` const in the controller above |
| Admin tools list card | `resources/views/admin/tools/list.blade.php` — "Artisan Commands" card |
| Routes | `routes/web.php` — search for `artisan-commands` |
| Breadcrumbs | `routes/breadcrumbs.php` — search for `artisancommands` |
| JS base class pattern | `resources/assets/js/custom/inline/admin/tools/artisancommands/backfillkillzoneenemyid.js` — state machine with pause/stop/resume, timer, ETA, DOM-appended log |
| Blade view pattern | `resources/views/admin/tools/artisancommands/backfillkillzoneenemyid.blade.php` |
| Tests | `tests/Feature/Controller/AdminTools/AdminToolsArtisanCommandsControllerTest.php` |

## Steps to add a new command

### 1. Whitelist the command
In `AdminToolsArtisanCommandsController::ALLOWED_COMMANDS` add the artisan signature string.

### 2. Add `--min` / `--max` if the command processes large datasets
If the command can take a long time, add `{--min=}` and `{--max=}` options (matching the `BackfillKillZoneEnemyId` pattern) so the frontend can chunk work into many small requests.

### 3. Create an Inline Code JS file
- Path: `resources/assets/js/custom/inline/admin/tools/artisancommands/<commandname>.js`
- Class name: `AdminToolsArtisancommands<Commandname>` (PascalCase, folder + filename)
- Extends `InlineCode`; implement `activate()`
- Copy the chunking loop from `backfillkillzoneenemyid.js` if chunking is needed; simplify to a single `$.ajax` call if the command is fast
- After creating, **the user must restart `npm run watch` / `npm run dev`** for the new file to compile

### 4. Create the Blade view
- Path: `resources/views/admin/tools/artisancommands/<commandname>.blade.php`
- Extends `layouts.sitepage` with `['showAds' => false]`
- Pass DB-queried status values from PHP into the `@include('common.general.inline', ...)` options array
- CSRF for AJAX is handled globally by `app.js` via `$.ajaxSetup` — no manual setup needed

### 5. Add a GET route
In `routes/web.php` inside the `admin/tools` prefix group:
```php
Route::get('artisan-commands/<command-slug>', new AdminToolsArtisanCommandsController()-><methodName>(...))->name('admin.tools.artisancommands.<name>.view');
```
The POST `artisan-commands/run` route is already shared — no new POST route needed per command.

### 6. Add a list-card entry
In `resources/views/admin/tools/list.blade.php`, add a `<li>` inside the existing "Artisan Commands" card.

### 7. Add translation keys
- `lang/en_US/view_admin.php` — `tools.list` (card link label + description) and `tools.artisancommands.<name>` (page title, header, description, etc.)
- `lang/en_US/breadcrumbs.php` — `home.admin.tools.<key>`

### 8. Add a breadcrumb
In `routes/breadcrumbs.php`:
```php
Breadcrumbs::for('admin.tools.artisancommands.<name>', static function (Generator $trail) {
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.<key>'), route('admin.tools.artisancommands.<name>.view'));
});
```

### 9. Write tests
- Controller test in `tests/Feature/Controller/AdminTools/AdminToolsArtisanCommandsControllerTest.php`
  — add GET 200 and POST whitelist tests following the existing pattern
- Add breadcrumb key to `tests/Feature/Breadcrumbs/AdminToolsBreadcrumbsTest.php` data provider

### 10. Run quality tools
```
docker compose exec -T app composer run fix
docker compose exec -T app composer run analyse
docker compose exec -T app php artisan test --compact tests/Feature/Controller/AdminTools/AdminToolsArtisanCommandsControllerTest.php
docker compose exec -T app php artisan test --compact tests/Feature/Breadcrumbs/AdminToolsBreadcrumbsTest.php
```
