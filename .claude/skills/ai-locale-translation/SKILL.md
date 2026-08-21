---
name: ai-locale-translation
disable-model-invocation: true
description: Bring the machine-translated `lang/*_ai` locales up to date with `lang/en_US` by translating the missing keys yourself (no OpenAI/DeepL key needed), one locale at a time, in each locale's established register and glossary. Use when asked to "bring all AI translations up-to-date", translate/fill a locale, or before a release to check translations are complete. Not for `lang/en_US` (source of truth) and not for the game-data files (spells/npcs/dungeons), which are never hand-translated.
---

# Keeping the `*_ai` locales up to date

`lang/<locale>_ai/` holds machine translations of `lang/en_US`. The built-in `translate:ksg` needs
a paid API key; this skill replaces it: **you are the translation engine**, the scripts here do the
mechanical parts (scoping, injection, a strict gate), and `locales/<locale>.md` holds each locale's
register and glossary so every pass reads the same as the last one.

`lang/en_US` is the source of truth and is never edited here. Existing translations are never
overwritten (the gate enforces it); only empty/missing keys are filled.

## "Bring all AI translations up-to-date" — the runbook

1. **Issue + worktree**, like any task (`create-github-issue`, `sh/worktree.sh create
   <issue>-update-ai-translations`). One issue, one branch, one PR for all locales.
2. **Status** — from the worktree root:
   ```bash
   sh .claude/skills/ai-locale-translation/scripts/status.sh --sync
   ```
   `--sync` runs `localization:sync en_US <locale>` per locale (adds empty stubs for new en_US
   keys — that is the backlog) and `translate:scan` once, then prints per-locale outstanding
   counts and writes `$OUT/<locale>.before.json` + `$OUT/<locale>.worklist.json`
   (`OUT=/tmp/translate` by default). Every locale usually shows the same count — the work lists
   are the same English strings. Each sync is wrapped in `check_sync.py` (a pre/post dump
   compare) which aborts the run if the sync changed any existing value. The sync also re-aligns
   `=>` columns; `composer run fix` puts them back, so run it before judging the diff. Commit the
   stubs (`#<issue> Sync *_ai stubs from en_US`).
3. **Per locale, in this order** (`de_DE_ai`, `es_ES_ai`, `es_MX_ai`, `fr_FR_ai`, `it_IT_ai`,
   `ko_KR_ai`, `pt_BR_ai`, `ru_RU_ai`, `zh_CN_ai`; `zh_TW_ai` is skipped — see its sheet):
   1. read `locales/<locale>.md` — register, glossary, conventions, known drift;
   2. translate the work list (section "Translating one locale" below), gate green;
   3. `composer run fix` (touches only `lang/<locale>/**` whitespace), gate green again;
   4. commit `#<issue> Translate <locale> (<n> keys)`;
   5. Codex review (section below), apply survivors with `rewrite.py`, gate green, commit
      `#<issue> Apply review fixes to <locale>`;
   6. update `locales/<locale>.md`: "Last full pass" row, new glossary rows, new conventions,
      one History line. Commit with the review fixes.
   One locale per session is the comfortable size (~770 keys ≈ one context); a fresh session
   picks up from `status.sh` and the sheets — nothing else carries between locales.
4. **Finish**: `composer run analyse`, `status.sh` shows `outstanding 0` for every non-skipped
   locale, PR as usual (cold review is the per-locale Codex reviews; say so in the PR body).
   `js.php` strings only reach the UI after an asset build, i.e. the next release.

This skill is user-invoke only (`/ai-locale-translation`); an agent cannot start it via the
`Skill` tool, but any session can read this file and run the scripts by path. That is how the
`create-release` skill uses it: its Step 0 runs `status.sh` (read-only) as a pre-flight and asks
Wotuu whether to run this runbook first when anything is outstanding.

## Translating one locale

