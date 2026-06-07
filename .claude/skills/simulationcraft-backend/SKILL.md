# Skill: SimulationCraft Backend Integration

Use this skill when working on the SimulationCraft export feature — adding/removing raid buffs, changing enemy health calculation, extending the output format, adjusting travel delay logic, or debugging simulation output.

## What it does

The SimulationCraft integration converts a `DungeonRoute` into a SimulationCraft-compatible `fight_style=DungeonRoute` configuration string. The string encodes all kill zones as timed pulls, with per-pull enemy health values scaled to key level and affixes, travel delay between pulls (accounting for mount usage), and per-pull bloodlust assignment. The output is pasted directly into SimulationCraft by the user.

## Entry point & route

```
POST /simulate  (throttle:simulate middleware)
→ AjaxDungeonRouteController@simulate
→ returns ['string' => $configString]
```

The route is defined in `routes/web.php` and named `api.dungeonroute.simulate`.

## Key files

| Purpose | Path |
|---|---|
| Route | `routes/web.php` — search for `api.dungeonroute.simulate` |
| Form request (validation) | `app/Http/Requests/DungeonRoute/AjaxDungeonRouteSimulateFormRequest.php` |
| Controller method | `app/Http/Controllers/Ajax/AjaxDungeonRouteController::simulate` |
| Options model (DB + factory) | `app/Models/SimulationCraft/SimulationCraftRaidEventsOptions.php` |
| Raid buffs enum | `app/Models/SimulationCraft/SimulationCraftRaidBuffs.php` |
| Service interface | `app/Service/SimulationCraft/RaidEventsServiceInterface.php` |
| Service implementation | `app/Service/SimulationCraft/RaidEventsService.php` |
| Collection (top-level output) | `app/Logic/SimulationCraft/RaidEventsCollection.php` |
| Per-pull output & delay calc | `app/Logic/SimulationCraft/RaidEventPull.php` |
| Per-enemy output & health calc | `app/Logic/SimulationCraft/RaidEventPullEnemy.php` |
| Mount intersection value object | `app/Logic/SimulationCraft/Models/MountableAreaIntersection.php` |
| Output interface | `app/Logic/SimulationCraft/RaidEventOutputInterface.php` |
| Tests | `tests/Unit/App/Logic/SimulationCraft/` |
| Test fixture trait | `tests/Fixtures/Traits/CreatesSimulationCraftRaidEventsOptions.php` |

## Data flow

```
HTTP POST /simulate
  → AjaxDungeonRouteSimulateFormRequest   (validates all input fields)
  → SimulationCraftRaidEventsOptions::fromRequest()  (creates & persists options model; gates premium fields)
  → RaidEventsService::getRaidEvents()
      → new RaidEventsCollection(coordinatesService, killZonePathService, options)
          → calculateRaidEvents():
              KillZonePathService::findPathsToKillZones()  (LatLng[] path per kill zone)
              for each KillZone (non-empty):
                  new RaidEventPull → calculateRaidEventPullEnemies(killZone, path)
                      calculateDelay(path)            (seconds, rounded to int)
                      for each Enemy grouped by npc_id:
                          new RaidEventPullEnemy → toString()
          → toString()   (assembles final config string)
  → return ['string' => $result]
```

## Options model fields

`SimulationCraftRaidEventsOptions` is persisted to `simulation_craft_raid_events_options`.

| Field | Type | Notes |
|---|---|---|
| `key_level` | int | Mythic+ key level, 1–40 |
| `affix` | string | Comma-separated: `fortified`, `tyrannical` |
| `thundering_clear_seconds` | int\|null | `null` = Thundering affix inactive |
| `raid_buffs_mask` | int | Bitmask; see enum below |
| `hp_percent` | float | Multiplies all enemy health values |
| `shrouded_bounty_type` | string | `none`, `crit`, `haste`, `mastery`, `vers` |
| `simulate_bloodlust_per_pull` | string | Comma-separated kill zone IDs |
| `ranged_pull_compensation_yards` | int | **Premium** (`ADVANCED_SIMULATION` Patreon benefit) |
| `use_mounts` | bool | **Premium** (`ADVANCED_SIMULATION` Patreon benefit) |

Premium fields are silently forced to `0` for non-Patreon users inside `SimulationCraftRaidEventsOptions::fromRequest()`.

## Raid buffs bitmask

`SimulationCraftRaidBuffs` is an `int`-backed enum. Each case is a power-of-two bit:

| Case | Value | SimulationCraft override key |
|---|---|---|
| `Bloodlust` | 1 | `override.bloodlust` |
| `ArcaneIntellect` | 2 | `override.arcane_intellect` |
| `PowerWordFortitude` | 4 | `override.power_word_fortitude` |
| `MarkOfTheWild` | 8 | `override.mark_of_the_wild` |
| `BattleShout` | 16 | `override.battle_shout` |
| `MysticTouch` | 32 | `override.mystic_touch` |
| `ChaosBrand` | 64 | `override.chaos_brand` |
| `Skyfury` | 128 | `override.skyfury` |
| `HuntersMark` | 256 | `override.hunters_mark` |
| `Bleeding` | 1024 | hardcoded to `0` in output (not toggled) |

