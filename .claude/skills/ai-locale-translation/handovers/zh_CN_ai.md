# Handover: translating `zh_CN_ai` (#4165)

Read `.claude/skills/ai-locale-translation/SKILL.md` first — it holds the whole procedure, the
scripts and the traps, plus the growing per-language glossary/lesson sections (German, Spanish x2,
French, Italian, Korean, Portuguese, Russian). This file only records what is **specific to
`zh_CN_ai`**, measured 2026-08-21 right after `ru_RU_ai` was finished (commits `89a7ce3ea` /
`81bc1967c` — see `handovers/ru_RU_ai.md` for that pass's notes, including its Codex-review
triage). Simplified Chinese is a different language with its own register question and its own
contested terms, so don't assume its glossary answers carry over — but every workflow lesson does,
especially the two below, which the last three locales in a row have each independently tripped on
at least one of.

**#4165 covers all ten `*_ai` locales.** `de_DE_ai`, `es_ES_ai`, `es_MX_ai`, `fr_FR_ai`,
`it_IT_ai`, `ko_KR_ai`, `pt_BR_ai` and `ru_RU_ai` are finished. `zh_CN_ai` is next; `zh_TW_ai`
remains after it. Work on the same branch, `4165-regenerate-ai-translations` — do not create a new
branch per locale. `sh/worktree.sh create 4165-regenerate-ai-translations
4165-regenerate-ai-translations` reuses the branch, same as every prior pass; a stack for it was
already up as of this handover (worktree at
`/home/wouterkoppenol/Git/private/keystone.guru-worktrees/4165-regenerate-ai-translations`) — check
`sh/worktree.sh list` before creating a duplicate.

## Numbers to expect

Re-run the sync + scan yourself before trusting any count below — these were measured once and
`en_US` keeps growing:

```bash
docker compose exec -T app php artisan localization:sync en_US zh_CN_ai
docker compose exec -T app php artisan translate:scan \
    --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation
```

| | |
|---|---|
| Empty total | 3124 |
| In scope | 769 — **identical set of English source strings** to every other locale in this pass, so `ru_RU_ai`'s batch plan (by file: `mapping` 305, `view_compendium` 144, `view_common` 62, `js` 54, `breadcrumbs`/`view_profile` 37 each, `controller` 28, `mapicontypes` 13, `view_creator` 12, `policy`/`spellmisstypes` 11 each, `spelldispeltype`/`view_dungeonroute` 8 each, `spellschools` 7, `services`/`spellimmunities`/`view_team` 6 each, `rules`/`spellcounters` 5 each, `leafletdraw`/`view_errors` 2 each) applies unchanged |
| Absent (need `localization:sync`, not this workflow) | 1 (`logic`) |

