---
name: combatlog-parsing-internals
description: How a single raw WoW combat log line becomes a parsed event object — CombatLogEntry's dispatch logic, the BaseEvent/SpecialEvent/CombatEvent class hierarchy, the CombatLogVersion registry and its getVersionLong() formula, the builder-with-default-fallback pattern every version-gated class uses, and the safe procedure for registering a new WoW client build. Use when a combat log line fails to parse, when a new WoW patch needs its version registered, or when touching CombatLogEntry/CombatLogVersion/SpecialEvent/Prefix/Suffix classes. For what happens AFTER a line becomes an event (NPC/spell mapping extraction, NPC Compendium) use combatlog-data-pipeline instead. For the operational runbook to find/download/reproduce real failing logs from Staging, use combatlog-parse-failure-triage.
---

# Combat Log Parsing Internals

How `app/Logic/CombatLog/*` turns one raw text line into a typed event object. This is the layer
*upstream* of the data-extraction pipeline described in `combatlog-data-pipeline` — everything here
happens before any NPC/spell mapping data is touched.

## Entry point: `CombatLogEntry::parseEvent()`

`app/Logic/CombatLog/CombatLogEntry.php`. Given one raw line plus the *currently active*
`$combatLogVersion` (an `int`, threaded through by the caller — see below):

1. Regex-splits the line into a timestamp and the rest (`m/d[/Y] H:i:s.v[-tz]  EVENT_NAME,params...`).
   Lines that don't match and aren't in the hardcoded `RAW_EVENT_IGNORE` allowlist throw
   `InvalidArgumentException`.
2. Parses the timestamp against `DATE_FORMATS` (13 timezone offset variants); remembers which format
   matched last time (`self::$previousDateFormat`, a **static**, process-lifetime cache) and tries
   that one first.
3. Splits the rest into `$eventName` + `$parameters` via `CombatLogStringParser::parseCombatLogLine`
   (handles quoted/bracketed/nested WoW log syntax).
