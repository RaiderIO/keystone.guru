# Handover: translating `it_IT_ai` (#4165)

Read `.claude/skills/ai-locale-translation/SKILL.md` first - it holds the whole procedure, the
scripts and the traps. This file only records what is **specific to `it_IT_ai`**, measured
2026-08-20 right after `fr_FR_ai` was finished (PR #4209 - see `handovers/fr_FR_ai.md` for that
pass's notes, including its Codex-review section; `it_IT_ai` is a different language with its own
register default and its own contested terms, so don't assume its answers carry over, but the
workflow lessons - especially "check the locale's own pre-existing conventions before trusting a
reviewer or another locale" - do).

**#4165 covers all ten `*_ai` locales, not just the ones already done.** `de_DE_ai`, `es_ES_ai`,
`es_MX_ai` and `fr_FR_ai` are finished (PRs #4209 for the last one; the first three landed
directly on this branch before any PR existed). `it_IT_ai` is next; five more remain after it
(`ko_KR_ai`, `pt_BR_ai`, `ru_RU_ai`, `zh_CN_ai`, `zh_TW_ai`). Work on the same branch,
`4165-regenerate-ai-translations` - do not create a new branch per locale.

## What you are doing

Fill the empty in-scope keys of `lang/it_IT_ai/` by translating them yourself. No API key is
involved and `translate:ksg` is not used - you are the translation engine. Work in a fresh
worktree off this same branch (`sh/worktree.sh create 4165-regenerate-ai-translations
4165-regenerate-ai-translations` - reuses the branch, same as every prior pass did) unless Wotuu
says otherwise. Commit directly to this branch as you finish each stage (translate, then
Codex-review fixes, then this handover's "Done" update) - that's what every prior pass did, and
`fr_FR_ai`'s PR #4209 is already open and draft, so push onto it rather than opening a second PR
for the same branch.

## Numbers to expect

Re-run the sync + scan yourself before trusting any count below - these were measured once and
`en_US` keeps growing:

```bash
docker compose exec -T app php artisan localization:sync en_US it_IT_ai
docker compose exec -T app php artisan translate:scan \
    --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation
```

| | |
|---|---|
| Keys in the locale | 18,918 |
| Empty | 2,984 |
| **In scope** | **769** |
| Deliberately skipped | 2,215 (`spells`, `npcs`, `dungeons`, `view_admin`, `datatables`, `validation`) |
| Absent from the locale entirely | 1 (`logic` - not a real key, an empty scan bucket; ignore) |

**Same 769 work-list keys as `fr_FR_ai`/`es_MX_ai`/`es_ES_ai`** - identical English source set,
since no new `en_US` keys have landed since the last pass. Per-file split: `mapping` 305,
`view_compendium` 144, `view_common` 62, `js` 54, `breadcrumbs` 37, `view_profile` 37, `controller`
28, `mapicontypes` 13, `view_creator` 12, `policy` 11, `spellmisstypes` 11, `spelldispeltype` 8,
`view_dungeonroute` 8, `spellschools` 7, `services` 6, `spellimmunities` 6, `view_team` 6, `rules`
5, `spellcounters` 5, `leafletdraw` 2, `view_errors` 2. `mapping.php` (270 pre-existing empty
stubs) and `view_compendium.php` (144 - entirely empty, same as it was for `es_MX_ai`) are the
two files with nothing to learn conventions from before this pass; the rest have real
pre-existing translations worth grepping first.

## Register: informal *tu*, like German/Spanish - **not** `fr_FR_ai`'s formal *vous*

A blunt grep found **164 pre-existing *tu*-form hits** (`tu`, `tuo`/`tua`/`tuoi`/`tue`, `clicca`,
`seleziona`) against **4** formal-*Lei* hits, across the files this workflow touches:

```bash
grep -rniE "\b(tu|tuo|tua|tuoi|tue|clicca|seleziona)\b" lang/it_IT_ai/*.php \
    | grep -v -E "spells\.php|validation\.php|datatables\.php|npcs\.php|dungeons\.php|view_admin\.php"
```

Translate the 769 new keys as informal **tu**, matching the dominant register - the opposite of
what the immediately-preceding `fr_FR_ai` pass needed. Re-run the grep above before finishing
anyway, since the last two passes both re-confirmed the register at the end and it costs nothing.

## Check `spells.php` / `affixes.php` / `enemies.php` before inventing a game term

Same lesson as every prior locale, and the single most common category of real defect the Codex
review has found in every pass so far (four spell/immunity names in `fr_FR_ai` alone - see its
handover). These three files hold Blizzard's own official localised names and sit inside
`lang/it_IT_ai/` itself, excluded from this workflow precisely so they stay authoritative:

```bash
grep -n "Pledge Pin" lang/en_US/spells.php && sed -n '4683,4687p' lang/it_IT_ai/spells.php
grep -n "'name'" lang/it_IT_ai/affixes.php        # official affix names
cat lang/it_IT_ai/enemies.php                     # Prideful/Awakened enemy names
```

Do not assume `it_IT_ai`'s answers match German/Spanish/French's - check independently, and do
not skip this for a spell/ability name that "seems obvious" (that specific mistake, e.g.
`Vanish` -> an invented word instead of the official one, was the single most common defect
category in the `fr_FR_ai` review).

## Terminology already established in `it_IT_ai` - read before choosing

Measured against pre-existing, non-empty values only (never against another locale, and never
against the excluded files):

| Term | `it_IT_ai` convention | Evidence |
|---|---|---|
| Dungeon | **untranslated** `dungeon`/`Dungeon` | 13+ hits in `view_common.php` alone. **Except** `breadcrumbs.php`'s admin section, which uses `Spedizioni`/`Nuova spedizione` (2 hits, both under `home.admin.dungeons.*`) - a minor pre-existing inconsistency, not a real split; default to the dominant untranslated `dungeon` for any new key unless it sits directly next to the `Spedizioni` block |
| Floor | **piano** | Multiple hits in `js.php` (`dungeonfloorswitchmarker_*`, `floorunion_*`) |
| Affix | **affisso** (translated) | 18 hits in `affixes.php` (the official name), 2 more in `npcclassifications.php` - translated, like French/Spanish, unlike German's untranslated *Affix* |
| Boss | **boss** (untranslated) | Multiple hits in `view_common.php` (`unkilled_important_enemy_opacity_title`, `awakened_enemy_set_title`, `affixes_title`) - matches the community-jargon default, same as `fr_FR_ai`, opposite of `es_MX_ai`'s translated `Jefe` |
| Pack | **pacchetto** (translated) | `mapping.php:367` (`hidden_pack_in_cave`), `js.php:138`/`211` - the opposite of `fr_FR_ai`'s and Spanish's untranslated `pack`; check this is still right for any *new* pack-related string, since it breaks from what the last two passes assumed by default |
| Route | **contested, lean percorso**: `percorso`/`percorsi` (41 in `view_common.php`, 36 in `js.php`) vs `rotta`/`rotte` (11 in `view_common.php`, 8 in `js.php`) | Not settled - read the surrounding file's existing lean before picking one, the same way `es_MX_ai` had to for Dungeon and `fr_FR_ai` had to for Route/itinéraire |
| Weight (line thickness) | **Peso** | Established in `js.php` (`weight_label`, `brushline_weight_label`, `path_weight_label`, `enemypatrol_weight_label`) and `view_common.php` (`default_line_weight`) before this pass - the same trap that bit `es_MX_ai` and nearly bit `fr_FR_ai`'s Codex review (it flagged `Poids`/`Peso` incorrectly there too - check this file's own convention before trusting a reviewer's "this seems wrong" instinct on a *_weight_label key) |
| Icon | **icona** | `js.php` (`mapicon`, `map_icon`, `map_icon_map_icon_type_id_label`) |
| Enemy forces, Checkpoint | not yet established | Same as `fr_FR_ai` - you're choosing these fresh. `js.php`'s `enemyforcescheckpoint*` keys are part of this pass's 769, all currently empty |

## Get it reviewed when you are done

`SKILL.md` -> "Reviewing a finished pass with Codex" has the invocation and the prompt template.
Adapt it for Italian, and keep the addition the `es_MX_ai`/`fr_FR_ai` post-review notes both
recommend: ask the reviewer to check the locale's own pre-existing non-empty values for a term
before flagging it as a community-jargon or excluded-file violation, not just
`spells.php`/`affixes.php`/`enemies.php`. In `fr_FR_ai`'s pass this single instruction still
didn't stop two false positives (the `js.php` Weight label, and the Ulduar teleporter names) -
both were only caught because the pass checked the locale's own file before applying the
reviewer's suggestion. Do the same here: verify every "this is wrong" claim against
`it_IT_ai`'s own existing text before accepting it, especially for `pacchetto`/`percorso`/`peso`,
which are exactly the kind of established-but-surprising choices a reviewer with no access to
this file's history is likely to flag.

Anchor the review on a commit, not the index (`git show <sha> -- lang/it_IT_ai`) - `git diff
--cached` evaporates the moment anyone commits the work while the review runs, and the review
takes ~10 minutes in the background (`--background`), so commit before dispatching it.

## The one thing that must be true at the end

```
OK - 769/769 work-list keys filled, 0 rewritten (unless a register slip turned up), 18918 keys
intact, <N> existing translations unchanged
```

plus `composer run analyse` clean, `composer run fix` leaving only whitespace realignment in
`lang/it_IT_ai/**`, and a `php artisan tinker` render check of one plural key
(`view_profile.view.route_count`) and one `%s` key (`view_common.dungeonroute.poster.views`) - the
same two keys every prior pass checked, so the render behaviour is already proven; this just
confirms `it_IT_ai`'s copies work the same way.

## Mechanical gotcha carried over from `fr_FR_ai`

The worktree's PHP CLI (used by `dump_locale.php`) can serve a stale `opcache`d read of a file
that was just edited by another process (e.g. `composer run fix`, or a prior `inject.py` run
within the same session) - a `rewrite.py` call can fail with a spurious "current value is not
the expected one" because of this. Run `docker compose exec -T app php -r "opcache_reset();"`
before re-dumping if a value you just wrote doesn't show up, before assuming the tooling itself
is wrong.

## When you finish

Update this file's status (see `handovers/fr_FR_ai.md` for the pattern - a `**Status: Done,
<date>.**` line at the top with the final numbers, plus a summary of what the Codex review
found and how it was triaged), update `SKILL.md`'s per-locale state table and its "next locale"
pointer (currently pointing here), and add an Italian glossary section to `SKILL.md` next to the
German/Spanish/French ones for whatever terminology decisions this pass makes.