**Do not commit the sync side-effects (new empty stubs in `view_admin.php`, `controller.php`,
`js.php`, `view_common.php`, `view_home.php`, and 3 new files — `spellcounters.php`,
`spellimmunities.php`, `view_creator.php`) until you start translating for real.** Running step 0
to measure numbers, as this handover's author did, leaves the checkout modified; `git checkout --
lang/zh_CN_ai/...` and `rm` the new files if you want a clean start before your first real
translate/inject/commit cycle (they'll regenerate identically next time you run sync — the
workflow is idempotent on that step). This handover's author already did this cleanup — the
checkout is clean as of this commit.

## Register

**Leans formal 您, but check by hand before committing to it — the signal is weaker than most
prior locales'.** 256 pre-existing `您` hits against 39 `你` hits across the locale before this
pass (roughly 87:13, not the 400+:1 or 300+:1 ratios `ru_RU_ai`/`fr_FR_ai`/`ko_KR_ai` had for their
formal registers). Re-confirm with the same grep before writing anything, and spot-check a sample
of the `你` hits by hand — some may be pre-existing informal-register drift from the original
gpt-3.5 pass rather than a deliberate choice, in which case they don't override treating this
locale as formal; others may be idiomatic fixed phrases where `你` is simply correct regardless of
register (Chinese doesn't inflect a verb by formality the way European languages do, so the
`您`/`你` distinction is a lexical word-choice, not a grammatical mood — the false-positive rate on
a raw count might be different in kind from the pronoun greps used for European locales).

```bash
grep -o '您' lang/zh_CN_ai/*.php | wc -l
grep -o '你' lang/zh_CN_ai/*.php | wc -l
```

## Terms already established in this locale (checked before this handover)

Not checked yet — read them off the locale's own already-filled files (`affixes.php`, `enemies.php`,
`js.php`, `view_common.php`) before inventing a term, the same way every prior locale did, and
record what you find as a "Chinese (Simplified) glossary" section in `SKILL.md` when you finish,
matching the shape of the German/Spanish/French/Italian/Korean/Portuguese/Russian sections already
there.

## Repeat lessons worth re-reading before you start (not new — the last several passes have each
hit at least one of these)

- **Community jargon terms (`Pull`, `Pack`, `Add`, `Boss`, `Gauntlet`, `Skip`, `Trash`, `Combat
  Log`, `Affix`) stay literal English/Latin script, not transliterated or translated** —
  `ru_RU_ai`'s first draft transliterated `Gauntlet` to Cyrillic (`гонтлет`) across 5 `mapping.php`
  keys despite this rule being spelled out in SKILL.md's own jargon list; caught in self-review
  before the Codex pass even ran. For a logographic script like Chinese this risk looks different
  (there's no "transliteration" the way Cyrillic/Hangul allow it, but there is a temptation to
  translate the *meaning* of jargon terms that the community actually just uses in English/pinyin
  loanword form) — check what the community actually calls a "gauntlet"/"pull"/"pack" in Chinese
  M+ terminology before assuming a direct semantic translation is correct.
- **NPC/creature/boss names inside `mapping.php` prose stay English, always** — even when
  `npcs.php` has an official localization. `it_IT_ai`, `pt_BR_ai`, and `ru_RU_ai` (on one key,
  `Witherlings`) all made this exact mistake despite it being spelled out in this file; read that
  section of SKILL.md twice before starting the `mapping.php` batches. Before translating *any*
  named enemy/boss inside a `mapping.php` sentence, grep the same key across the eight already-done
  locales first (`grep -n '<key>' lang/{de_DE,es_ES,es_MX,fr_FR,it_IT,ko_KR,pt_BR,ru_RU}_ai/mapping.php`)
  — faster and more reliable than re-deriving the rule from first principles.
- **Catalogued spell/item names inside `mapping.php` prose use their official `spells.php`
  translation, not English and not an invented term** — the opposite rule from the one above, and
  easy to conflate with it. `Blazing Aegis`, `Burning Chain`, `Stolen Power` (all three confirmed
  present in `spells.php` with real localisations across `pt_BR_ai` and `ru_RU_ai`) read like
  generic item/ability names but are catalogued spells — grep `spells.php` for the exact English
  string before translating any spell/ability/buff name by hand, even one that looks like a plain
  object.
- **`algeth_ar_academy` appears twice** in `mapping.php` (`midnight` and `df` top-level keys, same
  real dungeon under two game-version keys) — `fr_FR_ai`, `ko_KR_ai`, and `pt_BR_ai` all had to
  match the `df` block against the already-translated `midnight` block, including a `+`/no-`+` and
  `%`-placement difference the two source strings genuinely disagree on in `en_US` itself. Do a
  `grep -c "'<dungeon_slug>' => \["  lang/en_US/mapping.php` for every dungeon before translating
  its block; a count of 2 means check the other block first.
- **Grep `spells.php`/`affixes.php`/`enemies.php`/`npcs.php` before translating any spell, affix,
  item, or dungeon-location name by hand**, even one that looks generic — see the `Blazing Aegis`
  example above, and `ru_RU_ai`'s `Ruby Overlook`/`Atrium of Sethraliss`/`The Heart of Rage`
  checkpoint names, all pulled from `dungeons.php`'s official localisation rather than invented.
- **When dispatching the Codex review, tell it explicitly that catalogued-spell-name translations
  are correct and NOT a violation of the "proper nouns stay English" rule** — `ru_RU_ai`'s review
  flagged 3 correctly-translated spell names (the ones above) for reverting to English, a false-
  positive category none of the prior locales' review prompts had triggered as heavily. Word the
  review prompt to distinguish the two rules explicitly: NPC/boss/creature names and a curated
  exact-tooltip-name list stay English always; catalogued spell/item names use their official
  translation, never English, never invented.
- **Gate every reviewer finding against the locale's own pre-existing usage, an official glossary
  file, or documented multi-locale precedent in this SKILL.md before applying it** —
  `pt_BR_ai`'s review flagged a word choice that matched a pre-existing sibling key;
  `ko_KR_ai`'s review had a large false-positive cluster from reviewing pre-existing,
  untouched content; `ru_RU_ai`'s review flagged ~20 correctly-translated object/location names
  (rejected against `es_MX_ai`/`it_IT_ai`/`pt_BR_ai` precedent) and all 9 Ulduar teleporter names
  (rejected against `fr_FR_ai`'s own already-settled precedent, recorded in SKILL.md). Also gate
  every finding against `git show <sha> -- lang/<locale>/<file> | grep '^+'` — a finding that
  points at a line this pass never touched is a false positive by construction.
