---
name: Add new dungeon
about: All tasks that need doing when adding a new dungeon to the site.
title: 'Add dungeon '
labels: mapping
assignees: ''

---

Artisan commands run as `docker compose exec -T app php artisan ...` from the issue's worktree. `<key>` is the `DungeonKey` value.

Code registration
- [ ] Add a `DungeonKey` case and put it in the right `expansionKey()` arm (`app/Models/DungeonKey.php`)
- [ ] Add the dungeon name, abbreviation and floor names to `lang/en_US/dungeons.php` (a floor named like the dungeon becomes the facade)
- [ ] If MDT has the dungeon: add it to `Conversion::DUNGEON_NAME_MAPPING` (and `MAINLINE_MDT_DUNGEONS` for mainline MDT) in `app/Logic/MDT/Conversion.php`
- [ ] Add the key to the skip list of `tests/Unit/MapTiles/MapTilesExistenceTest.php` until the tiles are uploaded

Dungeon
- [ ] Add dungeon: `dungeon:createmissing <expansion>` (or admin "New dungeon")
- [ ] Replace the placeholder ids in admin: `zone_id`, `map_id`, `instance_id`, `challenge_mode_id`; check `slug`; leave `active` off
- [ ] Add floors: `dungeon:createmissingfloors` (or admin "New floor")
- [ ] Set every floor's `ui_map_id` (`combatlog:extractuimapids <log>`), `default`, `facade`/`mdt_sub_level` and floor couplings
- [ ] Sync dungeon and floor names to all locales: `localization:synczonenames`
- [ ] Add dungeon graphics in the assets repo (`images/dungeons/<expansion>/<key>.webp`, `.jpg`, `_3-2.jpg`, `_transparent.png`, `_wallpaper.jpg`), then tick `has_wallpaper`

Map tiles
- [ ] Generate the map tiles from the game client: `/fetch-map-tiles <key>`
- [ ] Upload map tiles, then remove the key from the `MapTilesExistenceTest` skip list
- [ ] Set `zoom_max` on floors that need another zoom level
- [ ] Facade without a combined Blizzard map: `/generate-facade-map`

Baseline mapping
- [ ] Import min/max coordinates for each floor (Admin → Tools → wago.gg "Import ingame coordinates"); cross-check with `combatlog:determinebounds <log> <dungeon>`
- [ ] Import the combat log, see where the enemies end up: `combatlog:createmappingversion <log> <gameVersion>`
- [ ] Rotate the floor coordinates if necessary: `mapping:rotateingamecoords <dungeon>` (90° per run)
- [ ] MDT package lacks the dungeon? `/update-mdt-package` first
- [ ] Re-import mapping with all enemies being put on the correct floors: `mdt:importmapping <dungeon> <gameVersion> --force`
- [ ] Go through the import log: new templates/types, `MissingTranslation` (`lang/en_US/mapping.php`), `NpcSetReplaced`, POIs that were NOT imported
- [ ] Every enemy has a pack (`mapping:assignpackgroups --dungeon=<key>`) and an MDT id (`mapping:assignmdtids --dungeon=<key>`)
- [ ] Set `timer_max_seconds` (and `facade_enabled`) in the mapping editor's mapping version modal
- [ ] Import the enemy forces from the game client: `wagotools:importenemyforces --dungeon=<key> --product=wow` (compare against `--product=wowt` for hotfixes), then `--write` and `mapping:save` (repeat after every MDT re-import)
- [ ] POIs without a map icon type: `mapicon:downloadmdtitemicons <dungeon>`, add the type, `mapicon:generateitemicons`
- [ ] Facade: floor unions and union areas (`/generate-floor-unions` or `mapping:importfloorunionareas <mappingVersion> <file>`)
- [ ] Place map icons for relevant doodads
- [ ] Compare against MDT: `mdt:accuracy <mappingVersionId>`
- [ ] `mapping:save`

NPCs
- [ ] Import NPCs from Wowhead (Admin → Tools → NPC import)
- [ ] Re-sync NPC names: `localization:exportnpcnames`, `localization:importnpcnames`, `localization:syncnpcnames retail`
- [ ] Check classification, boss and dangerous flags per NPC
- [ ] Display ids: `wowhead:fetchdisplayids <dungeon>`
- [ ] Fetch the npc icons from the game: `/npc-portraits-headless`, or `/npc-portraits` in-game
- [ ] NPC health from a +6 run: `combatlog:downloadruns --dungeon=<key>`, then `combatlog:extractnpchealth <dir> --dry-run`, then without `--dry-run` (fallback `wowhead:fetchhealth <dungeon>` or `/npc-health-ocr`)
- [ ] `mapping:save`

Spells
- [ ] `combatlog:extractdata <log>` over the downloaded runs
- [ ] `wowhead:fetchmissingspells`, `wowhead:fetchspelldata --dungeon=<key>`, `wowhead:fetchmissingspellicons`, `spell:assignmissingdungeons`
- [ ] New game build? `/spell-descriptions`
- [ ] Spell visibility (Admin → Tools → Manage spell visibility)
- [ ] `mapping:save`

Mapping refinement
- [ ] Watch VODs/go into dungeon and tweak positions, grouping, patrols
- [ ] Add Skippable enemies (live sessions)
- [ ] Check the dungeon start and every floor switch marker against the real entrance/exits (MDT imports them once)
- [ ] Draw mountable areas wherever players can mount (MDT has none; the SimulationCraft travel delay walks everywhere else)
- [ ] `mapping:save` and commit the seeder diff

Season, Raider.io and go-live
- [ ] Add the dungeon to its season (admin), `mapping:save`
- [ ] Ensure the dungeon slugs match with Raider.io, and `challenge_mode_id`/`zone_id`/`map_id` are correct
- [ ] Turn on `active` and `heatmap_enabled` for the dungeon and `active` for its floors
- [ ] Smoke test: `dungeonroute:generatetest --dungeon=<key>`, render a thumbnail (`/generating-thumbnails`), then `dungeonroute:generatetest --delete`
- [ ] Release

Auto-route creator (once production has combat logs of the dungeon)
- [ ] ARC and event correction tests: `/combatlog-route-tests <season>`
- [ ] Ensure all bosses are killable; add a rule in `app/Service/CombatLog/Builders/Rules/` if needed (`/debug-combatlog-route-json`)
- [ ] Enemy failures/resolutions: `combatlog:importenemyfailures <dungeon>`, `combatlog:analyzeenemyfailures <dungeon>`; fix positions, `mapping:save`

Walk graph and SimulationCraft travel delay (after the ARC tests pass)
- [ ] Seed the walk graph: `/walk-graph-seed` (`walkgraph:import --dungeon=<key>`, `db:seedone --database=migrate WalkGraphSeeder`, `combatlog:buildwalkgraphs --distances-only`)
- [ ] Check the walk graph of every floor is one connected piece (no floor where the largest component is far below the node count)
- [ ] Review the delay baseline before calibrating: dungeon start, floor switch markers and mountable areas are where players really start, change floors and mount
- [ ] Calibrate: `combatlog:calibratetraveldelay <dir of combat log route .json>` over 20+ runs; investigate any dungeon whose median observed/predicted ratio is far from 1 before changing global speeds

After every later MDT re-import
- [ ] `mdt:importmapping --force`, re-check packs, `timer_max_seconds` and POIs, `wagotools:importenemyforces --write`, re-sync NPC names, `mdt:syncaddonversions --refresh`, `mapping:save`, re-pin `settings.mappingVersion` in the ARC fixtures