4. Dispatches on `$eventName`, in this order:
   - In `SpecialEvent::SPECIAL_EVENT_ALL` → `SpecialEvent::createFromEventName(...)`.
   - Else `count($parameters) > 23` → `new AdvancedCombatLogEvent(...)` (advanced combat logging
     adds a fixed block of extra fields; 23 is the max param count for a *non*-advanced line per
     [wowpedia](https://wowpedia.fandom.com/wiki/COMBAT_LOG_EVENT)).
   - Else → `new CombatLogEvent(...)` (basic/non-advanced).

## Class hierarchy

```
BaseEvent (combatLogVersion, timestamp, eventName, rawEvent)
├── SpecialEvent (abstract)                 — one-off events: COMBAT_LOG_VERSION, RIO_LOG_VERSION,
│     │                                        ZONE_CHANGE, ENCOUNTER_START/END, COMBATANT_INFO, ...
│     └── (one concrete class per SPECIAL_EVENT_* constant, or built via a *Builder for
│          version-varying ones — see below)
└── CombatEvents\CombatLogEvent              — the generic SWING_/SPELL_/RANGE_* combat line,
      └── CombatEvents\AdvancedCombatLogEvent   made of a Prefix + a Suffix + GenericData(+AdvancedData)
```

`CombatLogEvent`/`AdvancedCombatLogEvent` build themselves from three independently-versioned pieces:
`Prefix::createFromEventName()` (dispatches on event-name *prefix*, e.g. `SWING_`, `SPELL_`) + a
matching `Suffix::createFromEventName()` (dispatches on event-name *suffix*, e.g. `_DAMAGE`,
`_AURA_APPLIED`) + `GenericDataBuilder::create()` (ignores version entirely — one shape for all
versions) +, for advanced events, `AdvancedDataBuilder::create()`.

## `CombatLogVersion` — the version registry

`app/Logic/CombatLog/CombatLogVersion.php`. A flat list of known client builds as `int` constants
(`RETAIL_12_0_5 = 22_012_000_005`) plus two lookup tables keyed by those constants:

- `RETAIL_ALL` — retail only, value = a small sequential "revision number".
- `ALL` — retail + classic + SoD + TBC, value = a small sequential "revision number" (separate
  numbering from `RETAIL_ALL`).

**The revision numbers in these tables are not used as a schema-revision switch anywhere in the
codebase.** They only exist so `PollCombatLogRunsCommand` can find "the newest known version" via
`array_key_last(RETAIL_ALL)` for Raider.IO polling eligibility. Don't confuse them with
`getVersionLong()` below, which is what everything else actually keys on.

### The `getVersionLong()` formula

`SpecialEvents\Traits\ComputesVersionLong` (mixed into `CombatLogVersion` and `RioLogVersion`, the
two special events that carry version info):

```
getVersionLong() = getVersionNumber() * 1_000_000_000 + major * 1_000_000 + minor * 1_000 + patch
```

where `getVersionNumber()` is the small int at the start of the `COMBAT_LOG_VERSION`/`RIO_LOG_VERSION`
line (e.g. `22`) and `major.minor.patch` comes from the line's `BUILD_VERSION` field (e.g. `12.0.7`).
So `COMBAT_LOG_VERSION,22,...,BUILD_VERSION,12.0.7,...` → `22_012_000_007`. **This** is the value that
gets registered as a `CombatLogVersion::RETAIL_*` constant and looked up in `ALL`/`RETAIL_ALL`.

### What `ALL`/`RETAIL_ALL` actually gates

Exactly two places, both an allowlist check that throws if the version is unregistered:

- `SpecialEvents\CombatLogVersion::setParameters()` (the event that starts a line-stream's active
  version) — line ~70: `if (!isset(CombatLogVersionConstant::ALL[$this->getVersionLong()])) { throw }`.
- `Service\CombatLog\ResultEvents\CombatLogVersion` — a duplicate of the same check, further downstream.

**That's it.** No builder anywhere switches behaviour based on whether a version is "known" — see next
section.

## The pattern every version-gated builder follows: `match` with `default`

Every class whose behaviour genuinely differs per client build (`DamageBuilder`, `AuraAppliedBuilder`,
`AuraRemovedBuilder`, `CombatantInfoBuilder`, `AdvancedDataBuilder`, `EncounterStartBuilder`,
`EncounterEndBuilder`, `DamageShieldBuilder`, `DamageShieldMissedBuilder`, `DamageSplitBuilder`,
`SpellAbsorbedBuilder`, `SpellAbsorbedSupportBuilder`, `EnvironmentalDamageBuilder`,
`EnergizeBuilder`, `MissedBuilder`, ...) implements `create(int $combatLogVersion): X` as:

```php
return match ($combatLogVersion) {
    CombatLogVersion::CLASSIC, CombatLogVersion::RETAIL_10_1_0 => new SomeOldVersion(...),
    default                                                    => new SomeNewestVersion(...),
};
```

**Any unlisted `combatLogVersion` int — registered or not — already falls into `default`, which is
always the newest handler.** `Prefix`/`Suffix::createFromEventName()` dispatch on event *name*, not
version, so they're unaffected entirely. `GenericDataBuilder::create()` ignores the version argument.

This is the fact that makes registering a new version low-risk *for parsing dispatch*: a brand-new
build was already being routed through the newest handler before you registered it (it just never got
past the `ALL`/`RETAIL_ALL` allowlist throw to reach that point). Registering the version doesn't
change *where* it's routed — it only lets it through the gate.

**What registering a version can't tell you**: whether that newest handler's *field layout*
actually matches the new build's real output. A new WoW patch occasionally does change a field
count/shape even without bumping past what the newest handler expects. Code-reading alone can't rule
that out — you have to re-parse real logs from that build. See `combatlog-parse-failure-triage` for
how to pull real failing logs from Staging and replay them locally before shipping a version fix.

## Parameter count validation

`CombatEvents\Traits\ValidatesParameterCount` (mixed into `Prefix`, `Suffix`, and every `SpecialEvent`):
each class declares `getParameterCount()` (max) and optionally `getOptionalParameterCount()` (default
`0`); `validateParameters()` throws `InvalidArgumentException` if the actual count falls outside
`[max - optional, max]`. The exception message format is `"Invalid parameter count for %s - wanted
%d-%d, got %d"` — the "got" number tells you exactly how many fields were present, useful for
diagnosing truncated/malformed lines (see `combatlog-parse-failure-triage`'s RioLogVersion example).

## Registering a new WoW combat log version — the procedure

1. **Get the real version-long value.** Pull a real `COMBAT_LOG_VERSION,...,BUILD_VERSION,X.Y.Z,...`
   line from an actual failing log (see `combatlog-parse-failure-triage`) — don't guess the format.
2. **Confirm the safety property above still holds** — `grep -rn "RETAIL_<latest>\b" app/` and check
   every match's builder has a `default =>` arm (it should; this has held for every version to date).
   If you find one *without* a default (an exhaustive `match` with no fallback), that class needs an
   explicit new case, not just a registry entry — treat it as a bigger change and investigate that
   class's newest handler's real field layout first.
3. Add `public const int RETAIL_X_Y_Z = <versionLong>;` to `CombatLogVersion.php`, plus an entry in
   both `RETAIL_ALL` and `ALL` with the next sequential revision number (`array_key_last` semantics —
   just increment from the current max value in each table; the two tables number independently).
4. Add a data-provider case to
   `tests/Unit/App/Logic/CombatLog/SpecialEvents/CombatLogVersion/CombatLogVersionTest.php`.
5. **Before considering it done**, re-parse real failing logs from that build end-to-end (not just the
   `COMBAT_LOG_VERSION` line in isolation) — see `combatlog-parse-failure-triage` for the exact
   reproduction recipe. Zero new exceptions across a couple of real runs is the bar; if anything new
   surfaces, that's a genuine new-build field-shape difference and needs its own fix in the relevant
   builder/handler, not a version-registry tweak.

## `CombatLogService` parsing entry points

`app/Service/CombatLog/CombatLogService.php` — the streaming reader that drives `CombatLogEntry` over
a whole file:

| Method | Use for |
|---|---|
| `parseCombatLog(string $filePath, callable $callback)` | Low-level: streams `fgets()` line by line, tracks the active `combatLogVersion`/advanced-logging flag across `COMBAT_LOG_VERSION` lines, transparently unzips `.zip` input first. Wraps any exception from `$callback` in `CombatLogParseException` (captures line number + raw line). |
| `parseCombatLogToEvents(string $filePath): Collection<BaseEvent>` | Convenience wrapper: parses the whole file into an in-memory `Collection` of events. **Read-only, no DB writes** — this is the right tool to reproduce/verify a parse failure locally. |
| `parseCombatLogStreaming(string $filePath, callable $callable)` | Like `parseCombatLogToEvents` but calls `$callable($event, $lineNr)` per line instead of collecting — used when the caller needs to react per-event without holding the whole file in memory. |
| `extractCombatLog(string $filePath): ?string` | Unzips a `.zip` to `/tmp` if the path ends in `.zip`; returns `null` (no-op) for anything else, including plain `.txt`. |

For the full production ingestion path (extractors, NPC/spell mapping writes, `ParsedCombatLog`
idempotency, staleness sweep) see the `combatlog-data-pipeline` skill instead —
`CombatLogDataExtractionService::extractData()` is built on top of `parseCombatLog()` but has real DB
side effects, so don't reach for it just to reproduce a parse failure.

## How parse failures get recorded

`App\Jobs\CombatLog\ProcessCombatLogSegments::handle()` (the Raider.IO M+ run ingestion path) catches
any `Throwable` from `extractData()` and calls
`CombatLogParseFailureRepositoryInterface::recordFailure()`, which `updateOrCreate`s a
`CombatLogParseFailure` row (connection `combatlog`) deduplicated on `(run_id, line_number)`. Key
fields: `raw_line`, `message`, `exception_class`, `combat_log_version`, `resolved_at` (null = still
unresolved). `CombatLogParseException::getOriginalExceptionClass()` unwraps to the *real* underlying
exception class (`InvalidArgumentException`, etc.) rather than always recording the wrapper.

## Key files

| Area | Path |
|---|---|
| Line entry point | `app/Logic/CombatLog/CombatLogEntry.php` |
| Base class | `app/Logic/CombatLog/BaseEvent.php` |
| Version registry | `app/Logic/CombatLog/CombatLogVersion.php` |
| Version-carrying special events | `app/Logic/CombatLog/SpecialEvents/CombatLogVersion.php`, `.../RioLogVersion.php`, trait `.../Traits/ComputesVersionLong.php` |
| Special event dispatch | `app/Logic/CombatLog/SpecialEvents/SpecialEvent.php` (`SPECIAL_EVENT_ALL`, `*_CLASS_MAPPING`, `*_BUILDER_CLASS_MAPPING`) |
| Generic combat event | `app/Logic/CombatLog/CombatEvents/CombatLogEvent.php`, `.../AdvancedCombatLogEvent.php` |
| Prefix/Suffix dispatch | `app/Logic/CombatLog/CombatEvents/Prefixes/Prefix.php`, `.../Suffixes/Suffix.php` |
| Param count validation | `app/Logic/CombatLog/CombatEvents/Traits/ValidatesParameterCount.php` |
| Streaming reader | `app/Service/CombatLog/CombatLogService.php` (+ `CombatLogServiceInterface`) |
| Parse exception | `app/Service/CombatLog/Exceptions/CombatLogParseException.php` |
| Failure record model/repo | `app/Models/CombatLog/CombatLogParseFailure.php`, `app/Repositories/{Interfaces,Database}/CombatLog/CombatLogParseFailureRepository*.php` |
| Failure-recording call site | `app/Jobs/CombatLog/ProcessCombatLogSegments.php` |
| Version unit test | `tests/Unit/App/Logic/CombatLog/SpecialEvents/CombatLogVersion/CombatLogVersionTest.php` |
| Other parser unit tests | `tests/Unit/App/Logic/CombatLog/**` |
| Legacy fixture logs (full route-creation tests, not M+ segments) | `tests/CombatLogs/*.zip` |

## Caveats

- `CombatLogEntry::$previousDateFormat` is a **static** — persists for the process lifetime, shared
  across every `CombatLogEntry` instance. Harmless for one-shot CLI/tinker reproduction but be aware
  of it if writing anything that parses multiple unrelated logs in the same PHP-FPM/Octane worker.
- `CombatLogEntry::parseEvent()` has a leftover `echo` debug statement in its catch block (prints the
  exception message + raw line to stdout) — expect that noise when reproducing failures via `artisan
  tinker`; it's not an error signal by itself, just log clutter.
- `RAW_EVENT_IGNORE` is a hardcoded allowlist of a few known-garbage lines (including one specific
  stray `COMBAT_LOG_VERSION,20,...,BUILD_VERSION,11.0.0,...` line) that are silently dropped instead
  of throwing — a precedent for "sometimes a single malformed line needs an explicit ignore-list entry
  rather than a structural fix", if a future failure turns out to be one truly one-off garbled line
  from the WoW client itself.
- The `combat_log_version` int threaded everywhere is **not** the same number as the small "revision"
  stored in `ALL`/`RETAIL_ALL` — don't mix them up when reading builder `match` arms.
