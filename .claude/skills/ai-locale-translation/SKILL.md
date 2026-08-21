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
  **This includes NPC/creature/boss names, even when `npcs.php` has an official localised name
  for them** - the "grep the reference files first" advice below is for spell/ability names
  specifically, not for who's casting them. `it_IT_ai`'s first pass translated every enemy-type
  name it could find in `npcs.php` (`Vengeful Fleshreaper` -> *Mieticarne Vendicativo*, `Mole
  Machine`-adjacent creature packs, all the `*_variant` enemy-list keys), which broke from the
  unanimous convention `de_DE_ai`/`es_ES_ai`/`es_MX_ai`/`fr_FR_ai` had already settled on
  (`Firebrand Darkweaver`, `Withered Spearhide`, `Ramstein the Gorger` etc. all stayed English in
  those four) - caught by Codex review and reverted across ~75 keys. Before translating *any* name
  inside a mapping.php sentence, check what the four already-done locales did with the same key
  (`grep -n '<key>' lang/{de_DE,es_ES,es_MX,fr_FR}_ai/mapping.php`) rather than reasoning from the
  rule text alone.
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

### Spanish glossary (es_MX_ai, established in the #4165 pass)

`es_MX_ai` is ~85% byte-identical to `es_ES_ai` (same machine-translation origin), but the two
locales genuinely disagree on some game terms - always read from `es_MX_ai`'s own `affixes.php`/
`spells.php`/`enemies.php`, never assume the `es_ES_ai` answer applies. Confirmed differences:

| Affix | `es_ES_ai` | `es_MX_ai` |
|---|---|---|
| Bolstering | Reforzante | **Fortalecedor** |
| Quaking | Temblores | **Tembloroso** |
| Teeming | Abundante | **Pululante** |

| English | `es_MX_ai` | Notes |
|---|---|---|
| Dungeon | **contested**: mazmorra (57) vs calabozo (18) after this pass | Not settled - read the surrounding file's existing lean before picking one; do not default to `mazmorra` the way `es_ES_ai` can |
| Floor | **piso**, not `planta` | Unlike `es_ES_ai`'s `planta`, `es_MX_ai` had already committed to `piso` before this pass (21+ prior hits in `js.php` alone, 0 for `planta`) - first-pass draft used `planta` by mistake (an `es_ES_ai` copy-through) and had to be corrected after Codex review caught it |
| Wyrm | vermis | Confirmed from `npcs.php` (`Mana Wyrm` -> `Vermis de maná`, etc.) - same as `es_ES_ai` but independently verified, not copied |
| Fel (prefix) | vil | Confirmed from `npcs.php` (`Corcel vil`, `Larva vil`, ...) - same as `es_ES_ai` but independently verified |
| Enemy forces | fuerzas enemigas (full form); **FE** abbreviation introduced in the enemy-forces-checkpoint pills/tooltips where `es_ES_ai` also abbreviates | Existing `es_MX_ai` js.php keys always spelled it out in full before this pass |
| Checkpoint | punto de control | No prior `es_MX_ai` usage found; matches the `es_ES_ai` glossary answer |
| Weight (line thickness) | Peso | Established in `es_MX_ai/js.php` (`brushline_weight_label`, `path_weight_label`, ...) before this pass - not `Grosor` |
| Icon | ícono (accented) | Dominant in `es_MX_ai/policy.php`, `js.php` before this pass |
| Mole Machine | Máquina topo | Read from `es_MX_ai/npcs.php` (`Mole Machine to Stormwind` -> `Máquina topo a Ventormenta`) before translating the `mapping.php` notes that mention it |

