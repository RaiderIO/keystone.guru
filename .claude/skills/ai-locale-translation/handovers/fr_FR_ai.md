# Handover: translating `fr_FR_ai` (#4165)

**Status: Done, 2026-08-20.** All 769 in-scope keys filled (matched the estimate below exactly),
gate green, `composer run analyse` clean, `composer run fix` only realigning whitespace. Formal
*vous* register confirmed correct throughout - no register cleanup needed. PR #4209.

**Codex review (commit `e0a27591f`) found 29 issues; 27 real, fixed in a follow-up commit
(`63cb567fa`):** official spell/immunity names not read from `spells.php` (`Vanish` ->
**Disparition**, not *Évanouissement*; `Shadowmeld` -> **Camouflage dans l'ombre**, not *Fondu
ombrageux*; `Divine Shield` -> **Bouclier divin**, not *Bouclier sacré*; `Blessing of
Spellwarding` -> **Bénédiction de protection des sorts**; `Anti-Magic Shell` -> **Carapace
anti-magie**); an in-file inconsistency where the new `df.algeth_ar_academy` buff labels didn't
match the pre-existing `midnight.algeth_ar_academy` block for the *same* dungeon (`+10% Soins
reçus` style, no space before `%`, no `de`, `Coup Critique` capitalised - the pre-existing block
should always be checked when a dungeon reappears under a different expansion key); Oculus drake
naming (`Drake` not `Dragonnet` - `npcs.php` already has `Drake émeraude`/`ambre`/`rubis` for
this exact zone); and several `route`/`itinéraire` consistency fixes where a sibling key in the
same file (`view_profile.php`'s `itinéraire`, `view_dungeonroute.php`'s `popular`/
`newly_published_routes`) had already picked one and the new key picked the other.

