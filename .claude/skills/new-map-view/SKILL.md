---
name: new-map-view
description: Step-by-step guide for creating a new page that renders a dungeon map — blade anatomy, JS wiring, sidebar controls, map context, and hidden object groups.
---

# Creating a New Map View in a Blade File

## Architecture Overview

```
layouts/map.blade.php              ← extends base layout (no ads, no header/footer)
  └── common/maps/map.blade.php    ← map orchestrator; receives $show, $mapContext, $dungeon, $floor, etc.
        ├── Sidebar control        ← @if($show['controls']['yourControl'])
        │     └── yourcontrol.blade.php
        │           └── @include('common.general.inline', path: 'common/maps/yourcontrol', options: [...])
        └── Leaflet map canvas
```

The main map orchestrator (`common/maps/map.blade.php`) handles the Leaflet map init,
plugin loading, and map context. Your job is only:
1. Pass the right `$show` and `$mapContext` from the controller.
2. Add a sidebar blade + JS file for your new control.

## Step-by-Step

### 1. Controller method

```php
public function myAdminPage(MapContextServiceInterface $mapContextService): View
{
    $dungeon        = Dungeon::getUserOrDefaultDungeon();
    $mappingVersion = $dungeon->getCurrentMappingVersion();
    $floor          = Floor::where('dungeon_id', $dungeon->id)
                           ->defaultOrFacade($mappingVersion)
                           ->first();
    $mapContext     = $mapContextService->createMapContextDungeonExplore(
        $dungeon,
        $mappingVersion,
        User::getCurrentUserMapFacadeStyle(),
    );

    return view('admin.tools.mypage', compact('dungeon', 'floor', 'mappingVersion', 'mapContext'));
}
```

Always build `MapContextDungeonExplore` via `MapContextServiceInterface::createMapContextDungeonExplore()` — never via `app()->make()`.

### 2. Main view blade

`resources/views/admin/tools/mypage.blade.php`:

```blade
@extends('layouts.map', ['title' => 'My Page'])

@section('content')
    @include('common.maps.map', [
        'mapContext'            => $mapContext,
        'dungeon'               => $dungeon,
        'floor'                 => $floor,
        'mappingVersion'        => $mappingVersion,
        'admin'                 => true,
        'edit'                  => false,
        'hiddenMapObjectGroups' => ['brushline', 'path', 'killzone', 'killzonepath'],
        'show'                  => [
            'header'   => true,
            'controls' => [
                'myControl' => true,
            ],
        ],
    ])
@endsection
```

Reference pattern: `resources/views/admin/floor/mapping.blade.php`.

### 3. Wire the control in `map.blade.php`

`resources/views/common/maps/map.blade.php` uses a series of `@if` blocks for sidebar
controls. Add yours as a new `@if` block (not chained to existing ones):

```blade
@if(isset($show['controls']['myControl']) && $show['controls']['myControl'])
    @include('common.maps.controls.mycontrol', [...options...])
@endif
```

Existing pattern reference (lines 353–360):
```blade
@if(isset($show['controls']['heatmapSearch']) && $show['controls']['heatmapSearch'])
    @include('common.maps.controls.heatmapsearch', ...)
@endif
```

### 4. Sidebar control blade

`resources/views/common/maps/controls/mycontrol.blade.php`:

```blade
<nav id="mycontrol_sidebar" class="route_sidebar leaflet-sidebar collapsed">
    <div class="leaflet-sidebar-tabs">...</div>
    <div class="leaflet-sidebar-content">
        {{-- Filter inputs, buttons, etc. --}}
    </div>
</nav>

@include('common.general.inline', [
    'path'    => 'common/maps/mycontrol',
    'options' => [
        'dungeonId' => $dungeon->id,
        'deleteUrl' => route('ajax.admin.mycontrol.delete'),
        // ... any other PHP → JS values
    ],
])
```

### 5. Inline JavaScript class

`resources/assets/js/custom/inline/common/maps/mycontrol.js`

**Naming rule**: path segments are capitalised and concatenated.
`common/maps/mycontrol` → class name `CommonMapsMycontrol`.

```js
/**
 * @typedef {Object} CommonMapsMycontrolOptions
 * @property {Number} dungeonId
 * @property {String} deleteUrl
 */

/**
 * @property {CommonMapsMycontrolOptions} options
 */
class CommonMapsMycontrol extends SearchInlineBase {
    constructor(id, bladePath, options) {
        super(id, bladePath, options, new SearchHandlerMyControl());
        this.filters['dungeonId'] = new SearchFilterPassThrough('dungeon_id', options.dungeonId);
    }

    activate() {
        super.activate();
        // Make the heat layer visible immediately (required when using HeatPlugin)
        getState().getDungeonMap().pluginHeat.toggle(true);
        // Bind any buttons
        $('#my_delete_btn').on('click', () => {
            $.ajax({ url: this.options.deleteUrl, type: 'DELETE' })
                .done(() => this._search());
        });
        this._search();
    }
}
```

**Auto-bundling**: `webpack.mix.js` uses the glob `'resources/assets/js/custom/inline/*/**/*.js'`
so any file placed under `resources/assets/js/custom/inline/` is automatically compiled.
No manual registration is needed. After creating a new inline JS file, restart `npm run watch`
(or run `npm run build`) for it to be picked up.

## Map Context Classes

| Class | Use case |
|---|---|
| `MapContextDungeonExplore` | Read-only dungeon map (enemies visible, no route editing). Best for admin views. HeatPlugin only works with this context — `HeatPlugin.isEnabled()` checks `instanceof MapContextDungeonExplore`. |
| `MapContextMappingVersionEdit` | Full edit mode (admin floor mapping). |
| `MapContextDungeonRoute` | Shows an existing saved dungeon route. |

Factory method (always use this, not `app()->make()`):
```php
$mapContextService->createMapContextDungeonExplore($dungeon, $mappingVersion, User::getCurrentUserMapFacadeStyle())
```

## `hiddenMapObjectGroups`

Pass group names to hide map layers irrelevant to your view:

| Group name | What it hides |
|---|---|
| `brushline` | Drawn brush lines |
| `path` | Route paths |
| `killzone` | Kill zone circles |
| `killzonepath` | Lines between kill zones |
| `mapicon` | Map icons (notes, portals, etc.) |
| `enemy` | Enemy markers (keep visible for heatmap views) |

Example for a heatmap view that shows only enemies:
```php
'hiddenMapObjectGroups' => ['brushline', 'path', 'killzone', 'killzonepath'],
```

## `$show` defaults in `map.blade.php`

```php
$show['controls']['enemyInfo']       ??= true;
$show['controls']['pulls']           ??= true;
$show['controls']['heatmapSearch']   ??= false;
$show['controls']['draw']            ??= false;
$show['controls']['view']            ??= false;
```

If your view should hide pulls and enemy info, explicitly pass them as false:
```php
'show' => ['controls' => ['pulls' => false, 'enemyInfo' => false, 'myControl' => true]]
```

## Blade → JS Path Resolution

`InlineManager` converts the `path` string to a JS class name by capitalising
each path segment:

| Path string | JS class name |
|---|---|
| `common/maps/heatmapsearchsidebar` | `CommonMapsHeatmapsearchsidebar` |
| `common/maps/mycontrol` | `CommonMapsMycontrol` |
| `admin/tools/mypage` | `AdminToolsMypage` |

All file names under `resources/assets/js/custom/inline/` must be **lowercase**.