```bash
LOCALE=de_DE_ai
SCRIPTS=.claude/skills/ai-locale-translation/scripts
OUT=/tmp/translate                                   # anywhere outside the repo

# 0. Stubs + scan + before-dump + work list (status.sh does all of this for every locale):
docker compose exec -T app php artisan localization:sync en_US $LOCALE     # ALWAYS pass the locale
docker compose exec -T app php artisan translate:scan --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation
docker compose exec -T app php $SCRIPTS/dump_locale.php $LOCALE > $OUT/$LOCALE.before.json
python3 $SCRIPTS/build_worklist.py $LOCALE $OUT/$LOCALE.before.json $OUT/$LOCALE.worklist.json

# 1. Translate: read the work list a file at a time, write {"dotted.key": "translation"} JSON
#    batches of 50-150 keys, inject each one.
python3 $SCRIPTS/inject.py $LOCALE $OUT/batch1.json

# 2. Gate - must print OK before anything is committed or handed off.
docker compose exec -T app php $SCRIPTS/dump_locale.php $LOCALE > $OUT/$LOCALE.after.json
python3 $SCRIPTS/verify.py $OUT/$LOCALE.before.json $OUT/$LOCALE.after.json $OUT/$LOCALE.worklist.json
```

PHP always runs in Docker; the Python scripts run on the host and only touch text files. The
bare `localization:sync en_US` (no target) rewrites every locale in `config('language.all')`.

Before writing a single string:

- `grep -n '<key>' lang/*_ai/<file>.php` for a few work-list keys — the finished locales are a
  worked example of the same English strings (read them for meaning, translate from the English).
- For every dungeon block in `mapping.php`: `grep -c "'<slug>' => \[" lang/en_US/mapping.php`.
  A count of 2 means the dungeon exists under two game-version keys (Algeth'ar Academy under
  `midnight` and `df`); the other block is already translated — match it exactly. Missed by three
  locales in a row.
- Render check once at the end: `php artisan tinker` on one plural key
  (`view_profile.view.route_count`) and one `%s` key (`view_common.dungeonroute.poster.views`).

## Scope: only empty keys, and only some files

Both filters are mechanical: `inject.py` only rewrites a line whose value is literally `''`/`""`,
and `build_worklist.py` only lists keys present in `lang/texts_to_translate.json`, which is the
`translate:scan` output with six files excluded. Never hand-translate them:

| File | Why not |
|---|---|
| `spells.php`, `npcs.php`, `dungeons.php` | Blizzard proper nouns with **official** localisations, filled by `localization:exportspellnames` / `localization:syncnpcnames` / wago.tools. |
| `view_admin.php` | Admin-only UI, English by convention. `localization:sync` adds stubs there too — expected, ignore them (a reviewer will flag them every time). |
| `validation.php` | Laravel framework strings; upstream ships real translations. |
| `datatables.php` | Populated by `localization:dt-download`. |

Those excluded files are also the **glossary you check before inventing a game term** — they sit
in the locale you are editing: `spells.php` (every spell/item-effect name), `affixes.php` (affix
names and descriptions), `enemies.php` (Prideful/Awakened enemies), `npcs.php`, `dungeons.php`.

```bash
grep -n "Pledge Pin" lang/en_US/spells.php          # -> line 4683-4687
sed -n '4683,4687p' lang/$LOCALE/spells.php         # -> the official localised name
```

## Translation rules

These are the rules the gate cannot check. Every one of them was learned from a Codex finding,
most of them more than once.

1. **Placeholders, plural segments and HTML are literal.** `:name`, `%s`, `{0}|{1}|[2,*]`, `<b>`,
   `<a href=":link">` survive unchanged (the gate checks this one).
2. **Register is per locale and fixed** — read it from `locales/<locale>.md` (formal: fr, ko, ru,
   zh; informal: de, es ×2, it, pt). Run the sheet's register check before handing off; a slip by
   habit from the previous locale is the most common mechanical error.
