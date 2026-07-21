---
name: ajax-map-editor-crud
description: The map editor's server-side CRUD layer — AjaxMappingModelBaseController, the Ajax*Controller pattern, FormRequests, routes/web.php ajax groups, broadcast events (ContextEvent/ModelChangedEvent/ModelDeletedEvent), presence channels in routes/channels.php, and the checklist for adding a new editable map-object type. Use when adding or changing an ajax map endpoint, a broadcast event, or debugging collaborative-editing sync. Not for the model versioning itself (mapping-versioned-models), the front-end Leaflet layer (new-map-view), or public API endpoints (api-endpoint).
---

# Ajax Map-Editor CRUD & Broadcast Events

## Overview

The map editor's async CRUD is ~30 `Ajax*Controller` classes in `app/Http/Controllers/Ajax/`,
one per object type, mostly extending `AjaxMappingModelBaseController`. Every successful mutation
is (a) written to a change log and (b) broadcast over a Reverb presence channel so other clients
editing the same route/mapping see it live. There are **no PHP listeners** — events go straight
to the front-end via Echo.

## The base controller

`app/Http/Controllers/Ajax/AjaxMappingModelBaseController.php` (abstract, `use ChangesMapping`).
Core flow — `storeModel(CoordinatesServiceInterface $coordinatesService, ?MappingVersion $mappingVersion, array $validated, string $modelClass, ?MappingModelInterface $model = null, ?Closure $onSaveSuccess = null, ?Model $echoContext = null): Model`, all inside `DB::transaction`:

1. Forces `$validated['mapping_version_id'] = $mappingVersion?->id`.
2. Snapshots `$beforeModel` (null on create).
3. `$modelClass::create($validated)` if `$model === null`, else `$model->update($validated)` —
   **the same `store` method serves both POST (create) and PUT (update)**; create-vs-update is
   decided by whether the route-bound `?Model` param is null.
4. Loads `['mappingVersion', 'floor', 'floor.dungeon']`, then runs the optional
   `$onSaveSuccess($model)` closure (subclasses do side-effects here: aura sync, floor changes).
5. If `shouldCallMappingChanged($beforeModel, $model)` (overridable, default true) →
   `$this->mappingChanged(...)` writes a `MappingChangeLog` row.
6. If `Auth::check()`: `$echoContext ??= $floor?->dungeon;` then
   `broadcast($this->getModelChangedEvent($coordinatesService, $echoContext, Auth::user(), $model))`.

Every subclass implements the abstract
`getModelChangedEvent(...): ModelChangedEvent`. **Delete is NOT in the base** — each subclass
writes its own `delete()`: `DB::transaction` → `$model->delete()` →
`$this->mappingChanged($model, null)` → broadcast `<Type>DeletedEvent` if authed → `response()->noContent()`.

Change-log traits (`app/Http/Controllers/Traits/`):

- `ChangesMapping::mappingChanged(?before, ?after)` — admin mapping audit (`MappingChangeLog`).
- `ChangesDungeonRoute::dungeonRouteChanged(DungeonRoute, ?before, ?after)` — team-route audit
  (`DungeonRouteChange`; skipped for non-team routes). Route-scoped objects use this instead.

Reference implementations: `AjaxEnemyController` (admin/mapping-version scoped) and
`AjaxMapIconController` (dual-scoped: `adminStore`/`dungeonRouteStore` thin wrappers around one
central `store`, overrides `shouldCallMappingChanged()` so only global icons hit the mapping
change log, and passes `$dungeonRoute` as `$echoContext` so route icons broadcast on the route
channel).

## FormRequests

Live in `app/Http/Requests/<Type>/` (e.g. `Enemy/APIEnemyFormRequest.php`,
`MapIcon/MapIconFormRequest.php`). `authorize()` returns `true` — real authorization is route
middleware or in-controller Gates (see Gotchas). Rules use `Rule::exists(...)`/`Rule::in(...)`;
some use the `CastInputData` trait + `prepareForValidation()` to coerce input types.

## Routing — `routes/web.php`

All ajax endpoints sit under `Route::prefix('ajax')->middleware('ajax')` (the `OnlyAjax` alias),
which is itself inside the site-wide group that includes `read_only_mode`.

- **Admin mapping endpoints**: `middleware(['auth', 'role:admin'])` → `prefix('admin')` →
  `prefix('mappingVersion/{mappingVersion}')`. Per type: `POST /enemy` + `PUT /enemy/{enemy}`
  both → `store`, `DELETE /enemy/{enemy}` → `delete`.
- **Route-scoped endpoints**: `Route::prefix('{dungeonRoute}')` with **no auth middleware**
  (the sandbox works logged-out); authorization happens inside controllers via
  `Gate::authorize('edit', $dungeonRoute)` (+ ability-specific gates like `addMapIcon`).

## Broadcast events — `app/Events/`

