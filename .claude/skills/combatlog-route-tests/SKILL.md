---
name: combatlog-route-tests
disable-model-invocation: true
argument-hint: "<season, e.g. midnight s2>"
description: Give every dungeon of a season an Auto Route Creator test and an event correction test, built from real combat logs pulled off Raider.IO. Invoke as `/combatlog-route-tests midnight s2` — only the season varies. Use at the start of a season, or when a dungeon is added to one. Not for debugging a single bad route (debug-combatlog-route-json) or for the download mechanics alone (combatlog-download-runs).
---

# /combatlog-route-tests `<season>` — ARC + event correction tests for a season

`$ARGUMENTS` is the season, as Wotuu would say it ("midnight s2", "tww season 3"). The job: for
**every dungeon in that season**, commit a real combat log request-body fixture and two tests
against it. Skip dungeons that already have both.

The download half is the `combatlog-download-runs` skill — read it for `searchruns`/`downloadruns`,
`scripts/reconstruct-run.sh`, and the traps in `combatlog:outputcombatlogroutejson`. This skill is
what to do with the body once you have it.

**Work in a worktree** (`sh/worktree.sh create <issue>-<slug>`) — this generates hundreds of MB of
logs and needs its own database.

## 1. Enumerate the season, then pre-flight the mapping

Get the dungeon list from `season_dungeons.json` (join `seasons.json` on `expansion_id` + `index`),
**not** from the expansion — a season routinely contains dungeons from older ones (Midnight S2 runs
King's Rest, a BfA dungeon). That distinction decides directory layout later, so get it right here.

For each dungeon confirm the mapping can actually produce a route:

```sh
docker compose exec -T app php artisan tinker --execute='
foreach ([<dungeon ids>] as $id) {
  $d = \App\Models\Dungeon::find($id);
  $mv = $d->mappingVersions()->orderByDesc("version")->first();
  printf("%-22s mv=%s efr=%s enemies=%d\n", $d->key, $mv?->version, $mv?->enemy_forces_required, $mv ? $mv->enemies()->count() : 0);
}'
```

A dungeon with no mapping version, `enemy_forces_required` of 0, or no enemies cannot yield a sane
route no matter how good the log is. Say so and skip it rather than committing a broken baseline.

## 2. Find one run per dungeon

```sh
docker compose exec -T app php artisan combatlog:searchruns --dungeon=<key> --min-level=2 --from-days=<days since season start> --limit=10
```

`--from-days` must not reach past the season start or you get last season's runs on a mapping that
has since changed. **Prefer a +10 or higher**: a completed high key is far more likely to have killed
every boss, which is what the gate in step 5 checks. Record the run ids — they are the reproducible
artifact, the filters are not.

Then, per the `combatlog-download-runs` skill: `downloadruns --run=<id>` → `reconstruct-run.sh` →
`combatlog:outputcombatlogroutejson`.

## 3. File the fixture

Directory is the **dungeon's own expansion**; filename is prefixed with the **season the run was
in**. They disagree often, and that is intentional — it follows the existing
`Fixtures/BFA/tww_s1_siege_of_boralus.json` precedent:

```
tests/Feature/Controller/Api/V1/APICombatLogController/Fixtures/BFA/midnight_s2_kings_rest.json
                                                                 ^ dungeon's expansion
                                                                     ^ season of the run
```

Name the file after the readable dungeon name (`kings_rest`), not its key (`kingsrest`).

## 4. Read the real numbers before writing assertions

The route test hardcodes a pull count and an enemy-forces total, so you need what the ARC actually
produces. Do **not** write a guess and iterate on failure messages — POST every fixture once from a
throwaway test that prints the numbers, then delete it:

```php
$response = $this->post(route('api.v1.combatlog.route.store'), $postBody);
$arr = json_decode($response->content(), true);
fwrite(STDERR, sprintf("%s pulls=%d ef=%d/%d\n", $fixture, count($arr['data']['pulls']),
    $arr['data']['enemyForces'], $arr['data']['enemyForcesRequired']));
\App\Models\DungeonRoute\DungeonRoute::where('public_key', $arr['data']['publicKey'])->first()?->delete();
```

Have it print the boss check from step 5 in the same pass. Extend `APIPublicTestCase` directly with
`LoadsJsonFiles` so it needs no dungeon binding, and give it a name that sorts last so it is obvious
it is scratch.

## 5. Gate the fixture before it becomes a baseline

**This is the step that matters.** A hardcoded count enshrines whatever the ARC produced, bug and
all, and for a whole season you cannot hand-verify every pull. Two checks stand in for that:

- **Enemy forces near the mapping version's requirement.** Over 100% is normal (overpull); Midnight
  S2 landed 96.5%–111.6%. Something at 40% is evidence of an ARC or mapping problem, not a baseline.
- **Every boss killed in the log resolves to a pull** — `validateBossesResolved()` on
  `APICombatLogControllerCombatLogRouteTestBase` does this, and it stays in the committed test so a
  future regression that drops a boss fails even when the counts still match.

If a fixture fails either, **say so and investigate — do not assert the broken number.** That is the
failure mode this task produces silently at scale.

**Do not assert spells.** The curated spell list yields 0–4 spells per route for current dungeons,
so `validateSpells()` would be pinning noise.

## 6. Write the two tests

Copy the shape from an existing pair — `Midnight/APICombatLogControllerCombatLogRouteMaisaraCavernsTest`
and its `CorrectEvents` sibling. Both live under the **dungeon's expansion** directory, matching the
fixture. Groups: `Controller`, `API`, `APICombatLog`, then `CombatLogRoute`/`CorrectEvents`, then the
dungeon. The route test wraps its assertions in `try`/`finally` with `deleteDungeonRoute()` — the
test DB is not rolled back between tests.

The `CorrectEvents` test is three lines; `executeTest()` **writes** the `_corrected` fixture when it
is missing and **asserts** against it when it exists. So:

```sh
docker compose exec -T app php artisan test --compact --group=CorrectEvents   # writes fixtures
git add tests/.../Fixtures
docker compose exec -T app php artisan test --compact --group=CorrectEvents   # must still pass
```

**The second run is the only one that proves anything** — assertion count goes up when the
comparison actually happens. If it fails, the corrected output is not deterministic and every one of
these tests is flaky by construction; stop and fix that first.

## 7. Finish

`composer run fix`, `composer run analyse`, then `--group=CombatLog` whole. Put the run id table in
the PR body. Delete `storage/app/combatlogs` or leave it for worktree teardown.