**Register:** `es_MX_ai` was already informal *tú* throughout with zero *usted* hits on the standard
verb-form search - no register cleanup pass was needed (unlike `es_ES_ai`'s 30-key normalisation).

**Mapping.php proper nouns:** for the classic-dungeon notes (Blackfathom Deeps, Blackrock Depths,
etc.), Title-Case strings that read as an exact in-game object/NPC tooltip name (`Large Solid
Chest`, `Iron Gate`, `The Black Anvil`, `Activation Rune`) were left in English, matching what
`es_ES_ai` had already done for the same keys (verified independently, not copied) - because the
name is what the player reads off their own client. Lowercase/descriptive labels that are not exact
tooltip names (`workshop door`, `east entrance`) were translated.

### French glossary (fr_FR_ai, established in the #4165 pass)

| English | French | English | French |
|---|---|---|---|
| Dungeon | donjon | Floor | étage |
| Route | itinéraire (dominant, but check the surrounding file - some lean `route`, e.g. `view_dungeonroute.php`) | Pull | pull (untranslated) |
| Enemy forces | forces ennemies (full form); **FE** abbreviation introduced for checkpoint pills/tooltips/snackbars | Pack | pack (untranslated) |
| Affix | affixe (translated - like Spanish, unlike German's untranslated *Affix*; already dominant, 61:3, before this pass) | Boss | Boss (untranslated - the opposite of `es_MX_ai`'s `Jefe`) |
| Weight (line thickness) | Poids | Icon | icône (accented) |
| Checkpoint | point de contrôle (not yet established before this pass) | Teeming | Foisonnant (read from `affixes.php`) |

Add a row here whenever a new locale forces a decision worth keeping.

**Register:** `fr_FR_ai` is formal *vous* throughout - the opposite of `de_DE_ai`'s *du* and
`es_ES_ai`/`es_MX_ai`'s *tú*. A blunt grep found 171 pre-existing *vous*/`veuillez`/`cliquez`
hits against 19 *tu*-form hits before this pass; no register cleanup was needed, and the new
keys were all written as *vous* to match.

**Codex review found 27 real issues** (`handovers/fr_FR_ai.md` has the full table), the two
recurring lesson categories worth restating here:

- **Official game terms not read from `spells.php`/`affixes.php`/`enemies.php` first.** Four
  spell/immunity names were invented instead of looked up (`Vanish` -> *Disparition*, not
  *Évanouissement*; `Divine Shield` -> *Bouclier divin*, not *Bouclier sacré*; etc.) - always grep
  the exact English string in `spells.php` before translating a spell/ability name by hand, even
  a common one that seems obvious.
- **An existing translation elsewhere in the same file/feature was not checked before picking a
  different word.** `df.algeth_ar_academy`'s buff labels didn't match the pre-existing
  `midnight.algeth_ar_academy` block for the *same real dungeon* reused under a different
  expansion key (`+10% Soins reçus` vs `+10 % de soins reçus`) - when a dungeon reappears under a
  different top-level game-version key, check whether it already has a translated block
  elsewhere in the file first. Similarly, several `route`/`itinéraire` picks in one file
  disagreed with the same feature's sibling keys in another file (`view_creator.php` vs
  `view_profile.php`) - both new in the same pass, so nothing forced them to agree except
  checking.

Two review findings were checked against the locale and **rejected** as false positives - the
`js.php` "Weight" label matches four sibling `*_weight_label` keys already using `Poids` (listed
above), and the Ulduar teleporter destination names are already officially translated in
`dungeons.php`, so the locale's own convention won over the reviewer's "keep English" suggestion
for those four; the other five teleporter destinations (no official source) stayed translated
for consistency.

### Italian glossary (it_IT_ai, established in the #4165 pass)

