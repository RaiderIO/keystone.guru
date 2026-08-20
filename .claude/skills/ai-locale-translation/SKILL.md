---
name: ai-locale-translation
description: Fill the empty translation keys of a `lang/*_ai` locale by translating them yourself, instead of running `translate:ksg` against a paid OpenAI/DeepL API key. Use when asked to translate a locale, fill missing/empty translations, or continue the #4165 AI-translation pass. Not for editing `lang/en_US` (source of truth), and not for the game-data locales (spells/npcs/dungeons) which are never hand-translated.
---

# Translating an `*_ai` locale by hand

`lang/<locale>_ai/` holds machine translations. The built-in pipeline is
`php artisan translate:ksg <locale> openai`, which POSTs every string to an external provider —
that needs a **paid API key** (`CHATGPT_API_KEY`, `DEEPL_API_KEY`, ...), which a Claude/ChatGPT
subscription does not grant. This skill is the alternative: **you** are the translation engine, and
the scripts here handle the mechanical parts (scoping, injection, verification) so the only thing
left to get right is the German/French/… itself.

`lang/en_US` is the source of truth and is **never** edited by this workflow.

## Order of operations

Run these from the repo root, in this order. Steps 1 and 2 are cheap; do not skip them.

```bash
LOCALE=de_DE_ai
SCRIPTS=.claude/skills/ai-locale-translation/scripts
OUT=/tmp/translate                        # anywhere outside the repo
mkdir -p $OUT

# 0. The scan file must exist and be current. It is untracked and lives only in the checkout
#    that generated it. Regenerate with the exclude list below if it is missing or stale.
#    localization:sync MUST have run before the scan, or the two key sets disagree (see Gotchas).
#    Always pass the target locale - the bare `localization:sync en_US` form loops over every
#    locale in config('language.all') and rewrites all of them.
docker compose exec -T app php artisan localization:sync en_US $LOCALE
docker compose exec -T app php artisan translate:scan \
    --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation

# 1. Snapshot the locale before touching it - this is what proves you changed nothing else.
docker compose exec -T app php $SCRIPTS/dump_locale.php $LOCALE > $OUT/before.json

# 2. Build the work list: keys that are empty AND in scope.
python3 $SCRIPTS/build_worklist.py $LOCALE $OUT/before.json $OUT/worklist.json

# 3. Translate. Read the work list a file at a time, write {"dotted.key": "translation"} JSON,
#    and inject each batch. Batches of 50-150 keys keep it reviewable.
python3 $SCRIPTS/inject.py $LOCALE $OUT/batch1.json

# 4. Gate. Must print OK before you hand anything off.
docker compose exec -T app php $SCRIPTS/dump_locale.php $LOCALE > $OUT/after.json
python3 $SCRIPTS/verify.py $OUT/before.json $OUT/after.json $OUT/worklist.json
```

Never run PHP on the host — always `docker compose exec -T app php`. The Python scripts run on the
host (WSL2) and only touch text files.

## Scope: only empty keys, and only some of them

Two independent filters, both mandatory:

1. **Only empty values.** An existing translation is never overwritten, however bad it looks. Some
   of the older gpt-3.5 output is inconsistent (`Dungeon` vs `Verlies`, `Etage` vs `Stockwerk`) —
   leave it. `inject.py` only ever rewrites a line whose value is literally `''` or `""`, so this
   is enforced mechanically, not by care.
2. **Only keys present in `lang/texts_to_translate.json`.** That file is `translate:scan`'s output
   and already excludes six files. `build_worklist.py` reports what it skipped and why.

Why those six files are excluded — do **not** hand-translate them:

| File | Why not |
|---|---|
| `spells.php`, `npcs.php`, `dungeons.php` | Blizzard proper nouns with **official** localizations. Guessing them produces names no player recognises. They come from `localization:exportspellnames` / `localization:syncnpcnames` / wago.tools instead. |
| `view_admin.php` | Admin-only UI, English by convention. |
| `validation.php` | Laravel framework strings; upstream ships real translations. |
| `datatables.php` | Populated by `localization:dt-download` from the DataTables CDN. |

For de_DE_ai that split was 745 in scope vs 2,214 deliberately skipped, out of 2,959 empty keys.

## The locale already contains an authoritative game glossary — grep it first

The six files this workflow refuses to translate are excluded **because they hold Blizzard's own
localised names**, which makes them the reference you check a game term against before inventing
one. They sit in the same locale directory you are editing:

| File | Holds |
|---|---|
| `spells.php` | every spell and item-effect name, keyed by spell id |
| `affixes.php` | official affix names *and* their descriptions |
| `enemies.php` | affix-enemy names (Prideful, Awakened, ...) |
| `npcs.php`, `dungeons.php` | NPC and dungeon names |

