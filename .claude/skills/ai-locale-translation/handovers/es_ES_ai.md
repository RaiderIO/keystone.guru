# Handover: translating `es_ES_ai` (#4165) — DONE, 2026-08-20

All 769 in-scope keys filled (745 planned + 24 that `localization:sync` pulled in fresh at the
start of this pass: `controller.admintools.flash.caches_drop_queued`, all of `spellcounters.php`/
`spellimmunities.php`, and `view_creator.php`). Plus the ~usted → tú normalisation below. Gate:
`OK - 769/769 work-list keys filled, 30 rewritten, 18918 keys intact, 15908 existing translations
unchanged`. `composer run fix` (whitespace-only realignment) and `composer run analyse` both clean.
Everything below is the pre-pass research; kept for the next locale to reuse the pattern.

Read `.claude/skills/ai-locale-translation/SKILL.md` first — it holds the whole procedure, the
scripts and the traps. This file only records what is **specific to `es_ES_ai`**, measured
2026-08-20 right after `de_DE_ai` was finished the same way.

## What you are doing

Fill the 745 empty in-scope keys of `lang/es_ES_ai/` by translating them yourself. No API key is
involved and `translate:ksg` is not used — you are the translation engine. Work in the **main
checkout** on branch `4165-regenerate-ai-translations` (Wotuu's explicit instruction for this task:
no worktree, no new issue). Leave everything **staged and uncommitted**.

## Numbers to expect

| | |
|---|---|
| Keys in the locale | 18,891 |
| Empty | 2,953 |
| **In scope** | **745** |
| Deliberately skipped | 2,208 (`spells`, `npcs`, `dungeons`, `view_admin`, `datatables`, `validation`) |
| Absent from the locale entirely | 25 — not your job, `localization:sync` owns those |

The 745 are the **same key set** `de_DE_ai` had, so `lang/de_DE_ai/` is a finished worked example
of every single string you have to translate. Read the German when an English source is ambiguous —
but translate from the English, not from the German.

Per-file split of the 745: `mapping` 305, `view_compendium` 144, `view_common` 62, `js` 54,
`breadcrumbs` 37, `view_profile` 37, `controller` 27, `mapicontypes` 13, `policy` 11,
`spellmisstypes` 11, `spelldispeltype` 8, `view_dungeonroute` 8, `spellschools` 7, `services` 6,
`view_team` 6, `rules` 5, `leafletdraw` 2, `view_errors` 2.

## Register: informal **tú**

`es_ES_ai` is already mostly **tú** (~64 keys clearly informal: `Solo tú puedes ver esta ruta`,
`Actualiza tu ruta ahora editándola`). Translate the new keys as **tú**, and use `usted` nowhere.

There are **~26 existing keys still on formal *usted*** — mostly `leafletdraw` (`Haga clic para
comenzar a dibujar la línea.`) plus `js.route_migration_to_*_confirm_warning`,
`js.sidebar_enemy_skippable_info_label` and a handful of `su ruta` / `su perfil` possessives.
Normalising them is the same overwrite pass `de_DE_ai` got (212 keys there, so this is a much
smaller job). **Ask Wotuu before doing it** — overwriting existing translations is never part of the
default workflow; he authorised it explicitly for German. Find them with:

```bash
grep -nE "\b(usted|Seleccione|Haga|Introduzca|Pulse|Elija|Inténtelo|Asegúrese)\b|\b[Ss]u ruta\b" lang/es_ES_ai/*.php
```

Review every hit by hand — `No se puede guardar la ruta` is impersonal ("cannot be saved"), not
formal address, and there are many of those. That false positive is why the naive `puede` grep
reports 165 hits and the real number is ~26.

## `es_MX_ai` is 85% the same file

13,581 of 15,938 non-empty values are byte-identical to `es_ES_ai`. It is a separate locale
(Latin-American Spanish) and gets its own pass — **do not copy `es_ES_ai` into it wholesale**, and
do not translate both in one session. Real differences worth preserving when you get there:
`vosotros` vs `ustedes` for plural you, `ordenador`/`computadora`, and Blizzard's own es-ES vs es-MX
client strings differ for many game terms.

## Check `spells.php` / `affixes.php` / `enemies.php` before inventing a game term

This is the lesson that cost `de_DE_ai` four wrong terms, and it will bite Spanish harder because
you are less likely to know the Blizzard es-ES vocabulary by ear. Those three files are **excluded
from translation precisely because they already hold the official localised names**, and they sit
in the locale you are editing:

```bash
grep -n "Pledge Pin" lang/en_US/spells.php && sed -n '4683,4687p' lang/es_ES_ai/spells.php
grep -n "'name'" lang/es_ES_ai/affixes.php        # official affix names
cat lang/es_ES_ai/enemies.php                     # Prideful/Awakened enemy names
```

In German these were all wrong before the review and all correctable by a grep: *Anstecknadel* vs
the locale's own **Schwurbrosche**, *Stolzhaft* vs **Stolz / Manifestation des Stolzes**,
*Schurkenhülle* vs **Verhüllender Nebel**, *Inspiration* vs **Inspirierend**. Every affix name,
affix-enemy name and spell name you need is already sitting there in Spanish — read it, do not
invent it.

## Spanish glossary to establish

`de_DE_ai` recorded its decisions in SKILL.md's glossary table; do the same for Spanish as you go.
Terms that came up repeatedly and need one consistent answer before you start:

| English | Suggested | Note |
|---|---|---|
| Dungeon | mazmorra | already used in `es_ES_ai` |
| Route | ruta | established |
| Pull | pull | community term, untranslated |
| Enemy forces | fuerzas enemigas | needs a short form for the `:enemyForces EF` pills |
| Pack | pack / grupo | pick one and keep it |
| Floor | planta | check what existing keys use |
| Spell | hechizo | |
| Dispel type | tipo de disipación | |
| Miss types | tipos de fallo | |
| Compendium | compendio | |
| Crowd control | control de masas | |
| Raid marker | marcador de banda | |
| Prideful / Inspiring / Sanguine / Bursting / Bolstering | — | **read them out of `affixes.php`**, do not translate by hand |
| Rogue Shroud, spell names | — | **read them out of `spells.php`** |

Proper nouns: same rule as German — inside prose (`mapping.php` map-icon notes) keep the English
NPC/item/room names and translate the sentence around them; in label-only files (`mapicontypes.php`)
translate the whole label. Spanish has official Blizzard localisations for most Classic content, so
if you are confident of one, prefer it — but never guess.

## Get it reviewed when you are done

`SKILL.md` → "Reviewing a finished pass with Codex" has the invocation and the prompt. Use a Codex
`task` (not `codex review`, which is a code reviewer) and anchor it on the commit sha rather than
`git diff --cached`. Triage the findings instead of applying them: in German 3 of 18 pointed at
lines outside the reviewed commit, and one contradicted `affixes.php` outright.

## The one thing that must be true at the end

```
OK - 745/745 work-list keys filled, ... keys intact, ... existing translations unchanged
```

plus `composer run analyse` clean, `composer run fix` leaving only whitespace realignment in
`lang/es_ES_ai/**`, and a `php artisan tinker` render check of one plural key
(`view_profile.view.route_count`) and one `%s` key (`view_common.dungeonroute.poster.views`).