```
ContextEvent (abstract, ShouldBroadcast)          __construct(Model $context, User $user)
└── Models/ContextModelEvent                      adds Model $model to the payload
    └── Models/ModelChangedEvent                  base for all <Type>ChangedEvent
ContextEvent
└── Models/ModelDeletedEvent                      base for all <Type>DeletedEvent —
                                                  stores model_id/model_class (model is gone)
```

- `ContextEvent::broadcastOn()` picks the presence channel from the **`$context` model type**:
  `DungeonRoute` → `{app.type}-route-edit.{routeKey}`, `LiveSession` →
  `{app.type}-live-session.{routeKey}`, `Dungeon` → `{app.type}-mapping-version-edit.{routeKey}`.
- `broadcastWith()` always includes `__name` (= `broadcastAs()`), context info, and
  `user` (`color`, `name`, `public_key` — the key self-echo filtering depends on).
- Concrete events live in `app/Events/Models/<Type>/`: `<Type>ChangedEvent` (constructor
  `(CoordinatesServiceInterface, Model $context, User, <Type> $model)`, `broadcastAs()` returns
  the hyphenated name like `'enemy-changed'`, and adds
  `'model_data' => $model->getCoordinatesData($coordinatesService)` for coordinate-bearing
  models) and `<Type>DeletedEvent` (usually just `broadcastAs(): '<type>-deleted'`).

## Channels — `routes/channels.php`

Presence channels (server prepends `presence-`): `route-edit` / `live-session` /
`route-compare` authorize via a shared callback returning a user-info array (honors
`echo_anonymous` with randomized "Anonymous X" identities); `mapping-version-edit` requires
`Role::ROLE_ADMIN`. The front-end joins the channel named by the map context —
`app/Logic/MapContext/Map/MapContextBase::getEchoChannelName()` builds the **exact same string**
as `ContextEvent::broadcastOn()`; if you change one, change the other.

## Front-end receiving side (pointer)

`resources/assets/js/custom/echo/echohandler.js` joins the presence channel and wires handler
classes; `messagehandler/messagehandler.js` does `presenceChannel.listen('.<name>', ...)`; raw
events are mapped by `e.__name` through the big switch in `message/messagefactory.js` to message
classes in `message/listen/models/<type>/changed.js|deleted.js`. **There is no server-side
registry** — the PHP `broadcastAs()` strings and the JS `MessageFactory` switch are the coupling.

## Checklist — adding a new editable map-object type (server side)

1. Model implements `MappingModelInterface` (+ `HasLatLng`/`HasVertices` traits if it has
   coordinates — that's what powers `getCoordinatesData()`). See **mapping-versioned-models**
   for the versioning wiring.
2. Controller in `app/Http/Controllers/Ajax/` extending `AjaxMappingModelBaseController`:
   `store()` delegating to `storeModel(...)`, a `delete()` following the sibling pattern, and
   `getModelChangedEvent()`. Override `shouldCallMappingChanged()` if dual-scoped (MapIcon).
3. FormRequest in `app/Http/Requests/<Type>/`.
4. Events `app/Events/Models/<Type>/<Type>ChangedEvent.php` + `<Type>DeletedEvent.php`.
5. Routes in the right `routes/web.php` ajax subgroup (admin mappingVersion prefix and/or
   `{dungeonRoute}` prefix); POST + PUT → `store`, DELETE → `delete`.
6. Channels: usually none — reuse the existing channels by passing the right `$context`.
7. Front-end echo (only if live sync is needed): message classes + `messagefactory.js` switch +
   handler under `messagehandler/listen/models/<type>/` + registration in the `EchoHandler`
   constructor's `_handlers` array.

## Gotchas

- `OnlyAjax` middleware 403s any non-XHR request **except** when `app.env === local` — curl
  tests pass locally but fail in production-like envs.
- `read_only_mode` wraps the whole ajax group: non-GET requests return 503 during read-only
  (whitelist: login, logout, heatmap data).
- **No `->toOthers()` anywhere** — the sender receives its own broadcast; self-filtering is
  client-side by comparing `user.public_key`. Break the `user` payload and every client
  re-applies its own edits.
- Broadcasts are gated on `Auth::check()` — anonymous sandbox edits save but never broadcast.
- Route-scoped endpoints have **no auth middleware**; forgetting `Gate::authorize('edit',
  $dungeonRoute)` in a new controller method is an authorization hole.
- `ModelDeletedEvent` snapshots `getRouteKey()`/`::class` in its constructor, so constructing it
  with an already-deleted model is fine — but broadcast only after `delete()` succeeded.
- Known asymmetry: `AjaxEnemyController` broadcasts `enemy-changed`/`enemy-deleted`, but the JS
  MessageFactory only wires `npc-*`/`overpulledenemy-*` — verify the JS side actually listens
  for your event name before assuming live sync works.

## Related skills

- **mapping-versioned-models** — versioning/cloning of the models these endpoints edit
- **new-map-view** — building the page that renders the map
- **javascript-in-blade-files** — the Inline Code JS conventions
- **api-endpoint** — the public REST API (different layer entirely)