| English | Italian | English | Italian |
|---|---|---|---|
| Dungeon | dungeon (untranslated) | Floor | piano |
| Route | contested, lean *percorso* (41:11 in `view_common.php`, 36:8 in `js.php`) | Pull | pull (untranslated) |
| Enemy forces | forze nemiche (full form); **FN** abbreviation introduced for checkpoint pills/tooltips/snackbars | Pack | **pacchetto** (translated - unlike French/Spanish's untranslated `pack`) |
| Affix | affisso (translated) | Boss | Boss (untranslated) |
| Weight (line thickness) | Peso | Icon | icona |
| Checkpoint | Checkpoint (untranslated - a common loanword in Italian gaming UI, no established prior value to check against) | Teeming | Abbondante (read from `affixes.php`) |
| Raid marker | marcatore di incursione (Raid -> *incursione*, already established in `js.php`/`mapicontypes.php` before this pass) | Season | Stagione |

Add a row here whenever a new locale forces a decision worth keeping.

**Register:** `it_IT_ai` is informal *tu* throughout, like German/Spanish and the opposite of
`fr_FR_ai`'s *vous*. A blunt grep found 164 pre-existing *tu*-form hits against 4 formal-*Lei*
hits before this pass; no register cleanup was needed, and the new keys were all written as *tu*
to match. Re-confirmed after the pass at 181:7, and every one of the 7 *Lei/Suo/Sua* hits turned
out to be a third-person possessive ("il **suo** dungeon" = *its* dungeon) rather than a formal
address - false positives, not register breaks.

**Codex review found ~90 real issues**, almost all of one kind - see the strengthened
"Proper nouns stay in English inside prose" rule above for the full story: the first pass
translated NPC/creature-type names in `mapping.php` prose using their official `npcs.php` names,
which is correct for spells but not for who casts them - it broke from the unanimous convention
every other locale had already settled on. ~75 keys were reverted via `rewrite.py` to match
(`Vengeful Fleshreaper`, `Firebrand Darkweaver`, `Withered Spearhide`, `Ramstein the Gorger`, and
so on all stayed/went back to English inside the translated sentence). Two smaller, correct
findings: three object names (`Iron Gate`, `Activation Rune`, `The Black Anvil`) that
`es_MX_ai`'s own glossary already named as the "exact in-game tooltip name" exception, and 6 keys
where "pack" had drifted to *gruppo* instead of this locale's own established *pacchetto*
(`js.php`'s `enemypack` label, checked before the pass started) - an internal-consistency slip,
not a wrong pick. The rest of the review (register, spell/immunity names against `spells.php`,
`Teeming`/`Affix` against `affixes.php`, wording polish) came back clean or was judgment-call
phrasing not worth a rewrite pass over.

**One lesson worth restating for the next locale doing `mapping.php`:** before translating *any*
named enemy/boss inside a prose sentence, grep the same key across the four already-done locales
first (`grep -n '<key>' lang/{de_DE,es_ES,es_MX,fr_FR}_ai/mapping.php`) - it is a faster and more
reliable signal than re-deriving the rule from official `npcs.php` names, which this pass's
mistake shows can lead you the wrong way even when done carefully and even when the official name
is real and correct.

### Korean glossary (ko_KR_ai, established in the #4165 pass)

| English | Korean | English | Korean |
|---|---|---|---|
| Dungeon | 던전 | Floor | 층 |
| Pull | 풀 (loanword, transliterated) | Pack | 무리/묶음 (translated, situational; `enemypack` UI label is 묶음) |
| Affix | 접사 (translated, matches pre-existing `js.php:93`) | Boss | 보스 (loanword) |
| Checkpoint | 체크포인트 (loanword) | Compendium | 도감 |
| Enemy forces | 적 병력 (full form); **적병** abbreviation for checkpoint pills/tooltips/snackbars | Weight (line thickness) | 무게 (majority 3:1 over 굵기) |
| Teeming | 번성 (read from `affixes.php`) | Nerubian | 네루비안 (read from `npcs.php` — **not** 네루빔, a mistake this pass made and fixed) |
| Pledge Pin | 충성의 핀 (read from `spells.php` — **not** 서약 핀, same category of mistake) | Ad-free (giveaway) | 광고 없음 (제공/상태), matches pre-existing `js.php:430-434` |

Add a row here whenever a new locale forces a decision worth keeping.

**Register:** `ko_KR_ai` is formal 하십시오체 (verb endings `-습니다`/`-세요`) throughout — a
different formality mechanism from French's pronoun-based *vous* but the same "formal" register
choice. 336 pre-existing `-습니다` hits and 80 `-세요` hits against near-zero informal hits before
this pass; no register cleanup was needed, and all new keys were written in `-습니다`/`-세요` form.

**Codex review found ~90 issues, but only 29 were real** — the rest were two repeat mistakes
worth restating here because they generalize to every remaining locale, not just Korean:

- **~15 findings pointed at lines this pass never touched** — pre-existing content elsewhere in
  `mapping.php` (the `midnight.*` sections) that Codex's review prompt flagged because it reviewed
  the whole locale's quality against `en_US`, not strictly the commit diff. Gate every finding
  against `git show <sha> -- lang/<locale>/<file> | grep '^+'` before applying it — this pass's
  out-of-scope rate was five times `fr_FR_ai`'s (3 of 18) because the review prompt didn't scope
  Codex to the diff tightly enough. Next locale: tell Codex explicitly to only report on lines
  present in that diff, not just "review the commit".
- **~70 findings were the it_IT NPC/boss-name mistake, from the reviewer's side this time.**
  Almost every "official Korean name ignored" finding pointed at an NPC/creature/boss name inside
  `mapping.php` prose — including the *exact same two names* (`Ramstein the Gorger`,
  `Vengeful Fleshreaper`) SKILL.md already cites as the canonical example of names that must stay
  English. This pass's review prompt tried to pre-empt this with an exception clause ("flag it
  only if an official localization exists") that itself contradicts the rule — names stay English
  **even when** `npcs.php` has an official localization, that is the whole point of the it_IT
  lesson above. Next locale: state the rule as an absolute in the review prompt, no exception
  clause, or expect the same false-positive cluster again.

The 29 real fixes: `df.algeth_ar_academy`'s 5 stat-buff labels didn't match the pre-existing
`midnight.algeth_ar_academy` block for the same real dungeon reused under a different
game-version key — the `fr_FR_ai` `algeth_ar_academy`/`windrunner_spire` lesson, repeated
exactly, because this pass didn't check for it up front; 5 Dragonflight Pledge Pin names used an
invented "서약 핀" instead of the official "충성의 핀" sitting in this locale's own `spells.php`;
4 Title-Case object labels (`Heavy Door`, `Stairwell Door`, `Supply Room Door`, `Sewer Gate`) were
translated instead of kept English, breaking the `es_MX_ai`-established exact-tooltip-name rule
applied inconsistently within the same pass; plus a `Json`→`JSON` casing fix, a redundant `#%d위`
double rank-marker, an invented `네루빔` for the officially-attested `네루비안`, 9
`unlocks_after_*` keys missing the `-됩니다` ending sibling keys used, and 2 `주문`→`플레이어
주문` disambiguation fixes. Full triage in `handovers/ko_KR_ai.md`.

**Do a dungeon-appears-twice check before translating any dungeon's `mapping.php` block**, not
just for locales that already had the `fr_FR_ai` incident:
```bash
grep -c "'<dungeon_slug>' => \["  lang/en_US/mapping.php
```
A count of 2 means the dungeon has blocks under two different top-level game-version keys (e.g.
Algeth'ar Academy under both `midnight` and `df` — a Midnight Season 2 remix of a Dragonflight
dungeon) — check the other block's existing translation before writing the empty one, every time.
This is now the second locale in a row this exact mistake has been caught in.

### Brazilian Portuguese glossary (pt_BR_ai, established in the #4165 pass)

| English | Portuguese | English | Portuguese |
|---|---|---|---|
| Dungeon | masmorra | Floor | andar |
| Route | rota | Pull | pull (untranslated) |
| Pack | pacote (already dominant, 4+:1, before this pass) | Enemy forces | forças inimigas (full form); **FI** abbreviation introduced for checkpoint pills/tooltips/snackbars |
| Affix | afixo (translated, already established in `affixes.php`) | Boss | chefe (already established) |
| Checkpoint | checkpoint (untranslated, already established in `js.php`) | Compendium | Compêndio (translated — no prior convention, matched the majority of other locales) |
| Weight (line thickness) | Peso (already established in `js.php`) | Icon | ícone (already established) |
| Teeming | Enxameante (read from `affixes.php` — note `js.php`'s pre-existing `enemypack_teeming_label` already said "Pululante", an untouched pre-existing inconsistency) | Crowd control | controle de multidão (already established in `affixes.php`) |
| Drake (the Oculus) | Draco (read from `npcs.php`: Emerald/Amber/Ruby Drake -> Draco Esmeralda/Âmbar/Rubi) | Teleporter | Teletransportador (already established in `mapping.php`, preferred over `npcs.php`'s unrelated NPC name "Teleportador") |

Add a row here whenever a new locale forces a decision worth keeping.

**Register:** `pt_BR_ai` is informal *você* throughout — 121 pre-existing `você` hits and 0 `tu`
hits across the locale before this pass; no register cleanup was needed, and all new keys were
written as *você*.

**`algeth_ar_academy` appears twice** (`midnight` and `df`), the `fr_FR_ai`/`ko_KR_ai` lesson
again — the `midnight` block was already translated, and the `df` block's 7 keys were matched to
it exactly, including a `+`/no-`+` and `%`-placement difference the two source strings genuinely
disagree on in `en_US` itself.

**Two spell/item names had official `spells.php` translations** and were used instead of invented
text: `Blazing Aegis` -> "Égide Fulgurante" and `Burning Chain` -> "Corrente Ardente"
(`mapping.map_icons.df.neltharus`) — both read like generic item pickups but are catalogued
spells with real localisations, the "grep spells.php first" rule paying off on a case that didn't
look like a spell name at a glance.

**Codex review found 34 issues, 32 real** — repeated the it_IT/ko_KR NPC-name-in-prose mistake on
five names that all have official `npcs.php` localizations (`Hackclaw's War-Band`,
`Bracken Warscourge`, `Khajin the Unyielding`, `Watcher Irideus`, `the Seven`) despite this same
SKILL.md file stating the rule right above; also two official-glossary misses (`Enrage` invented
as "Fúria" instead of `spells.php`'s "Enfurecer"; `Awakened` invented as "Desperto" instead of
`affixes.php`'s "Despertado"), and "counter" (as in "countered by") mistranslated as
`contra-atacar` (counter-*attack*) instead of `neutralizar` across 7 `view_compendium.php` keys.
Full triage, including two findings checked and rejected against the locale's own pre-existing
conventions, in `handovers/pt_BR_ai.md`.

### Russian glossary (ru_RU_ai, established in the #4165 pass)

| English | Russian | English | Russian |
|---|---|---|---|
| Dungeon | Подземелье (pre-existing) | Route | Маршрут (pre-existing) |
| Pack | Пак | Boss | Босс (pre-existing) |
| Enemy forces | Силы врага (full form, pre-existing); **СВ** abbreviation introduced for checkpoint pills/tooltips/snackbars | Compendium | Компендиум (no prior convention; matches the majority of other locales) |
| Checkpoint | Контрольная точка | Teeming | Кишащий (read from `affixes.php`/`view_common.php`, pre-existing) |
| Floor | Уровень (pre-existing) | Weight (line thickness) | Толщина (pre-existing) |
| Icon | Иконка (pre-existing) | Sidebar | Боковая панель (pre-existing) |
| Thumbnail | Эскиз (pre-existing, `js.php`) | Season | Сезон (pre-existing) |
| Teleporter | Телепорт (pre-existing) | Classification | Классификация (pre-existing) |
| Aura | Аура (read from `view_admin.php`) | Debuff | Дебафф (no prior convention; loanword, matches community usage) |

Add a row here whenever a new locale forces a decision worth keeping.

**Register:** `ru_RU_ai` is formal *Вы* throughout — 435 pre-existing `Вы`/`вам`/`Ваш`/`вас` hits
against 1 `ты`-family hit before this pass; no register cleanup was needed, and all new keys were
written as *Вы* (476:1 after the pass).

**`Gauntlet` is community jargon and must stay literal English, not transliterated to Cyrillic** —
this pass's first draft wrote it as `гонтлет` across 5 `mapping.php` keys (`kings_rest.gauntlet_gate`,
both `naxxramas*.gauntlet_room` keys, `brackenhide_hollow.witherlings_gauntlet`,
`halls_of_stone.brann_gauntlet`) before catching it in the Codex review — SKILL.md's own community-
jargon list already named `Gauntlet` as untranslated, and every other locale (`fr_FR_ai`, `es_MX_ai`,
`it_IT_ai`) confirms this by leaving it as literal `gauntlet` in the source language. One of those
same keys (`witherlings_gauntlet`) had also translated the NPC name `Witherlings` (confirmed in
`npcs.php`) to `Иссохшие` — the it_IT/ko_KR/pt_BR NPC-name-in-prose mistake, caught in the same pass.

**Catalogued spell names inside mapping.php prose use their official `spells.php` translation, not
English** — `Blazing Aegis` -> `Пылающая эгида`, `Burning Chain` -> `Горящая цепь`, `Stolen Power`
(Midnight's `city_of_threads` buff) -> `Похищенная сила`, all confirmed against `spells.php` before
writing, matching the `pt_BR_ai` precedent for the same two names. The Codex review flagged all
three as "should stay English" against the general proper-noun rule — checked against `spells.php`
and rejected, since the rule specifically carves out an exception for catalogued spell/item names
(only NPC/boss/creature names and a short list of exact tooltip object names stay English).

**Codex review found 97 candidate issues, 6 real** (plus the Gauntlet/Witherlings pair above) —
an unusually low real-finding rate for this pass, because the review prompt's proper-noun-in-
`mapping.php` exception clause was too broad and flagged roughly 20 correctly-translated object/
location names (`Cannon`, `Grounding Field`, `Slipstream`, `Cursed Spire of Ny'alotha`, `Cave of
Mam'toth`, `Den of Sseratus`, all 9 Ulduar teleporter destination names, `Witherbark Prisoner`, the
Stratholme postboxes) for reverting to English — each checked against either an official
`spells.php`/`dungeons.php` source or the multi-locale precedent already in this file (the 9 Ulduar
teleporters in particular: `fr_FR_ai` explicitly kept the 7 without an official source translated
"for consistency", see above) before rejecting. A further ~20 findings flagged `view_admin.php`
stubs newly added by `localization:sync` as "missing translations" — that file is permanently
excluded from this workflow (admin-only UI, English by convention), so these are not real. The rest
were stylistic rewrite preferences ("Враги могут быть одним из: X, Y или Z" flagged as a grammar
error on ~20 nearly-identical enemy-variant sentences) with no actual defect. The 6 real fixes: a
`Json`/`JSON` casing slip (`rules.php`), a `Пробуждённый`/`Пробужденный` spelling inconsistency
against the pre-existing `enemies.php` term for the *Awakened* affix, a "Cast time" mistranslation
(`Время накладывания` -> the standard MMO term `Время произнесения`, 3 keys), an accusative-case
slip (`охватывает этажей` -> `охватывает этажи`), and a genuine Russian declension bug: `%s
просмотров` renders wrong for raw (non-Laravel-pluralized) counts ending in 1-4 (`21 просмотров`
should be `21 просмотр`) — fixed by rewording to the count-invariant `Просмотров: %s` rather than
adding a plural bucket, since the source `%s` format has none to match. Full triage in
`handovers/ru_RU_ai.md`.

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
| `es_MX_ai` | 2980 | 769 | Done — #4165, 2026-08-20 (no register cleanup needed, already informal *tú*) |
| `fr_FR_ai` | 2986 | 769 | Done — #4165, 2026-08-20, PR #4209 (formal *vous* register, no cleanup needed; plus 27 Codex-review fixes, see `handovers/fr_FR_ai.md`) |
| `it_IT_ai` | 2984 | 769 | Done — #4165, 2026-08-21 (informal *tu* register, no cleanup needed; plus ~90 Codex-review fixes, almost all reverting NPC/creature names in `mapping.php` prose back to English — see the Italian glossary above) |
| `ko_KR_ai` | 3042 | 769 | Done — #4165, 2026-08-21, PR #4209 (formal 하십시오체 register, no cleanup needed; plus 29 Codex-review fixes — see `handovers/ko_KR_ai.md`, including a repeat of the `fr_FR_ai` duplicate-dungeon-block lesson) |
| `pt_BR_ai` | 2984 | 769 | Done — #4165, 2026-08-21 (informal *você* register, no cleanup needed; plus 32 Codex-review fixes — see `handovers/pt_BR_ai.md`, including a repeat of the it_IT/ko_KR NPC-name-in-prose mistake) |
| `ru_RU_ai` | 2841 | 769 | Done — #4165, 2026-08-21 (formal *Вы* register, no cleanup needed; plus 6 real Codex-review fixes and a self-caught Gauntlet/Witherlings slip — see `handovers/ru_RU_ai.md`) |
| `zh_CN_ai` | 3097 | 745 | Not started |
| `zh_TW_ai` | 3094 | 745 | Not started |

All nine also report the same 25 keys absent entirely (see the `localization:sync` gotcha).

Do one locale per session and per commit: the work lists are independent, and a failed gate should
never be ambiguous about which locale caused it.

Per-locale notes measured before starting a pass live in `handovers/<locale>.md` next to this file
(`handovers/ko_KR_ai.md` is the next one up). Write one for a locale when you finish it, so the pass
after yours starts from numbers rather than from a re-measurement.
