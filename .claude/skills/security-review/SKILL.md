`---
name: security-review
description: The backend security model of keystone.guru and how to review it — authentication (Laratrust, Basic-auth API), authorization (Gate/policies on the write surface), CSRF posture, request-input safety, and the areas already verified safe so they are not re-investigated. Use when doing a security review, adding an endpoint, or touching auth/CSRF/SQL/authorization. Not for front-end security or dependency CVEs.
---

# Security Review

How authentication, authorization, and request-input safety work in this backend, plus a checklist
for reviewing changes and the areas already checked (so a review doesn't re-litigate settled ones).

## Authentication

- **Web**: session-based, Laravel auth + Socialite for OAuth login. Roles/permissions via
  **Laratrust** (`App\Models\Laratrust\Role`, e.g. `Role::ROLE_ADMIN`). `User::$is_admin` ==
  `hasRole(Role::ROLE_ADMIN)`.
- **API (`/api/v1`)**: **HTTP Basic auth**, applied to the *entire* api group by the
  `ApiAuthentication` middleware (wired in `bootstrap/app.php` `$middleware->api([...])`). There are
  **no API keys**. The internal-team endpoints add `api_role:admin` (Laratrust role check). So the
  API has no anonymous surface — treat all `/api/v1` routes as authenticated.

## Authorization

- Policies live in `app/Policies/` (one per per-owner resource: `DungeonRoute`, `Team`,
  `LiveSession`, `Season`, `Dungeon`, `Expansion`, `GameVersion`, `Tag`, `TagCategory`).
- The **write surface is the Ajax map editor** (`app/Http/Controllers/Ajax/`), and it enforces
  ownership with `Gate::authorize('edit'|'view'|'addKillZone'|..., $dungeonRoute)` **in every
  mutating controller method** (including a re-authorize-after-reload guard in
  `AjaxKillZoneController` against cross-route hijacking).
- **Admin mapping mutations** (enemy, enemypack, mapicon, mountablearea, floorunion, …) are gated at
  the route level by `Route::middleware(['auth', 'role:admin'])` in `routes/web.php`.
- **Do not be alarmed by "9 policies for 150+ models."** The models without a policy are
  mapping/reference data gated by role at the route level, not per-owner resources — the ratio is
  *not* a finding.

## CSRF

- CSRF token validation is **enabled**, excepting only external webhooks:
  `validateCsrfTokens(except: ['webhook/*'])` in `bootstrap/app.php`. Do **not** re-broaden this to
  `['*']`.
- The front-end already sends tokens everywhere: the `<meta name="csrf-token">` tag in
  `layouts/app.blade.php`, `$.ajaxSetup` injecting `X-CSRF-TOKEN` (`resources/assets/js/app.js`),
  and `@csrf` in every traditional form. New Ajax and forms inherit this automatically.
- `config/session.php` pins `same_site` to `lax`. **SameSite=lax does not protect top-level GET
  navigations**, so any state-changing action **must** use POST/PUT/PATCH/DELETE — never a
  state-changing `Route::get(...)`.

## Request-input safety

- **Validation** goes in Form Requests (`app/Http/Requests/`), never inline in controllers. IDs use
  `exists` rules (`['exists:users,id']`) with a cached getter that returns the model.
- **Never use `env()` outside `config/`** — read via `config(...)`. (Verified zero violations in
  `app/`.)
- **Raw SQL**: prefer Eloquent / query builder. If raw is unavoidable, values from the request must
  be bound parameters, and any interpolated identifier must be from a server-side whitelist — never
  request input.

## Already verified safe — do NOT re-flag

- **Datatables raw SQL** (`app/Logic/Datatables/**`, `orderByRaw`/`selectRaw`) is **not injectable**:
  client-supplied column names pass through the `SimpleColumnHandler::VALID_COLUMN_NAMES` whitelist
  (non-whitelisted columns ignored), search values are bound `LIKE` parameters, sort direction is
  coerced to a literal `asc`/`desc`, and the one interpolated value (`$currentAffixId`) is
  server-derived.
- **Ajax/API authorization** is consistent (Gate + role gating, as above).
- **API is fully authenticated** (Basic auth on the whole group).

## Review checklist for a new/changed endpoint

1. Validated by a Form Request (with `exists` rules for foreign IDs)?
2. Authorized — `Gate::authorize`/policy for owned resources, or `role:` middleware for admin/global?
3. Correct HTTP verb — **no state-changing GET** (bypasses CSRF)?
4. Rate-limited if it is expensive or widely reachable (named `throttle:` limiters exist for
   combatlog/thumbnail)?
5. No raw SQL built from request input; no `env()` outside config.
6. `preventLazyLoading` is on (throws in dev, logs in prod) — don't disable it to paper over N+1.

## Filing findings

The repo (`RaiderIO/keystone.guru`) is **public**.

- Architecture / performance / test-gap findings → **public GitHub issues** (see the
  **create-github-issue** skill), cross-linked into the triage trackers (#3374 planned / #3375 rest).
- **Security-sensitive findings → a private local report, NOT a public issue**, and never a public
  issue containing an exploit recipe. A fix can be described publicly *after* it ships.

## Related

- **project-backend-structure** (layering), **repository-pattern**, **api-endpoint**,
  **laravel-best-practices**, **create-github-issue**.