Use `$options->hasRaidBuff(SimulationCraftRaidBuffs::X)`, `addRaidBuff()`, and `removeRaidBuff()` — these delegate to the `BitMasks` trait on the model.

## Output format

```
fight_style=DungeonRoute
override.bloodlust=1
override.arcane_intellect=1
override.power_word_fortitude=0
override.mark_of_the_wild=0
override.battle_shout=1
override.mystic_touch=0
override.chaos_brand=0
override.skyfury=0
override.hunters_mark=0
override.bleeding=0
single_actor_batch=1
max_time=1800
enemy="My Route Title"
enemy_health=999999
keystone_bounty=crit
keystone_level=20
raid_events=/invulnerable,cooldown=5160,duration=5160,retarget=1
raid_events+=/pull,pull=01,bloodlust=0,delay=000,enemies="trash-name_1":45000|"trash-name_2":45000
raid_events+=/pull,pull=02,bloodlust=1,delay=012,mark_duration=5,enemies="BOSS_boss-name_1":350000
raid_events+=/pull,pull=03,bloodlust=0,delay=008,enemies="BOUNTY1_shrouded-name_1":55000
```

Notes:
- `keystone_bounty` line is omitted when `shrouded_bounty_type` is `none`.
- `mark_duration` is only present when `thundering_clear_seconds` is set.
- `delay` is zero-padded to 3 digits; `pull` to 2 digits.
- `max_time` comes from `$dungeonRoute->mappingVersion->timer_max_seconds`.

### Enemy name prefixes

| Prefix | Condition |
|---|---|
| _(none)_ | Regular trash |
| `BOSS_` | `npc->classification_id === NpcClassification::NPC_CLASSIFICATION_BOSS` |
| `BOUNTY1_` | `enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED` |
| `BOUNTY3_` | `enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX` |

The name itself is `Str::slug(__($enemy->npc->name)) . '_' . $enemyIndexInPull` (index resets per NPC group per pull).

## Travel delay calculation

`RaidEventPull::calculateDelay(LatLng[] $path)` receives waypoints from `KillZonePathService::findPathsToKillZones()`. It:

1. **Skips segments where the floor ID changes** — floor transitions are assumed instantaneous.
2. **Applies ranged pull compensation only to the last same-floor segment** — reduces that segment's in-game distance by `$options->ranged_pull_compensation_yards` before computing time.
3. For each remaining segment, calls `calculateDelayBetweenPoints()`:
   - Converts map coordinates to in-game yards via `CoordinatesService`.
   - Calls `calculateMountedFactorAndMountCastsBetweenPoints()` which intersects the path line against all `MountableArea` polygons on the floor to get a list of `{factor, speed}` entries (fraction of distance mounted at a given speed) and the number of times the player mounts up.
   - Sums `delayMounted` + `delayOnFoot` + `delayMountCasts`.
   - **Optimization**: if going entirely on foot is faster than the above (mount overhead dominates a short distance), uses full on-foot time instead.

**Speed constants** (read from config, not hardcoded):
- Walking: `config('keystoneguru.character.default_movement_speed_yards_second')`
- Mount cast time: `config('keystoneguru.character.mount_cast_time_seconds')`
- Mounted speed: per `MountableArea::getSpeedOrDefault()`

## How to extend

### Add a new raid buff

1. Add a new case to `SimulationCraftRaidBuffs` with the next available power-of-two value.
2. Add the corresponding `override.<key>=%d` line in `RaidEventsCollection::toString()`, passing `$this->options->hasRaidBuff(SimulationCraftRaidBuffs::NewBuff) ? 1 : 0`.
3. Update `AjaxDungeonRouteSimulateFormRequest` if the front end needs to send it.
4. The bitmask logic in `SimulationCraftRaidEventsOptions` requires no changes.

### Add a new enemy name prefix

In `RaidEventPullEnemy::toString()`, add an `elseif` branch that checks the relevant `$this->enemy->seasonal_type` or `$this->enemy->npc->classification_id` and applies the prefix via `sprintf('PREFIX_%s', $name)`.

### Change the delay calculation

Walking speed and mount cast time are in `config/keystoneguru.php` under `character.default_movement_speed_yards_second` and `character.mount_cast_time_seconds`. Per-area mount speeds are on the `MountableArea` model. The algorithmic logic lives in `RaidEventPull::calculateDelayBetweenPoints()` and `calculateMountedFactorAndMountCastsBetweenPoints()`.

### Add a new top-level SimulationCraft field

Add the field to the `sprintf` in `RaidEventsCollection::toString()`. Source data should come from `$this->options` (model fields) or `$this->options->dungeonRoute` (route metadata). If it requires a new user-configurable input, add it to:
- `AjaxDungeonRouteSimulateFormRequest` (validation)
- `SimulationCraftRaidEventsOptions` (`$fillable`, `casts()`, and `fromRequest()`)
- The relevant migration

## Running tests

```bash
docker compose exec -T app php artisan test --compact --group=SimulationCraft
```

To run a single test file:

```bash
docker compose exec -T app php artisan test --compact tests/Unit/App/Logic/SimulationCraft/RaidEventPullTest.php
```
