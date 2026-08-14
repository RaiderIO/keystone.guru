---
name: spell-descriptions
description: How spell descriptions are imported from the game client's DB2 tables on wago.tools and rendered into the tooltips on spell links. Use when re-running the import for a new game patch, when a description renders wrong or empty, or when extending the description template parser. Not for spell names/icons (those come from Wowhead via wowhead:fetchspelldata).
---

# Spell descriptions

`spells.description` holds a readable description of every spell we know, rendered up front from
the game client's own data. `spells.description_template` holds the raw template it came from: it is
what makes a changed description reviewable in a seeder diff, and what tells you whether a
description reads oddly because of the parser or because of the game. Re-rendering it still needs
the DB2 tables, which is what the download cache below is for.

## Re-running it for a new game patch

```sh
docker compose exec -T app php artisan wagotools:importspelldescriptions   # DB2 -> templates + coefficients
docker compose exec -T app php artisan wowhead:calibratespelldamage        # -> spells.damage_multiplier
docker compose exec -T app php artisan wagotools:importspelldescriptions   # re-render with the multipliers
docker compose exec -T app php artisan mapping:save                        # -> database/seeders/dungeondata/spells.json
git add database/seeders/dungeondata/spells.json
```

Everything inferred from the game data ships in the seeder, so staging and production know it without
running any of this: the templates, the per-effect coefficients (nested under each spell) and the
damage multipliers.

- `--product=wow` picks the CDN product (`wowt` is the PTR, `wow_classic` a classic client) and
  `--gameVersion=retail` says which of our game versions that client is. **They must match** - only
  the spells of that game version are touched, which is what stops a classic build from rewriting
  every retail description. `--build=12.1.0.69214` pins a build instead of the most recent one.
- The first run for a build downloads ~140MB of CSV into `storage/app/db2/<build>/` and re-uses it
  afterwards, so a re-run after a parser change costs nothing. Delete that folder to force a
  re-download.
- `mapping:save` rewrites the whole seeder set — keep only `spells.json` unless the rest is yours.

## How it fits together

| Piece | Where |
|---|---|
| Downloads + streams a DB2 table's CSV | `app/Service/WagoTools/WagoToolsService.php` |
| Streams the tables, renders, writes the two columns | `app/Service/Spell/Description/SpellDescriptionImportService.php` |
| The template language | `app/Service/Spell/Description/SpellDescriptionParser.php` |
| The arithmetic inside `${...}` | `app/Service/Spell/Description/MathExpressionEvaluator.php` |
| Measuring the damage multipliers | `app/Service/Spell/Description/SpellDamageCalibrationService.php` |
| The tooltip | `resources/assets/js/custom/spelltooltip.js` + `resources/assets/css/sections/spell-tooltip.css` |
| The links that get one | `resources/views/common/spell/link.blade.php`, `resources/assets/js/handlebars/spell_template.handlebars` |

**The DB2 tables are never imported.** `SpellEffect` alone is ~1M rows, and nothing about a rendered
description varies per request, so the tables are streamed once and thrown away. Only spells already
in `spells` get a description; the spells those descriptions *reference* are read from the same
stream, so a cross-spell value resolves even for a spell we do not track.

## Things that will trip you up

- **Creature damage in DB2 is a coefficient, not an amount.** "10" in the data is 50,845 in the
  game: `amount = coefficient / 10 x the spell's damage multiplier`. Nothing in the client data
  derives that multiplier - the spell carries no content tuning, `SpellLevels` is empty, and only 65
  of our 6,149 NPCs carry a tuning of their own - so it is measured against the game's own rendered
  numbers by `wowhead:calibratespelldamage` and shipped. It is **per spell**: one dungeon's spells
  can carry three different multipliers, so per-dungeon does not work (measured, not assumed).
- **A spell with no multiplier renders no damage number at all**, rather than its raw coefficient.
  About a third of described spells are in that state - their numbers could not be paired against
  the game's, or disagreed with each other.
- **Calibration measures against the coefficient, never against what we display**, since what we
  display already has a multiplier in it. Measuring the displayed number would report a multiplier
  of one and quietly undo the previous run.
- **A description without a number is usually correct.** Player abilities carry no base points in
  DB2 and scale off the casting character's stats, so "causing Frost damage" is the honest render -
  there is no character to scale against.
- **Conditionals render their false branch.** `$?s12345[a][b]` asks whether the viewer knows a
  talent, has an aura, plays a class or is in a difficulty - unknowable up front, so every such
  check is false and its negation true. That matches what Wowhead shows a logged out visitor.
  Conditionals chain (`$?a[x]?b[y][z]` is if / else if / else), and `$?$s4>0[x][y]` compares spell
  values and *is* evaluated for real.
- **An unresolvable token is omitted, never left raw** - a reader has no use for `$s1`. Arithmetic
  containing one is dropped whole rather than half-evaluated.
- **`${...}` never goes near `eval()`.** It is externally sourced text; the evaluator only accepts
  the grammar in its docblock and fails everything else.
- **A description is only cleared when the build knows the spell and dropped its text.** A spell the
  build has no row for at all is left as it was, and a build that describes nothing we know refuses
  to run - a wrong `--build` or a bad download cannot wipe the lot. If you see "Nothing was
  imported", check the build exists on https://wago.tools/db2/Spell.
- **Descriptions are English only.** DB2 serves `_lang` columns per locale and we ask for `enUS`;
  the durations the parser writes ("8 sec") are hardcoded English to match the surrounding text.

## Spell links and Wowhead

A link renders our tooltip when the spell has a description, and keeps its `data-wowhead` attribute
when it does not, so Wowhead still covers the gaps. Wowhead's `power.js` stays loaded site-wide -
its `iconizeLinks` also decorates bare `wowhead.com` hrefs in the admin tools and the compendium's
"View on Wowhead" button, which removing the script would silently change.

## Related skills

- **combatlog-data-pipeline** - where `npc_spells` and the compendium's event feed come from
- **seeder-save** / **seeder-load** - the `mapping:save` export the descriptions ride along in
