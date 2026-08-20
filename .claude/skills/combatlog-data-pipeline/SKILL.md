---
name: combatlog-data-pipeline
description: End-to-end guide to how WoW combat logs are parsed into the combatlog DB — the streaming reader, the ordered data-extractor pipeline, the observation (rolling window) vs event (audit) tables, the hourly staleness sweep, and how the NPC Compendium front-end reads the resulting feed. Use when touching combat log extraction, NPC/spell observations or events, or the NPC Compendium.
---

# Combat Log Data Pipeline

How a raw WoW combat log file becomes NPC/spell mapping data and the NPC Compendium activity feed.

## Two databases

The pipeline straddles two connections:

- **Main DB** — canonical mapping data that the rest of the app consumes: `npcs`, `spells`,
  `npc_characteristics`, `npc_spells`, `spell_dungeons`, `npc_dungeons`, `floors`.
- **`combatlog` connection** — the audit/observation store. All four observation/event models set
  `protected $connection = 'combatlog'`: `combat_log_npc_characteristic_observations`,
  `combat_log_spell_property_observations`, `combat_log_npc_events`, `combat_log_spell_events`, plus
  `parsed_combat_logs`.

Because reads cross connections, the Compendium feed cannot use normal joins/eager loads — related
main-DB models are attached by hand (`setRelation`). See `NpcCompendiumService`.

## Observation vs Event (the load-bearing distinction)

Two different "combat log" record types — **do not conflate them**:

- **Observation** = a rolling-window "we saw X today" heartbeat. Upserted on **every** sighting while
  parsing, unique per `(npc_id|spell_id, characteristic_id|property, observed_on)` — so at most one row
  per fact per day. Its only job is to keep a recency window alive. Tables:
  `combat_log_npc_characteristic_observations`, `combat_log_spell_property_observations`.
- **Event** = a write-once audit record (`created_at` only, `UPDATED_AT = null`) emitted **only when
  canonical mapping state actually changes** — a fact was newly added, assigned, property-changed, or
  removed. Tables: `combat_log_npc_events`, `combat_log_spell_events`. **These are what the NPC
  Compendium activity feed reads.**

Relationship: on ingest, an observation is *always* upserted; an event is written *only* when the
observation represents a new fact (e.g. `NpcCharacteristic::firstOrCreate` → `wasRecentlyCreated` →
emit `CharacteristicAdded`). On the hourly sweep, facts with no fresh observation are removed and a
`*Removed` event is emitted. **Observations decide whether facts stay alive; events record the moments
facts appeared or disappeared.**

> ⚠️ **Caveat:** `App\Models\CombatLog\CombatLogEvent` (backed by OpenSearch index `combat_log_events`)
> is a **completely separate concept** — positional analytics (player/enemy deaths & spell casts with
> grid coords) for heatmaps and run analysis. It does **not** participate in this observation→event
> pipeline. Don't let the name mislead you.

## End-to-end flow

```
combatlog:pollruns (Raider.IO)        External S3 upload (dispatcher lives outside this repo)
        │                                     │
        ▼                                     ▼
ProcessCombatLogSegments        ProcessCombatLogFanout → ProcessCombatLogFromS3 (per file)
   (queue *-cl-process)          (queue *-cl-fanout)      (queue *-cl-process)
        └──────────────┬───────────────────────┘
                       ▼   (also: combatlog:extractdata CLI, extractDataAsync analyze flow)
        CombatLogDataExtractionService::extractData()
          - idempotency guard via ParsedCombatLog (combat_log_path) unless --force
          - streams lines via CombatLogService::parseCombatLog (fgets, transparent .zip unzip)
          - CombatLogEntry::parseEvent → BaseEvent / AdvancedCombatLogEvent / SpecialEvent
          - resolves current dungeon (ChallengeModeStart / ZoneChange)
          - runs fixed, ORDER-SENSITIVE extractor pipeline per event
                       │
     ┌─────────────────┼──────────────────────────┐
     ▼                 ▼                            ▼
UPSERT observations   mutate canonical mapping   INSERT audit events
(rolling window)      (npcs/spells/npc_spells…)  (Added/Assigned/Changed/Created)
     │
     │ hourly: combatlog:detectstaledata
     ▼
 fact not observed within window → remove canonical mapping + INSERT *_Removed event; prune old observations
                       │
                       ▼
 NpcCompendiumService (buildEventFeed / getEventsForDate / getActivityDates)
 reads combat_log_npc_events + combat_log_spell_events → NPC Compendium front-end
```