3. **Official game terms come from the locale, never from memory or a reviewer.** Spell, ability,
   immunity, affix, affix-enemy and dungeon/zone names: grep the English in `lang/en_US/<file>.php`,
   read the line in `lang/<locale>/<file>.php`. This applies to names that don't look like spells
   (`Blazing Aegis`, `Burning Chain`, `Enrage`, `Awakened`, `Teeming`) — the common ones are the
   ones that get invented. The locale also beats a confident reviewer (`affixes.php` said
   *Explosiv*, the reviewer said *Platzend*).
4. **NPC / boss / creature names stay in English inside `mapping.php` prose — absolute, even when
   `npcs.php` has an official localisation.** Translate the sentence, keep `Ramstein the Gorger`,
   `Vengeful Fleshreaper`, `Witherlings`, `Hackclaw's War-Band` as-is: the player reads the name
   off their own client. Broken by it_IT (75 keys), pt_BR (5), ru_RU (1) despite this rule; the
   fastest check is `grep -n '<key>' lang/{de_DE,fr_FR,es_MX}_ai/mapping.php`.
5. **Object / location labels in `mapping.php` are decided per item, by precedent.** Title-Case
   exact in-game tooltip names stay English (`Iron Gate`, `Activation Rune`, `The Black Anvil`,
   `Shadowforge Key`, `Large Solid Chest`, `Heavy Door`, `Stairwell Door`, `Supply Room Door`,
   `Temple Door`); lowercase descriptive labels are translated (`workshop door`, `east entrance`);
   anything with an official `dungeons.php` name uses it (`Crusader's Square`, Ulduar's
   `Inner Sanctum`). For the rest (`Teleporter`, `Cursed Spire of Ny'alotha`, `Decaying Cauldron`,
   `Cannon`, `Grounding Field`) there is no rule — `grep -n '<key>' lang/*_ai/mapping.php` and
   follow the majority. A reviewer asserting "must stay English" is a hypothesis, not evidence.
6. **Label-only files are fully translated** (`mapicontypes.php`: `Black Dragonflight Pledge Pin`
   → the locale's `spells.php` name), widely known WoW terms always (spell schools, dispel types,
   raid marker).
7. **Community jargon (`Pull`, `Pack`, `Add`, `Boss`, `Gauntlet`, `Skip`, `Trash`, `Combat Log`,
   `Affix`) stays English by default, but the locale sheet overrides the default** — Affix is
   translated in every Romance locale, Boss in es_MX/pt_BR, Pack in it_IT/pt_BR/ko/zh, Combat Log
   in zh_CN. When it stays English it stays in **Latin script** (ru_RU wrote *гонтлет*).
