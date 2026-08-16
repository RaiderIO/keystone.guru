<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Laravel Boost overrides
|--------------------------------------------------------------------------
|
| Only the keys we deliberately override live here; everything else is merged
| in from the package default (BoostServiceProvider::mergeConfigFrom), so this
| file does not need to be kept in sync with the shipped config.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Excluded Guideline Packs
    |--------------------------------------------------------------------------
    |
    | `boost:update` regenerates the <laravel-boost-guidelines> block in the
    | root CLAUDE.md, which every agent session loads before `.claude/CLAUDE.md`.
    | These packs contradict this project's own rules, so they are dropped and
    | the parts worth keeping are re-stated (corrected) in
    | `.ai/guidelines/keystone-overrides.md`:
    |
    | - `laravel/core`  - tells agents to use `php artisan make:` (produces
    |                     root-owned files under WSL, see #3414) and references
    |                     Vite / `npm run build`, neither of which this project
    |                     uses (it builds via `scripts/build/build.mjs`).
    | - `boost`         - tells agents to run Artisan and Tinker directly on the
    |                     host, contradicting the Docker-only execution rule.
    | - `deployments`   - advertises Laravel Cloud; this project deploys via
    |                     GitHub Actions to ECS.
    | - `phpunit/core`  - recommends `php artisan make:test` and host-run test
    |                     commands; the `writing-tests` skill carries this
    |                     project's actual PHPUnit conventions.
    |
    | The `tests` pack ("every change must be programmatically tested") is
    | deliberately kept - it matches this project's policy.
    |
    | Note: `boost.guidelines.exclude` is not present in the package's shipped
    | config, so diff the regenerated CLAUDE.md after every Boost upgrade in
    | case the key is renamed or dropped.
    |
    */

    'guidelines' => [
        'exclude' => [
            'laravel/core',
            'boost',
            'deployments',
            'phpunit/core',
        ],
    ],

];