```bash
# before translating a game term, look for the English one and read off the localised name
grep -n "Pledge Pin" lang/en_US/spells.php          # -> line 4683-4687
sed -n '4683,4687p' lang/de_DE_ai/spells.php        # -> 'Schwurbrosche des roten Drachenschwarms'
```

Every finding the Codex review raised that turned out to be **verifiably** right was of this
shape - a term I had invented while the correct one was sitting in the same locale:

| I wrote | Locale already had | Where |
|---|---|---|
| Anstecknadel des Roten Drachenschwarms | **Schwurbrosche des roten Drachenschwarms** | `spells.php:4683-4687` |
| stolzer Feind / Stolzhaft | **Stolz** / **Manifestation des Stolzes** | `affixes.php:90`, `enemies.php:9` |
| Schurkenhülle | **Verhüllender Nebel** | `spells.php:787` |
| Inspiration | **Inspirierend** | `affixes.php` |

This cuts both ways: the reviewer *also* claimed Bursting is "Platzend" and Sanguine is "Blutiges
Sekret", and `affixes.php` says **Explosiv** and **Blutig**. The locale beats any outside source,
including a confident reviewer - check the claim before applying the fix.

## Translation rules

- **Placeholders are literal.** `:name`, `:count`, `%s`, `%d` must appear in the translation exactly
  as in the source, same set, same spelling. Never translate a placeholder name.
- **Plural strings keep their segment structure.** `{0} No routes|{1} :count route|[2,*] :count routes`
  needs the same number of `|` segments and the same `{n}` / `[n,*]` markers, in the same order.
- **HTML stays.** `<b>`, `<a href=":link">` and friends are copied through unchanged.
- **Proper nouns stay in English inside prose, but standalone labels are translated.** In
  `mapping.php` the strings are notes *about* named things (`Shadowforge Key`, `Hall of the
  Cursed`): translate the sentence, leave the name, because the name is what the player reads off
  their own English-or-not client. In label-only files such as `mapicontypes.php` the string *is*
  the UI label, so it gets translated as a whole (`Black Dragonflight Pledge Pin` ->
  `Anstecknadel des Schwarzen Drachenschwarms`). Widely known WoW terms (spell schools, dispel
  types, `Schlachtzugsmarkierung`) always get translated.
- **Keep the register of the file.** UI labels are short and impersonal; help text addresses the
  user. For German this workflow used informal **du** throughout (not *Sie*), matching the game.
- **Community jargon is not translated** where the community does not translate it: `Pull`, `Pack`,
  `Add`, `Boss`, `Gauntlet`, `Skip`, `Trash`, `Combat Log`, `Affix`.

### German glossary (de_DE_ai, established in the #4165 pass)

| English | German | English | German |
|---|---|---|---|
| Dungeon | Dungeon | Floor | Etage |
| Route | Route | Pull | Pull |
| Enemy | Gegner | Enemy forces | Feindkräfte (abbrev. `FK`) |
| Pack | Pack | Patrol | Patrouille |
| Spell | Zauber | Schools | Schulen |
| Dispel type | Bannart | Miss types | Fehlschlagarten |
| Counters | Konter | Crowd control | Kontrolleffekte |
| Compendium | Kompendium | Characteristics | Eigenschaften |
| Raid marker | Schlachtzugsmarkierung | Sidebar | Seitenleiste |
| Season | Saison | Thumbnail | Vorschaubild |
| Creator | Creator | Checkpoint | Checkpoint |
| Prideful | Stolz (the enemy: Manifestation des Stolzes) | Inspiring | Inspirierend |
| Bolstering | Stärkend | Bursting | Explosiv |
| Sanguine | Blutig | Rogue Shroud | Verhüllender Nebel |
| Overpulled | zusätzlich gepullt | PUG | Randomgruppe |
| Ad-free giveaway | werbefreier Zugang | Locked (a chest/door) | verschlossen |

Add a row here whenever a new locale forces a decision worth keeping.

**Register:** `de_DE_ai` is informal *du* throughout. The original gpt-3.5 output used formal *Sie*
in ~212 keys; those were normalised to *du* in a separate, explicitly authorised overwrite pass (see
"Normalising an existing locale" below). Any new locale should pick one register up front and say so
here, so the same clean-up is not needed twice.

### Spanish glossary (es_ES_ai, established in the #4165 pass)