8. **Consistency inside your own diff.** Two new keys for the same feature in different files
   (`view_creator.php` vs `view_profile.php`), or sibling keys in one batch (`scheduled_publish_*`,
   `unlocks_after_*`), must agree; and a new key must match the locale's pre-existing sibling
   (`js.php`'s four `*_weight_label` keys already say *Poids*/*Peso*/*무게*) — check the file's
   existing lean before picking a word, and never copy a sibling locale's pick (es_MX copied
   es_ES's *planta*; its own file said *piso*).
9. **Counter means neutralise, not retaliate** (`view_compendium.php` "countered by"): pt_BR wrote
   *contra-atacar*, zh_CN nearly wrote 反击. Cast time, debuff, aura, crowd control: established
   MMO terms from the sheet.
10. **Raw `%s` counts have no plural bucket** — in declining languages write a count-invariant
    form (`Просмотров: %s`), don't add a bucket the source doesn't have.

Add a glossary row to the locale sheet whenever a pass forces a decision worth keeping.

## Per-locale style sheets — `locales/<locale>.md`

One file per locale, same template: a header table (register, the register-check grep, last full
pass, normalisations done), glossary, locale-specific conventions, known pre-existing drift that
must not be "fixed" without authorisation, and a one-line-per-pass history. The sheet is the
contract between passes — read it first, update it last.

| Locale | Register | Sheet |
|---|---|---|
| `de_DE_ai` | informal *du* | [locales/de_DE_ai.md](locales/de_DE_ai.md) |
| `es_ES_ai` | informal *tú* (*vosotros*) | [locales/es_ES_ai.md](locales/es_ES_ai.md) |
| `es_MX_ai` | informal *tú* (*ustedes*) | [locales/es_MX_ai.md](locales/es_MX_ai.md) |
| `fr_FR_ai` | formal *vous* | [locales/fr_FR_ai.md](locales/fr_FR_ai.md) |
| `it_IT_ai` | informal *tu* | [locales/it_IT_ai.md](locales/it_IT_ai.md) |
| `ko_KR_ai` | formal 하십시오체 (`-습니다`/`-세요`) | [locales/ko_KR_ai.md](locales/ko_KR_ai.md) |
| `pt_BR_ai` | informal *você* | [locales/pt_BR_ai.md](locales/pt_BR_ai.md) |
| `ru_RU_ai` | formal *Вы* | [locales/ru_RU_ai.md](locales/ru_RU_ai.md) |
| `zh_CN_ai` | formal 您 | [locales/zh_CN_ai.md](locales/zh_CN_ai.md) |
| `zh_TW_ai` | formal 您 — **skipped** (not offered on the site, Wotuu 2026-08-21) | [locales/zh_TW_ai.md](locales/zh_TW_ai.md) |

A new `*_ai` locale gets a sheet before its first pass: measure the register from the existing
machine output (count formal vs informal markers outside the excluded files), pick the dominant
one, list the terms the locale has already committed to (`grep -c` a dozen glossary words), and
write the table. Translate from the English only — never convert a sibling locale's output
(es_ES→es_MX, zh_CN→zh_TW): Blizzard's client strings differ between them.

## The gate — `verify.py`

Fails (exit 1) rather than warns. Asserts: the key set is identical before/after; every
previously non-empty value is byte-identical, except keys in an explicit rewrite map that went
exactly `from`→`to`; every work-list key is now non-empty and no other key was filled; every
placeholder, sprintf token, plural segment/marker and HTML tag survives. The dumps come from PHP
`require`, so a green run also proves every file still parses. Diff sanity: insertions equal
deletions plus one line per embedded newline; anything else means the wrong thing was touched.

## Overwriting existing values — `rewrite.py`

Only when explicitly authorised (a register normalisation, a terminology fix, review fixes).
Every entry states the exact value it expects; a mismatch is refused, never guessed.

```bash
python3 $SCRIPTS/make_rewrites.py $OUT/$LOCALE.after.json $OUT/new_values.json $OUT/rewrites.json
python3 $SCRIPTS/rewrite.py $LOCALE $OUT/rewrites.json
python3 $SCRIPTS/verify.py $OUT/$LOCALE.before.json $OUT/$LOCALE.after2.json $OUT/$LOCALE.worklist.json $OUT/rewrites.json
```

Chaining batches: merge keeping the **first** batch's `from` and the **last** batch's `to`. When
converting register, re-read the whole sentence — old machine output also predates the glossary
(*Zug* for pull, *Paket* for pack); fix those in the same line.

## Reviewing a finished locale with Codex

Worth it every time — 6 to 32 real findings per locale, all of a kind the gate cannot see. Use
`task`, not `codex review` (that's a code reviewer), anchor on the commit sha (the index
evaporates), and paste this prompt — it encodes every false-positive cluster the nine passes hit:

```bash
COMPANION=$(ls -d ~/.claude/plugins/cache/openai-codex/codex/*/scripts/codex-companion.mjs | sort -V | tail -1)
node "$COMPANION" task --background --effort high "Review the <LANGUAGE> TRANSLATION QUALITY of
the lines ADDED in: git show <sha> -- lang/<locale>. Report ONLY on '+' lines whose text changed
in that diff - not on pre-existing lines, not on lines that only moved because of '=>' realignment,
and never on lang/<locale>/view_admin.php (admin UI, deliberately English). Not a code review:
structure and placeholders are machine-verified. lang/en_US/ is the source; for each finding give
file:line, the English, the current text, a concrete replacement, and the evidence.
Rules of this locale, which are NOT findings: register is <formal/informal form> throughout.
NPC, boss and creature names inside mapping.php prose stay in English, ALWAYS, even when
lang/<locale>/npcs.php has an official localisation - do not flag them. Catalogued spell/item/
affix names use the official name in lang/<locale>/spells.php, affixes.php, enemies.php,
dungeons.php - those files beat any other source, including you; check them before claiming a
term is wrong. Community jargon (Pull, Pack, Add, Boss, Gauntlet, Skip, Trash, Affix) is
untranslated unless this locale's own pre-existing non-empty values translate it - check the
locale's existing usage (grep lang/<locale>/*.php) before flagging a term as wrong or
inconsistent; an established choice in the locale wins over your preference. Object/location
labels in mapping.php are per-item choices: flag one only if it contradicts lang/<locale>/dungeons.php.
Look for: invented game terms that have an official name in those files; the same English term
rendered differently within the diff or against the locale's existing usage; meaning errors;
register slips; clumsy or over-literal phrasing; grammar/agreement errors."
```

Triage, don't apply wholesale: confirm each finding is on a changed line (`git show <sha> -- <file>
| grep '^+'`, and that the *value* changed — realignment moves lines with identical text), check
every "official name" claim against the locale's files, check every "inconsistent" claim against
the locale's existing usage, then apply survivors with `rewrite.py` so the gate covers them.
Expect the reviewer to be right about invented game terms and intra-diff inconsistency, and wrong
about NPC names and object labels. Record counts and lessons in the locale sheet's History.

## Gotchas

- **`localization:sync` before `translate:scan`** — scan keys the locale lacks entirely are
  *missing*, not empty; `build_worklist.py` lists them as `absent`, `inject.py` can't fill them,
  `status.sh --sync` fixes it. `de_DE_ai` sat 24 keys behind for a day because of this.
- **`localization:sync` used to corrupt `\'` escapes** — it substituted the target's raw lemma
  into the *base* file's quote character, so a translation stored as `'Skycap\'n'` came back as
  `"Skycap\'n"` (literal backslash) once en_US quoted that string with `"`. Fixed in #4165
  (`LocalizationSync::$lemmaQuotes`, covered by `LocalizationSyncTest`); `check_sync.py` in
  `status.sh --sync` is the guard that would have caught it (~25 strings per locale were at risk).
- **`translate:scan` emits `'logic': []`** for a file with no strings; `build_worklist.py` drops
  non-string buckets. `lang/texts_to_translate.json` is tracked (committed during #4165); it is
  derived output and the scan rewrites it — commit it or not, it makes no difference.
- **Empty stubs come in two quote styles** (`''` or `""`, copied from the en_US source's quoting);
  `inject.py` matches both and writes single quotes.
- **Leaf keys repeat across sibling arrays** (`gong` three times in `mapping.php`); both scripts
  resolve the full dotted path from a nesting stack.
- **`inject.py` is not idempotent** — a filled key is no longer empty, so a re-run reports it
  `UNMATCHED`. Expected on a re-run; on a first run it means the stub is missing or oddly shaped.
- **opcache serves stale reads** to `dump_locale.php` right after another process edited the file
  (`composer run fix`, a previous `inject.py`): a spurious "current value is not the expected one"
  from `rewrite.py`. `docker compose exec -T app php -r "opcache_reset();"` before re-dumping.
- **`composer run fix` touches `lang/**`** — PhpCsFixer re-aligns `=>` once a filled value is
  multi-line. Whitespace only; run the gate again to prove it (rebuild the before-dump from HEAD
  into a throwaway `lang/<tmp>/` if you deleted it).
- **`translate:ksg` rewrites whole files** (`exportTranslations(preserveExisting: true)`), which is
  why this workflow injects line by line instead.
- **`js.php` is baked into the JS bundle** — invisible until `npm run dev`/`prod`; the `hotfix`
  skill can't ship it, a release can.