## Ingestion entry points

Everything funnels into `CombatLogDataExtractionService::extractData(string $filePath, ?bool $force,
?callable $onProcessLine, ?CombatLogRunContextInterface $runContext)`:

- `ProcessCombatLogFromS3::handle()` — S3 fanout path (queue `*-cl-process`, timeout 1800).
- `ProcessCombatLogSegments::handle()` — Raider.IO segment path (`ShouldBeUnique`, tries 3, backoff
  `[30,120]`); dispatched by `PollCombatLogRunsCommand` (`combatlog:pollruns`).
- `combatlog:extractdata {filePath} {--force}` (`ExtractData.php`) — manual/local CLI.
- `extractDataAsync()` — wraps `extractData` updating a `CombatLogAnalyze` progress row (interactive
  "analyze" flow).

The reader (`CombatLogService::parseCombatLog`) streams line-by-line with `fgets` (never loads the
whole file), transparently unzips `.zip` inputs into `/tmp`, and toggles `combatLogVersion` /
advanced-logging when it hits a `COMBAT_LOG_VERSION` header. Only advanced-logging lines are extracted.
Line→event parsing is `CombatLogEntry::parseEvent`.

## Extractor pipeline (hard-coded, order-sensitive)

Built by `DataExtractorFactory::createExtractors()` (bound to `DataExtractorFactoryInterface`, which the
extraction service takes in its constructor) as an ordered collection, then iterated for
`beforeExtract` → per-line `extractData` → `afterExtract`. **DB writes are flushed in `afterExtract`**
(batched). The set is hard-coded in the factory, not config; swapping it means binding another
`DataExtractorFactoryInterface` instance — `ProfilingDataExtractorFactory` (benchmark) and
`FixedDataExtractorFactory` (on-demand commands, see "NPC health from logs" below) are the two precedents.

1. `CreateMissingNpcDataExtractor` — creates missing `Npc` + `NpcDungeon` (skips pets & summoned NPCs).
2. `SpellDataExtractor` — orchestrates ordered sub-collectors (below).
3. `NpcCharacteristicDataExtractor` — on `SPELL_AURA_APPLIED` targeting a creature where the spell maps
   to a characteristic: upserts a characteristic **observation**, `NpcCharacteristic::firstOrCreate`,
   and on new → `CombatLogNpcEvent` (`CharacteristicAdded`).