| English | Spanish | English | Spanish |
|---|---|---|---|
| Dungeon | mazmorra | Floor | planta |
| Route | ruta | Pull | pull (untranslated) |
| Enemy | enemigo | Enemy forces | fuerzas enemigas (abbrev. `FE`) |
| Pack | pack (untranslated) | Patrol | patrulla |
| Spell | hechizo | Schools | escuelas |
| Dispel type | tipo de disipación | Miss types | tipos de fallo |
| Counters (noun) | contramedidas | Crowd control | control de masas |
| Compendium | compendio | Characteristics | características |
| Raid marker | marcador de banda | Sidebar | — (not yet needed) |
| Season | temporada | Thumbnail | vista previa |
| Creator | creador | Checkpoint | punto de control |
| Affix | afijo (translated - unlike German's untranslated *Affix*; already the dominant convention in this locale, 17:0 before this pass) | Boss | jefe (already established in this locale) |
| Prideful | Orgulloso (read from `affixes.php`/`enemies.php`, do not invent) | Inspiring / Bolstering / Sanguine / Bursting / Teeming | — **read them out of `affixes.php`**, do not translate by hand |
| Fel (prefix) | vil | Wyrm | vermis |

Add a row here whenever a new locale forces a decision worth keeping.

**Register:** `es_ES_ai` is informal *tú* throughout. The original machine output was already mostly
*tú*; ~30 keys still on formal *usted* (mostly `leafletdraw.php` imperatives and `js.php`/
`view_common.php` possessives) were normalised to *tú* in the same pass that filled the empty keys,
explicitly authorised by Wotuu. `validation.php`, `datatables.php` and other excluded files were left
untouched even where an *usted* form appeared, since this workflow never hand-edits those.

## Normalising an existing locale (overwriting existing values)

The default workflow never overwrites a non-empty value. When Wotuu explicitly asks for one -
a register change, a terminology fix - use `rewrite.py`, which does overwrite but refuses to
guess: every entry states the exact value it expects to find, and a mismatch is an error.

```bash
# 1. Write {"dotted.key": "new value"} for the keys you want changed.
# 2. Pair it against the locale's current values - the "from" side stays machine-generated,
#    so the guard can never be a mistyped copy of the old string. It also refuses a change
#    that reflows the indentation of a multi-line value.
python3 $SCRIPTS/make_rewrites.py $OUT/current.json $OUT/new_values.json $OUT/rewrites.json
python3 $SCRIPTS/rewrite.py $LOCALE $OUT/rewrites.json

# 3. Gate, now with the rewrite map - those keys may change, nothing else may.
python3 $SCRIPTS/verify.py $OUT/before.json $OUT/after.json $OUT/worklist.json $OUT/rewrites.json
```

Chaining several batches: merge them keeping the **first** batch's `from` and the **last** batch's
`to`, or the gate will compare against an intermediate value that no longer exists.

**Re-read the whole sentence, not just the pronouns.** A register conversion drags in strings the
original machine translation wrote before any glossary existed, so the sentence you are touching may
also be using *Zug* for `pull` and *Paket* for `pack`. Fix those in the same pass - they are lines
you are already changing, and leaving them is how a locale ends up with a glossary it does not
follow.

Finding formal-address keys in a German locale (adapt the markers per language):

```bash
grep -nE "\b(Sie|Ihnen|Ihre[nmrs]?|Ihr)\b" lang/$LOCALE/*.php
```

Review every hit by hand: sentence-initial *Sie* is often third-person *sie* ("**Sie** laufen zur
Mitte der Brücke" = the guards do), which must not be touched. Three such false positives came up
in the `de_DE_ai` pass; where the ambiguity was genuinely confusing the sentence was reworded
instead ("Andere können beitreten...").

## The gate

`verify.py` fails (exit 1) rather than warns, and it asserts:

1. the key set is identical before/after — nothing added, dropped or re-nested;
2. every previously non-empty value is byte-identical, except keys listed in an explicit
   rewrite map, which must have gone exactly from their recorded `from` to their recorded `to`;
3. every work-list key is now non-empty, and no key outside the work list was filled;
4. every `:placeholder`, `%s`/`%d`, plural segment and HTML tag of the source survives.

Because the before/after dumps come from PHP `require`, a green run also proves every file still
parses. `php -l` is not a substitute — it says nothing about structure.

Sanity check on the diff: insertions should equal deletions plus one extra line per newline
embedded in a translated string. Anything else means `inject.py` touched something it should not
have.

## Gotchas found the hard way

- **Empty stubs come in two quote styles.** `localization:sync` copies the en_US quoting, so a
  source string containing an apostrophe leaves `=> "",` and everything else leaves `=> '',`.
  `inject.py` matches both and normalises to single quotes.
- **Leaf keys repeat across sibling arrays.** `gong` exists three times in `mapping.php` under
  different dungeons. `inject.py` resolves the full dotted path from a nesting stack; never match
  on a bare key name.
- **`inject.py` is not idempotent, by design.** A key it already filled is no longer empty, so a
  re-run reports it `UNMATCHED`. On a re-run that is expected; on a first run it means the key is
  absent from the file or its stub has an unexpected shape — investigate before continuing.
- **`localization:sync` must run before `translate:scan`.** The scan reads en_US, so keys added to
  en_US after the last sync appear in the scan while the locale has no stub for them at all. Those
  are *missing* keys, not empty ones — `build_worklist.py` lists them separately and this workflow
  does not create them; re-run `localization:sync` instead. In the #4165 pass that was 25 keys,
  most of them the whole of `view_creator.php`.
- **`translate:ksg` rewrites entire files** via `exportTranslations(preserveExisting: true)`, which
  reformats untouched keys and makes "only empty keys changed" unverifiable. That is the other
  reason this workflow injects line-by-line instead of reusing it.
- **`composer run fix` does touch `lang/**`, and that is fine.** PhpCsFixer re-aligns the `=>` of a
  key block once a filled value makes a neighbouring string multi-line. It changes whitespace only.
  Run the gate again afterwards to prove that (rebuild the "before" dump from HEAD if you deleted
  it: copy `git show HEAD:lang/<locale>/*.php` into a throwaway `lang/<tmp>/` and dump that).
- **`js.php` strings are baked into the JS bundle at build time.** They will not appear in the UI
  until assets are rebuilt (`npm run dev` / `prod`); this is also why the `hotfix` skill cannot ship
  them. Keep escaped apostrophes out of `js.php` values where you can.

## Reviewing a finished pass with Codex

Worth doing - it found 15 real issues in the `de_DE_ai` pass, four of them terminology errors that
the gate can never catch. Two things make or break it:

- **Use `task`, not `review`.** `codex review` is a code reviewer; on a lang diff it inspects
  placeholders and array syntax, which `verify.py` already proves. You want a prose review.
- **Anchor it on a commit, not the index.** `git show <sha> -- lang/<locale>` is stable;
  `git diff --cached` evaporates the moment anyone commits the work while the review runs.

```bash
COMPANION=$(ls -d ~/.claude/plugins/cache/openai-codex/codex/*/scripts/codex-companion.mjs | sort -V | tail -1)
node "$COMPANION" task --background --effort high "Review the <LANGUAGE> TRANSLATION QUALITY of
git show <sha> -- lang/<locale>. Not a code review - the structure and placeholders are already
machine-verified. lang/en_US/ is the source of truth. For each finding give file:line, the English
source, the current text and a concrete replacement. Cross-check every game term against
lang/<locale>/spells.php, affixes.php and enemies.php, which hold the official localised names -
those files win over any external source. Look for: register breaks; clumsy or over-literal
phrasing; game terminology players do not use; the same English term rendered inconsistently within
the diff; and meaning errors. Community jargon (Pull, Pack, Add, Boss, Gauntlet, Skip, Trash,
Affix) is deliberately untranslated. Proper nouns stay English inside prose notes in mapping.php
while the sentence around them is translated; label-only files like mapicontypes.php are fully
translated."
```

Then triage rather than apply wholesale - in the German pass 3 of 18 findings pointed at lines
outside the reviewed commit (check with `git blame -L <n>,<n>`), one was half wrong against
`affixes.php`, and one proposed hard-coding grammatical gender for runtime placeholders. Apply the
survivors with `rewrite.py` so the gate still covers them.

## Per-locale state

Measured 2026-08-20. Every locale has the **same 745 in-scope keys** - the same English source
strings, so a work list built for one is the work list for all nine. The "empty" column differs only
in the excluded files, which are not this workflow's business.

| Locale | Empty total | In scope | Status |
|---|---|---|---|
| `de_DE_ai` | 2959 | 745 | Done — #4165, 2026-08-20 (plus 212 *Sie* → *du* and 31 post-review rewrites) |
| `es_ES_ai` | 2953 | 745 (769 after re-sync) | Done — #4165, 2026-08-20 (plus 30 formal *usted* → informal *tú* rewrites) |
| `es_MX_ai` | 2953 | 745 | Not started |
| `fr_FR_ai` | 2959 | 745 | Not started |
| `it_IT_ai` | 2957 | 745 | Not started |
| `ko_KR_ai` | 3042 | 745 | Not started |
| `pt_BR_ai` | 2957 | 745 | Not started |
| `ru_RU_ai` | 2841 | 745 | Not started |
| `zh_CN_ai` | 3097 | 745 | Not started |
| `zh_TW_ai` | 3094 | 745 | Not started |

All nine also report the same 25 keys absent entirely (see the `localization:sync` gotcha).

Do one locale per session and per commit: the work lists are independent, and a failed gate should
never be ambiguous about which locale caused it.

Per-locale notes measured before starting a pass live in `handovers/<locale>.md` next to this file
(`handovers/es_ES_ai.md` is the next one up). Write one for a locale when you finish it, so the pass
after yours starts from numbers rather than from a re-measurement.
