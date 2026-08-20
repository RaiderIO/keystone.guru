# Handover: translating `es_MX_ai` (#4165)

Read `.claude/skills/ai-locale-translation/SKILL.md` first — it holds the whole procedure, the
scripts and the traps. This file only records what is **specific to `es_MX_ai`**, measured
2026-08-20 right after `es_ES_ai` was finished (see `handovers/es_ES_ai.md` for that pass's notes —
`es_MX_ai` is 85% byte-identical to it, see below, so it is worth skimming for context but **not**
for copying answers).

## What you are doing

Fill the empty in-scope keys of `lang/es_MX_ai/` by translating them yourself. No API key is
involved and `translate:ksg` is not used — you are the translation engine. Work in the **main
checkout**, on a fresh branch off `master` unless told otherwise (the `es_ES_ai` pass used
`4165-regenerate-ai-translations` directly on Wotuu's explicit instruction — confirm with him
whether this pass reuses that branch or gets its own; don't assume). Ask whether to leave the
result staged-and-uncommitted (as the `es_ES_ai` pass did) or to commit.

## Numbers to expect

Re-run the sync + scan yourself before trusting any count below — these were measured once and
`en_US` keeps growing:

```bash
docker compose exec -T app php artisan localization:sync en_US es_MX_ai
docker compose exec -T app php artisan translate:scan \
    --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation
```

| | |
|---|---|
| Keys in the locale | 18,920 |
| Empty | 2,982 |
| **In scope** | **771** |
| Deliberately skipped | 2,211 (`spells`, `npcs`, `dungeons`, `view_admin`, `datatables`, `validation`) |
| Absent from the locale entirely | 1 (`logic` — not a real key, an empty scan bucket; ignore) |

**771, not 769/745.** `es_MX_ai`'s work list is `es_ES_ai`'s work list (as filled 2026-08-20) plus
exactly **2 extra keys** that `en_US` grew between the two passes:
`mapping.map_icons.midnight.den_of_nalorakk.offering` and
`mapping.map_icons.midnight.den_of_nalorakk.warding_incense`. Translate those two fresh; for
everything else `lang/de_DE_ai/` and the now-finished `lang/es_ES_ai/` are worked examples of the
same English source string — read them when ambiguous, but translate from the English.

Per-file split of the 771: `mapping` 307, `view_compendium` 144, `view_common` 62, `js` 54,
`breadcrumbs` 37, `view_profile` 37, `controller` 28, `mapicontypes` 13, `view_creator` 12,
`policy` 11, `spellmisstypes` 11, `spelldispeltype` 8, `view_dungeonroute` 8, `spellschools` 7,
`services` 6, `spellimmunities` 6, `view_team` 6, `rules` 5, `spellcounters` 5, `leafletdraw` 2,
`view_errors` 2.

## `es_MX_ai` is 85% byte-identical to the now-finished `es_ES_ai` — do not copy it wholesale

Measured after the `es_ES_ai` pass landed: of `es_MX_ai`'s 15,938 non-empty values, 13,596 (85.3%)
are byte-identical to `es_ES_ai`. That is a strong signal the two locales started from the same
machine-translation run, **not** permission to reuse `es_ES_ai`'s output for the 771 empty keys —
it is a separate locale (Latin-American Spanish) and Blizzard's own es-MX client strings genuinely
differ from es-ES for a meaningful slice of game terms. Confirmed differences found in
`affixes.php` alone (already fully translated in both locales, so safe to compare):

| Affix | es_ES_ai | es_MX_ai |
|---|---|---|
| Bolstering | Reforzante | **Fortalecedor** |
| Quaking | Temblores | **Tembloroso** |
| Teeming | Abundante | **Pululante** |

Always read the term from `lang/es_MX_ai/affixes.php` / `spells.php` / `enemies.php` (see below),
never from the `es_ES_ai` equivalent, even though they will often turn out the same.

Other known es-ES vs es-MX differences to watch for while translating the 771 (none found
contaminating `es_MX_ai` as of this measurement, but check before assuming): `vosotros` (es-ES) vs
`ustedes` (es-MX) for plural "you" — grep for `vosotros` and treat any hit as a leftover to fix, not
a pattern to follow; `ordenador` (es-ES) vs `computadora` (es-MX) for "computer" — neither term
currently appears in `es_MX_ai`, so this locale has no established convention yet.

## Register: informal **tú** (already the case — no *usted* found)

Unlike `es_ES_ai`, which needed a 30-key formal→informal cleanup pass, `es_MX_ai` currently has
**zero** hits on the same *usted*-imperative search:

```bash
grep -rnE "\b(seleccione|haga|arrastre|suelte|continúe|presione|elija|indique|marque|está seguro)\b" \
    lang/es_MX_ai/*.php -i | grep -v -E "spells\.php|validation\.php|datatables\.php|npcs\.php|dungeons\.php|view_admin\.php"
```

Re-run it anyway before finishing — a register slip is exactly the kind of thing worth catching
before handoff, and this only checked the verb forms that came up in the `es_ES_ai` pass, not an
exhaustive list. Translate the 771 new keys as **tú**.

## Check `spells.php` / `affixes.php` / `enemies.php` before inventing a game term

Same lesson as `es_ES_ai` (which cost `de_DE_ai` four wrong terms before that), and it matters more
here because `es_MX_ai` and `es_ES_ai` genuinely disagree on some terms (see above) — you cannot
lean on "I remember what es_ES_ai used" the way you could half-guess from German. These three files
are **excluded from translation precisely because they already hold the official localised names**,
and they sit in the locale you are editing:

```bash
grep -n "Pledge Pin" lang/en_US/spells.php && sed -n '4683,4687p' lang/es_MX_ai/spells.php
grep -n "'name'" lang/es_MX_ai/affixes.php        # official affix names - confirmed to differ from es_ES_ai above
cat lang/es_MX_ai/enemies.php                     # Prideful/Awakened enemy names
```

`Prideful` is confirmed identical across both locales (`Orgulloso`, `enemies.php:9`) - do not assume
that generalises to every term.

## Terminology already established in `es_MX_ai` — check before choosing, don't assume `es_ES_ai`'s answer

| Term | `es_ES_ai` convention | `es_MX_ai` current usage | Verdict |
|---|---|---|---|
| Affix | afijo (translated), 17:0 before the ES pass | afijo, 24:0 (the 3 raw `affix` hits are PHP array keys `'affix' => [...]`, not translated text) | Same: **afijo** |
| Boss | Jefe (`js.php` `kill_zone_enemy_row_has_boss_label`) | Jefe (same key, same value, already present) | Same: **Jefe** |
| Dungeon | mazmorra, heavily dominant (~90%+ of hits) | **contested**: `mazmorra` 30 hits vs `calabozo` 18 hits across `rules.php`, `view_home.php`, `services.php`, `affixes.php`, `view_common.php`, `view_dungeonroute.php` | **Not settled — read the surrounding file's existing choice before picking one**, do not default to `mazmorra` on the strength of the ES pass |

The Dungeon split is the one real trap here: `es_ES_ai` let you default to `mazmorra` everywhere
because the locale had already committed to it almost unanimously. `es_MX_ai` has not - check each
file (`grep -c "mazmorra\|calabozo" lang/es_MX_ai/<file>.php`) before filling its empty keys, and
prefer whichever term that specific file already leans on so you don't end up with both terms
inside one file.

## Spanish glossary reference

`SKILL.md` now has a "Spanish glossary (es_ES_ai, established in the #4165 pass)" table with the
`es_ES_ai` decisions (enemy forces abbreviation `FE`, "Counters" → contramedidas, etc.) - read it,
but verify every game-specific term (not generic UI vocabulary) against `es_MX_ai`'s own
`spells.php`/`affixes.php`/`enemies.php` per the Dungeon trap above. Generic UI vocabulary (Route →
ruta, Pull → pull untranslated, Spell → hechizo, Compendium → compendio, Crowd control → control de
masas, Raid marker → marcador de banda, Season → temporada, Checkpoint → punto de control) is safe
to reuse as-is; it is not where es-ES/es-MX diverge.

Add a new "Spanish glossary (es_MX_ai)" table to `SKILL.md` if this pass makes any decision that
differs from the `es_ES_ai` table - the Dungeon split above will very likely produce one.

## Get it reviewed when you are done

`SKILL.md` → "Reviewing a finished pass with Codex" has the invocation and the prompt. Use a Codex
`task` (not `codex review`, which is a code reviewer) and anchor it on the commit sha (or, if left
staged/uncommitted like `es_ES_ai` was, tell the reviewer to review the staged diff instead and
re-anchor once it's committed). Triage the findings instead of applying them wholesale - ask it to
specifically flag anywhere the translation looks copied from `es_ES_ai` rather than chosen for
`es_MX_ai`, given the 85% base similarity.

## The one thing that must be true at the end

```
OK - 771/771 work-list keys filled, 0 rewritten (unless a register slip turned up), 18920 keys
intact, 15938 existing translations unchanged
```

plus `composer run analyse` clean, `composer run fix` leaving only whitespace realignment in
`lang/es_MX_ai/**`, and a `php artisan tinker` render check of one plural key
(`view_profile.view.route_count`) and one `%s` key (`view_common.dungeonroute.poster.views`) -
same two keys the `es_ES_ai` pass checked, so the render behaviour is already proven; this just
confirms `es_MX_ai`'s copies work the same way.