4. `SpellCounterDataExtractor` (#3826) — correlates a player counter cast (Vanish, Shadowmeld) with an
   NPC ability fizzling; sets `spells`.`counters_mask`. Three signatures, ε = 100 ms.
5. `ImmunityBypassDataExtractor` (#3835) — the inverse signal: NPC abilities that **land** on a player
   during an active immunity window (Divine Shield, Ice Block, ...); sets
   `spells`.`bypasses_immunities_mask`. Each immunity declares its own coverage (protected schools,
   damage vs harmful auras) in `DataExtractors/ImmunityBypasses/ImmunityDefinitions.php` — without that
   coverage model most in-window hits are expected behaviour, not bypasses.

(There is no `FloorDataExtractor` or `NpcUpdateDataExtractor` any more — older notes that list them are
stale; the base-health work the latter was a placeholder for is the on-demand `NpcHealthDataExtractor`
below, #4094.)

### NPC health from logs — `combatlog:extractnpchealth` (#4094, on demand, not in the pipeline)

`npc_healths` is seeded mapping data, so it is never written by ingestion. `NpcHealthDataExtractor`
only *observes*: for every advanced line whose info GUID is a real creature (unit type `Creature`, no
owner GUID — pets/guardians out) it records max HP per NPC per key level, accumulating across files
(a Raider.IO run is 6–10 segments and each NPC shows up in a few). `combatlog:extractnpchealth <file|dir>`
installs it alone via `FixedDataExtractorFactory`, runs `extractData(..., force: true, runContext:)` —
**force** so already-ingested logs are re-read and no `ParsedCombatLog` row is written, **runContext**
because only the first segment has `CHALLENGE_MODE_START`; the rest open with a `RIO_LOG_VERSION`
header that names the dungeon but not the key level (the command pre-scans the files for the
`CHALLENGE_MODE_START`, or takes `--key-level`/`--affix-ids`). `NpcHealthExtractionService` then
reverses `Npc::getScalingFactor()` (honouring the row's `percentage`, which both abandoned earlier
attempts forgot) and writes missing/placeholder rows
(`--overwrite` for real values, `--dry-run` to only print the comparison table). Finish with
`mapping:save`.

**Pick the log's key level with care** — modifiers differ per NPC and per week and the factor can't know
which: +2..+5 carry Lindormi's Guidance (−5% on *most* hostile trash, not all), +7..+9 carry *one* of
Fortified/Tyrannical (they swap every other week, and Raider.IO's `CHALLENGE_MODE_START` lists only the
seasonal affix, so the log does not say which; summoned units are exempt from Fortified anyway), +10 both.
**A +6 log has none of that**, so derive from +6 and use another level only as a cross-check. The factor itself is fitted to measurements at every level (`NpcScalingFactorTest`);
the `percentage`-aware reversal, MDT's 4.17% boss-base offset and the per-level rounding are all
explained in `config/keystoneguru.php` `keystone`.

Both counter and bypass extractors write the same observation/`CombatLogSpellEvent` plumbing as the
spell collectors, and assign the spell to its casting NPC themselves (cast-only and nil-destination
spells never pass `SpellDataExtractor`'s assignment gate).

### SpellDataExtractor sub-collectors

`app/Service/CombatLog/DataExtractors/SpellDataCollectors/` — order is load-bearing so `SpellCreated`
precedes `PropertyChanged` in the feed:

1. `SummonedNpcCollector` — tracks summoned NPC IDs so downstream collectors ignore them (no writes).
2. `SpellCreationCollector` — creates missing `Spell` rows; emits `CombatLogSpellEvent` (`SpellCreated`).
3. `SpellPropertyObservationCollector` — detects Aura/Debuff/miss/interrupt; upserts a spell property
   **observation**, applies the property to the `Spell` if new, emits `CombatLogSpellEvent`
   (`PropertyChanged`).
4. `SpellDungeonAssignmentCollector` — creates `SpellDungeon` links for unknown-category spells.
5. `NpcSpellAssignmentCollector` — creates `NpcSpell` (+ `SpellDungeon` if missing); emits
   `CombatLogNpcEvent` (`SpellAssigned`).

## Staleness sweep — `combatlog:detectstaledata`

`DetectStaleCombatLogDataCommand`, scheduled **hourly** in `routes/console.php`. Cutoff =
`now()->subDays(observation_window_days)` (config
`keystoneguru.combat_log_staleness.observation_window_days`, default **3**, env
`COMBAT_LOG_STALENESS_OBSERVATION_WINDOW_DAYS`). Scoped to the **current season's dungeons** (skips with
a warning if there is no current season). Three phases:

1. `removeStaleNpcCharacteristics()` — deletes `NpcCharacteristic` rows with no fresh observation
   (`->toBase()->delete()`, a bulk delete that skips Eloquent's caching builder layer) + emits
   `CharacteristicRemoved`.
2. `removeStaleSpellProperties()` — clears stale `aura`/`debuff`/miss-type bits on `Spell` + emits
   `PropertyRemoved`.
3. `pruneOldObservations()` — hard-deletes observation rows older than `window + 1` days.

Removal events carry `combat_log_path = null` — the marker for system-generated (vs ingest) events.

## NPC Compendium reads

Routes in `routes/web.php` behind `feature_active:App\Features\NpcCompendium`; controller
`NpcCompendiumController`; service `NpcCompendiumService`:

- `show()` → `buildEventFeed($npc)`: newest 50 `combat_log_npc_events` for the NPC (manually eager-loads
  the polymorphic `model` per `model_class` — `Characteristic` or `Spell` — rejecting `hidden_on_map`
  spells) merged with newest 50 `combat_log_spell_events` for the NPC's `npcSpells.spell_id`s, sorted by
  `created_at` desc, top 50.
- `activity()` / `activityDay()` → `getActivityDates()` / `getEventsForDate()`: distinct
  `DATE(created_at)` across both event tables, dungeon-scoped via `npc_dungeons` (NPC events) and
  `NpcSpell` spell IDs (spell events).

Observation tables are **never read directly** by the Compendium — they only feed event creation/expiry.

## Event type enums

- `CombatLogNpcEventType`: `CharacteristicAdded`, `CharacteristicRemoved`, `SpellAssigned`.
- `CombatLogSpellEventType`: `SpellCreated`, `PropertyChanged`, `PropertyRemoved`.
- `SpellProperty`: `aura`, `debuff`, `miss_*` (cast on the `property` column of both the spell
  observation and spell event models).

## Key files

| Area | Path |
|---|---|
| Orchestrator | `app/Service/CombatLog/CombatLogDataExtractionService.php` (+ `...Interface.php`) |
| Streaming reader | `app/Service/CombatLog/CombatLogService.php`; `app/Logic/CombatLog/CombatLogEntry.php` |
| Extractors | `app/Service/CombatLog/DataExtractors/*.php` (+ `DataExtractorInterface.php`) |
| Spell collectors | `app/Service/CombatLog/DataExtractors/SpellDataCollectors/*.php` |
| Result DTO | `app/Service/CombatLog/Dtos/DataExtraction/{ExtractedDataResult,DataExtractionCurrentDungeon}.php` |
| Observation/event models | `app/Models/CombatLog/CombatLog{NpcCharacteristicObservation,SpellPropertyObservation,NpcEvent,SpellEvent}.php` + `CombatLog{Npc,Spell}EventType.php`, `SpellProperty.php` |
| Migrations | `database/migrations_combatlog/2026_05_22_00000{1,2,3,4}_*.php`, `2026_05_23_000001_update_combat_log_spell_events_table.php`, `..._000006_seed_combat_log_observations.php`, `..._000007_add_indices_to_combat_log_npc_events_table.php` |
| Staleness | `app/Console/Commands/CombatLog/DetectStaleCombatLogDataCommand.php`; schedule in `routes/console.php` |
| Ingestion jobs | `app/Jobs/CombatLog/{ProcessCombatLogFanout,ProcessCombatLogFromS3,ProcessCombatLogSegments}.php` |
| Polling / manual | `app/Console/Commands/CombatLog/PollCombatLogRunsCommand.php`, `.../ExtractData.php` |
| Front-end | `app/Http/Controllers/Compendium/NpcCompendiumController.php`; `app/Service/Compendium/NpcCompendiumService.php`; routes in `routes/web.php` |
| Repositories | `app/Repositories/{Database,Interfaces}/CombatLog/`; bound in `app/Providers/RepositoryServiceProvider.php` |
| Bindings | `app/Providers/KeystoneGuruServiceProvider.php` (service binds); `app/Providers/LoggingServiceProvider.php` (extractor loggers) |
| Config | `config/keystoneguru.php` (`combat_log_staleness`, `raider_io.combat_log_polling`) |

## Caveats

- Extractors and spell collectors are **hard-coded and order-sensitive**; the set comes from
  `DataExtractorFactoryInterface` (swap by binding another instance), everything else is not
  container-bound.
- Base health is written on demand by `combatlog:extractnpchealth` (see the "NPC health from logs"
  section), never by ingestion — the old `NpcUpdateDataExtractor` placeholder was deleted in #4094.
- `ProcessCombatLogFanout` has **no in-repo dispatcher** — the S3 ingestion trigger lives outside this
  codebase (infra pushing to the `*-combat-log-fanout` queue). The sibling
  `keystone.guru.combatlogstreamer` repo is a local log *replayer*, not the dispatcher.
- `combatlog:pollruns` exists but its scheduler entry is currently commented out; only
  `combatlog:detectstaledata` is actively scheduled.
- `CombatLogEvent` (OpenSearch) is a separate positional-analytics concept — not part of this pipeline.
