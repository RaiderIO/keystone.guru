# Handover: translating `ru_RU_ai` (#4165)

Read `.claude/skills/ai-locale-translation/SKILL.md` first — it holds the whole procedure, the
scripts and the traps. This file only records what is **specific to `ru_RU_ai`**, measured
2026-08-21 right after `pt_BR_ai` was finished (PR #4209 — see `handovers/pt_BR_ai.md` for that
pass's notes, including its Codex-review section; `ru_RU_ai` is a different language with its own
register default and its own contested terms, so don't assume its answers carry over, but the
workflow lessons do — especially "NPC/creature/boss names inside `mapping.php` prose stay English,
always, even when `npcs.php` has an official localization for them." Both `it_IT_ai` and `pt_BR_ai`
made this exact mistake despite it being spelled out in this file; read that section twice before
starting the `mapping.php` batches).

**#4165 covers all ten `*_ai` locales.** `de_DE_ai`, `es_ES_ai`, `es_MX_ai`, `fr_FR_ai`,
`it_IT_ai`, `ko_KR_ai` and `pt_BR_ai` are finished (PR #4209 covers `fr_FR_ai` onward — the first
three landed directly on this branch before any PR existed). `ru_RU_ai` is next; `zh_CN_ai` and
`zh_TW_ai` remain after it. Work on the same branch, `4165-regenerate-ai-translations` — do not
create a new branch per locale. `sh/worktree.sh create 4165-regenerate-ai-translations
4165-regenerate-ai-translations` reuses the branch, same as every prior pass; a stack for it was
already up as of this handover (worktree at
`/home/wouterkoppenol/Git/private/keystone.guru-worktrees/4165-regenerate-ai-translations`) — check
`sh/worktree.sh list` before creating a duplicate.

## Numbers to expect

Re-run the sync + scan yourself before trusting any count below — these were measured once and
`en_US` keeps growing:

```bash
docker compose exec -T app php artisan localization:sync en_US ru_RU_ai
docker compose exec -T app php artisan translate:scan \
    --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation
```

| | |
|---|---|
| Empty total | 2868 |
| In scope | 769 — **identical set of English source strings** to every other locale in this pass, so `pt_BR_ai`'s batch plan (by file: `mapping` 305, `view_compendium` 144, `view_common` 62, `js` 54, `breadcrumbs`/`view_profile` 37 each, `controller` 28, `mapicontypes` 13, `view_creator` 12, `policy`/`spellmisstypes` 11 each, `spelldispeltype`/`view_dungeonroute` 8 each, `spellschools` 7, `services`/`spellimmunities`/`view_team` 6 each, `rules`/`spellcounters` 5 each, `leafletdraw`/`view_errors` 2 each) applies unchanged |
| Absent (need `localization:sync`, not this workflow) | 1 (`logic`) |

**Do not commit the sync side-effects (new empty stubs in `view_admin.php` and 3 new files —
`spellcounters.php`, `spellimmunities.php`, `view_creator.php`) until you start translating for
real.** Running step 0 to measure numbers, as this handover's author did, leaves the checkout
modified; `git checkout -- lang/ru_RU_ai/...` and `rm` the new files if you want a clean start
before your first real translate/inject/commit cycle (they'll regenerate identically next time you
run sync — the workflow is idempotent on that step).

## Register

**Formal `Вы`, not informal `ты`.** 435 pre-existing `Вы`/`вам`/`ваш`/`вас` hits against 1
`ты`-family hit across the locale before this pass — matches `fr_FR_ai`'s formal register, the
opposite of `de_DE_ai`/`es_ES_ai`/`es_MX_ai`/`it_IT_ai`'s informal *tu*/*du*/*você* and
`ko_KR_ai`'s 하십시오체. Confirm with the same grep before writing anything, and re-confirm the
count after the pass the way `it_IT_ai`/`ko_KR_ai` did (a handful of `Ваш`-family hits can be
false positives — third-person possessives or similar — check by hand, don't just trust the raw
count).

```bash
grep -orE '\b(Вы|вам|Ваш|ваш|Вас|вас)\b' lang/ru_RU_ai/*.php | wc -l
grep -orE '\b(ты|тебе|твой|тебя)\b' lang/ru_RU_ai/*.php | wc -l
```

## Terms already established in this locale (checked before this handover)

| English | Russian | Where |
|---|---|---|
| Dungeon | Подземелье | `view_common.php:27`, pre-existing |

That's the only pre-existing hit checked so far — read off the rest of the glossary
(`Pull`/`Pack`/`Affix`/`Boss`/`Route`/`Checkpoint`/`Compendium`/`Enemy forces`/`Teleporter`/etc.)
the same way every prior locale did: grep the locale's own already-filled files
(`affixes.php`, `enemies.php`, `js.php`, `view_common.php`) before inventing a term, and record
what you find as a "Russian glossary" section in SKILL.md when you finish, the same shape as the
German/Spanish/French/Italian/Korean/Portuguese sections already there.

## Repeat lessons worth re-reading before you start (not new — every recent pass has hit at least
one of these)

- **NPC/boss/creature names inside `mapping.php` prose stay English, always** — even when
  `npcs.php` has an official localization. Both `it_IT_ai` and `pt_BR_ai`'s first passes ignored
  this and had to revert dozens of keys after Codex review. Before translating *any* named
  enemy/boss inside a `mapping.php` sentence, grep the same key across the seven already-done
  locales first (`grep -n '<key>' lang/{de_DE,es_ES,es_MX,fr_FR,it_IT,ko_KR,pt_BR}_ai/mapping.php`)
  — faster and more reliable than re-deriving the rule from first principles.
- **Exact in-game object/tooltip names** (`Iron Gate`, `Activation Rune`, `The Black Anvil`,
  `Stairwell Door`, `Heavy Door`, `Supply Room Door`, `Temple Door`, and similar Title-Case
  clickable-object labels in the classic dungeons) also stay English — same grep-the-other-locales
  check applies.
- **`algeth_ar_academy` appears twice** in `mapping.php` (`midnight` and `df` top-level keys, same
  real dungeon under two game-version keys) — `fr_FR_ai`, `ko_KR_ai` and `pt_BR_ai` all had to
  match the `df` block against the already-translated `midnight` block. Do a
  `grep -c "'<dungeon_slug>' => \["  lang/en_US/mapping.php` for every dungeon before translating
  its block; a count of 2 means check the other block first.
- **Grep `spells.php`/`affixes.php`/`enemies.php` before translating any spell, affix, or item
  name by hand**, even one that looks generic — `pt_BR_ai` found `Blazing Aegis` and
  `Burning Chain` (which read like plain item pickups) both had real catalogued Russian names it
  would have been wrong to invent. The inverse mistake also happened in `pt_BR_ai`: `Enrage` and
  `Awakened` were invented instead of read from the official glossary files, and Codex caught both.
- **Gate every reviewer finding against the locale's own pre-existing usage before applying it** —
  `pt_BR_ai`'s Codex review flagged `controller.php`'s "trabalhos" (jobs) as an odd word choice,
  but it matched a pre-existing sibling key two lines above; applying the suggested fix would have
  broken established consistency instead of fixing anything. Also gate every finding against
  `git show <sha> -- lang/<locale>/<file> | grep '^+'` — `ko_KR_ai`'s review had a ~15-finding
  false-positive cluster from Codex reviewing pre-existing content this pass never touched.
