---
name: live-session-debug
description: End-to-end loop for debugging the live-session combat-log feature (#3275 epic) by replaying a real WoW combat log into a live session and inspecting the resulting enemy state in the database. Use when you need to reproduce/verify killed / overpulled / obsolete enemy behaviour or player positions against a real route (default debug route - dungeonroute nFcTwnK).
---

# Debugging live sessions with the combat-log streamer

The fastest way to debug the live-session pipeline is to replay a recorded combat log
into a fresh live session and inspect the persisted state in the database. The DB is the
source of truth for enemy state, so you can verify behaviour without watching the map.

See the **`live-session-enemy-state`** skill for the subsystem internals (services,
tables, broadcast events). This skill is just the operational debug loop.

## 1. Create a fresh live session

Default debug route: **dungeonroute `nFcTwnK`**. Always make a *new* session per debug
run so state starts clean.

- **Via browser (preferred):** log in, open the route's live URL — the
  `dungeonroute.livesession.create` route (`GET /dungeon/{dungeon}/route/nFcTwnK/live`)
  creates a `LiveSession` and redirects to `.../live/{public_key}`. The `{public_key}`
  in the final URL is your session key, and you get the live map to watch.
- **Via tinker** (no browser, e.g. headless debugging):
  ```php
  docker compose exec -T app php artisan tinker --execute="
    \$dr = App\Models\DungeonRoute\DungeonRoute::where('public_key','nFcTwnK')->firstOrFail();
    \$ls = App\Models\LiveSession\LiveSession::create([
      'dungeon_route_id' => \$dr->id,
      'user_id'          => \$dr->author_id,
      'public_key'       => App\Models\LiveSession\LiveSession::generateRandomPublicKey(),
    ]);
    echo \$ls->public_key.PHP_EOL;
  "
  ```
  Note the printed `public_key`.

## 2. Point the streamer at the session

The streamer lives at `/home/wouterkoppenol/Git/private/keystone.guru.combatlogstreamer`.
Edit its `.env`:

- `LIVE_SESSION_KEY=<public_key from step 1>`
- `TARGET_URL=http://localhost:8008` (local app)
- `COMBATLOG_FILE=<absolute path to a recorded WoWCombatLog .txt>` (a Pit of Saron and
  other sample logs already sit in the streamer directory)
- `API_USERNAME` / `API_PASSWORD` — credentials of the session's user

## 3. Replay the log

Run from the streamer directory (Node 20.6+ for `--env-file`):

```sh
node --env-file=.env index.js --no-start-from-eof          # replay from start, real-time pacing
node --env-file=.env index.js --no-start-from-eof --speed 10   # 10x faster (recommended for debugging)
node --env-file=.env index.js --speed 0                    # instant, no pacing (fastest smoke test)
node --env-file=.env index.js --dry-run                    # print batches, do not POST
```

Key flags: `--no-start-from-eof` reads the file from the beginning (without it the
streamer tails only new lines); `--speed N` replays at N× using per-line timestamps
(`0` = instant); `--dry-run` inspects batches without sending.

**Queue worker must be running.** Ingested batches dispatch the
`ProcessLiveSessionCombatLogBuffer` job onto the `*-*-live-session-process` queue;
enemy state only updates once that job runs. Make sure Horizon / a queue worker is up in
Docker (e.g. `docker compose exec -T app php artisan horizon` or the worker container),
otherwise the buffer fills but no state is computed.

## 4. Inspect the persisted state

Use the `database-query` MCP tool. Resolve the session id first, then read the three
state tables. Readable join (names + forces):

```sql
SELECT 'killed' AS state, n.name, k.npc_id, k.mdt_id, NULL AS kill_zone_id
FROM live_session_killed_enemies k JOIN npcs n ON n.id = k.npc_id
WHERE k.live_session_id = :id
UNION ALL
SELECT 'overpulled', n.name, o.npc_id, o.mdt_id, o.kill_zone_id
FROM live_session_overpulled_enemies o JOIN npcs n ON n.id = o.npc_id
WHERE o.live_session_id = :id
UNION ALL
SELECT 'obsolete', n.name, ob.npc_id, ob.mdt_id, NULL
FROM live_session_obsolete_enemies ob JOIN npcs n ON n.id = ob.npc_id
WHERE ob.live_session_id = :id;
```

**Key sanity check — an enemy must never be both killed and obsolete** (you cannot skip
an enemy you actually killed). This should return **0 rows**:

```sql
SELECT k.npc_id, k.mdt_id
FROM live_session_killed_enemies k
JOIN live_session_obsolete_enemies o
  ON o.live_session_id = k.live_session_id AND o.npc_id = k.npc_id AND o.mdt_id = k.mdt_id
WHERE k.live_session_id = (SELECT id FROM live_sessions WHERE public_key = '<key>');
```

Player positions live in `live_session_player_positions` (one row per player GUID, only
the latest position is kept).

## 5. Re-run / reset

For a clean re-run, create a new session (step 1) rather than reusing one — killed and
overpulled rows accumulate across the session lifetime by design. Expired sessions and
all their state are pruned hourly by `livesession:cleanup-expired`.