**Two findings were checked against the locale and rejected** - the same lesson `es_MX_ai`
already taught, worth restating because it happened again: `js.php`'s `arrow_weight_label` ("Weight")
was flagged for `Poids` -> `Épaisseur`, but four sibling `*_weight_label` keys already say
`Poids` (see the Weight row in `SKILL.md`'s glossary) - changing this one would have broken
consistency, not fixed it. And the Ulduar teleporter destination names (`Antechamber of Ulduar`,
`Inner Sanctum`, ...) were flagged as "keep in English", but `dungeons.php` already has official
French translations for four of them (`L'antichambre d'Ulduar`, etc.) - those four were changed
to match the official casing/wording exactly instead, and the five without an official source
(Colossal Forge, Conservatory of Life, ...) stayed translated for consistency with the four that
are confirmed official.

**Mechanical gotcha:** the worktree's PHP CLI (used by `dump_locale.php`) can serve a stale
`opcache`d read of a file that was just edited by another process (e.g. `composer run fix` or a
prior `inject.py` run within the same `docker compose exec` session) - one `rewrite.py` call
failed with a spurious "current value is not the expected one" because of this. Run
`docker compose exec -T app php -r "opcache_reset();"` before re-dumping if a value you just
wrote doesn't show up, before assuming the tooling itself is wrong.

Read `.claude/skills/ai-locale-translation/SKILL.md` first - it holds the whole procedure, the
scripts and the traps. This file only records what is **specific to `fr_FR_ai`**, measured
2026-08-20 right after `es_MX_ai` was finished (see `handovers/es_MX_ai.md` for that pass's notes,
including the Codex-review section - `fr_FR_ai` is a *different* Romance language with its own
register default, so don't assume its answers carry over, but the workflow lessons do).

## What you are doing

Fill the empty in-scope keys of `lang/fr_FR_ai/` by translating them yourself. No API key is
involved and `translate:ksg` is not used - you are the translation engine. Work in a fresh
worktree off this same branch (`sh/worktree.sh create 4165-regenerate-ai-translations
4165-regenerate-ai-translations` - reuses the branch, same as the `de_DE_ai`/`es_ES_ai`/`es_MX_ai`
passes did) unless Wotuu says otherwise. Ask whether to leave the result staged-and-uncommitted or
to commit - the last three passes all committed directly to this branch with no MR yet (7 locales
still pending after this one: `it_IT_ai`, `ko_KR_ai`, `pt_BR_ai`, `ru_RU_ai`, `zh_CN_ai`,
`zh_TW_ai`, plus this one), so that's the default unless told to change it.

## Numbers to expect

Re-run the sync + scan yourself before trusting any count below - these were measured once and
`en_US` keeps growing:

```bash
docker compose exec -T app php artisan localization:sync en_US fr_FR_ai
docker compose exec -T app php artisan translate:scan \
    --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation
```

| | |
|---|---|
| Keys in the locale | 18,918 |
| Empty | 2,986 |
| **In scope** | **769** |
| Deliberately skipped | 2,217 (`spells`, `npcs`, `dungeons`, `view_admin`, `datatables`, `validation`) |
| Absent from the locale entirely | 1 (`logic` - not a real key, an empty scan bucket; ignore) |

**Same 769 work-list keys as `es_MX_ai`/`es_ES_ai`** - identical English source set, since no new
`en_US` keys landed between the two passes this time. Per-file split: `mapping` 305,
`view_compendium` 144, `view_common` 62, `js` 54, `breadcrumbs` 37, `view_profile` 37, `controller`
28, `mapicontypes` 13, `view_creator` 12, `policy` 11, `spellmisstypes` 11, `spelldispeltype` 8,
`view_dungeonroute` 8, `spellschools` 7, `services` 6, `spellimmunities` 6, `view_team` 6, `rules`
5, `spellcounters` 5, `leafletdraw` 2, `view_errors` 2. `view_compendium.php` was **entirely
empty** for `es_MX_ai` (0 pre-existing translations to learn conventions from) - check whether
that's also true here before assuming there's nothing to grep.

## Register: **formal *vous*, not *tu*** - the opposite of German/Spanish

This is the one thing most likely to trip you up if you carry over the last two passes' instincts.
`de_DE_ai` is informal *du* and `es_ES_ai`/`es_MX_ai` are informal *tú*, both established
deliberately. `fr_FR_ai` is different: a blunt grep found **179 pre-existing *vous* hits** (`vous`,
`veuillez`, `cliquez`, `assurez-vous`, `êtes-vous`) against **1** *tu*-form hit, across the files
this workflow touches:

```bash
grep -rniE "\b(vous|veuillez|cliquez|sélectionnez|assurez-vous|êtes-vous)\b" lang/fr_FR_ai/*.php \
    | grep -v -E "spells\.php|validation\.php|datatables\.php|npcs\.php|dungeons\.php|view_admin\.php"
```

Translate the 769 new keys as **vous** (formal), matching the existing dominant register. Do not
"fix" this to *tu* the way `de_DE_ai`/`es_ES_ai` needed a *Sie*/*usted* -> informal cleanup - here
the informal form would be the outlier, not the fix. Re-run the grep above before finishing anyway,
since it's worth confirming your own additions didn't slip into *tu* by habit from the last two
passes.

## Check `spells.php` / `affixes.php` / `enemies.php` before inventing a game term

Same lesson as every prior locale - these three files hold Blizzard's own official localised names
and sit inside `lang/fr_FR_ai/` itself, excluded from this workflow precisely so they stay
authoritative:

```bash
grep -n "Pledge Pin" lang/en_US/spells.php && sed -n '4683,4687p' lang/fr_FR_ai/spells.php
grep -n "'name'" lang/fr_FR_ai/affixes.php        # official affix names
cat lang/fr_FR_ai/enemies.php                     # Prideful/Awakened enemy names
```

Confirmed already: `Teeming` -> **Foisonnant** (`affixes.php:54`). Check the others yourself - do
not assume they match German or Spanish's choices, and do not assume they match each other across
`es_ES_ai`/`es_MX_ai` either (those two disagreed on `Bolstering`/`Quaking`/`Teeming` - French is
its own locale with its own answers).

## Terminology already established in `fr_FR_ai` - read before choosing

Measured against pre-existing, non-empty values only (never against `es_ES_ai`/`de_DE_ai`, and
never against the excluded files):

| Term | `fr_FR_ai` convention | Evidence |
|---|---|---|
| Dungeon | **donjon** | 65 hits, no contested split like `es_MX_ai`'s mazmorra/calabozo - this one looks settled |
| Floor | **étage** | 46 hits |
| Affix | **affixe** (translated) | 61 hits vs 3 raw `affix`/`Affix` hits (2 of those are in the excluded `view_admin.php`) - translated, like Spanish, unlike German |
| Boss | **boss** (untranslated) | 36 hits vs 2 `patron`/`patronne` hits - the opposite of `es_MX_ai`'s established `Jefe`; do not translate it here |
| Pack | **pack** (untranslated) | 8 pre-existing hits (`js.php`, `view_common.php`) - matches the skill's default community-jargon rule; `es_MX_ai`'s first draft got this wrong for the *opposite* reason (translated it when it should have stayed English) - don't overcorrect into translating it here |
| Weight (line thickness) | **Poids** | Established in `js.php` (`weight_label`, `brushline_weight_label`, `path_weight_label`, `enemypatrol_weight_label` all say `Poids`) before this pass - the same trap that bit `es_MX_ai` (a `Peso`/`Grosor` split introduced by not checking this first) |
| Icon | **icône** (with circumflex) | 18 hits, no unaccented `icone` variant found |
| Enemy forces | **forces ennemies** (full form, no abbreviation established) | `js.php` intro/warning strings |
| Route | **contested-ish**: `itinéraire` (181) leans over `route` (73) | Not as lopsided as `donjon`, so still worth checking the surrounding file's lean before picking one, the same way `es_MX_ai` had to for Dungeon |
| Checkpoint | not yet established | `js.php`'s `enemyforcescheckpoint*` keys are part of this pass's 769, all currently empty - you're choosing this one fresh |

## The `es_MX_ai` Codex review taught two lessons worth carrying forward

Full detail in `handovers/es_MX_ai.md`, but the two mechanical takeaways:

1. **A single wrong assumption early on repeats itself for the whole pass.** `es_MX_ai` copied
   `es_ES_ai`'s `planta` for "floor" instead of checking `fr_FR_ai`'s own... wait, `es_MX_ai`'s own
   pre-existing `piso` - it never actually checked, just assumed the sibling Spanish locale's
   answer, or misread its own grep. **Actually run the grep for every term in the table above
   yourself before translating** rather than trusting this handover's numbers blindly (they can go
   stale, and you should be able to reproduce them).
2. **The community-jargon list (`Pull, Pack, Add, Boss, Gauntlet, Skip, Trash, Combat Log,
   Affix`) is a default, not a rule that overrides an established locale convention.** `es_MX_ai`
   correctly keeps `Affix`/`Boss` translated (`Afijo`/`Jefe`) because that's what the locale had
   already committed to before the pass, contra the jargon list's default. `fr_FR_ai` inverts part
   of this: `Boss` stays untranslated here (matches the jargon-list default) while `Affix` gets
   translated as `affixe` (contra the jargon-list default, same as Spanish). Neither locale is
   "wrong" - check each term's own pre-existing usage, don't pattern-match from the other locale or
   from the generic jargon list.

## Get it reviewed when you are done

`SKILL.md` -> "Reviewing a finished pass with Codex" has the invocation and the prompt template.
Adapt it for French, and explicitly add the instruction the `es_MX_ai` post-review note
recommends: ask the reviewer to check the locale's own pre-existing non-empty values for a term
before flagging it as a community-jargon or excluded-file violation, not just `spells.php`/
`affixes.php`/`enemies.php`. That single addition would have killed most of the false positives in
the `es_MX_ai` review.

## The one thing that must be true at the end

```
OK - 769/769 work-list keys filled, 0 rewritten (unless a register slip turned up), 18918 keys
intact, <N> existing translations unchanged
```

plus `composer run analyse` clean, `composer run fix` leaving only whitespace realignment in
`lang/fr_FR_ai/**`, and a `php artisan tinker` render check of one plural key
(`view_profile.view.route_count`) and one `%s` key (`view_common.dungeonroute.poster.views`) - the
same two keys every prior pass checked, so the render behaviour is already proven; this just
confirms `fr_FR_ai`'s copies work the same way. French plurals are simpler than Spanish/German (no
special rule needed beyond the existing `{0}|{1}|[2,*]` segments already in the source string), but
verify it anyway rather than assuming.
