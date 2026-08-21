# Handover: translating `ru_RU_ai` (#4165)

**Status: Done, 2026-08-21.** All 769 in-scope keys filled, gate green, `composer run analyse`
clean, `composer run fix` only realigning whitespace. Formal *Вы* register throughout (435
pre-existing `Вы`/`вам`/`Ваш`/`вас` hits against 1 `ты`-family hit before this pass) — no
register cleanup needed, all new keys written as *Вы* (476:1 after the pass). Commits `89a7ce3ea`
(translation) and `81bc1967c` (review fixes) on branch `4165-regenerate-ai-translations`.

**Codex review found 97 candidate issues; 6 were real, plus one self-caught mistake found before
the review even ran.** This pass's real-finding rate was unusually low — most of the 97 were
false positives from the review prompt over-applying the proper-noun-stays-English rule. Full
triage below.

## The mistake caught before dispatching the review

**`Gauntlet` is on SKILL.md's own community-jargon list ("Pull, Pack, Add, Boss, Gauntlet, Skip,
Trash, Combat Log, Affix") and must stay literal English, not be transliterated to Cyrillic.** The
first draft wrote it as `гонтлет` across 5 `mapping.php` keys anyway (`kings_rest.gauntlet_gate`,
both `naxxramas*.gauntlet_room` keys, `brackenhide_hollow.witherlings_gauntlet`,
`halls_of_stone.brann_gauntlet`). Caught while re-reading the diff before dispatching Codex, fixed
with `rewrite.py` alongside the review fixes. Confirmed against `fr_FR_ai`/`es_MX_ai`/`it_IT_ai`,
which all kept it as literal `gauntlet`. One of the same 5 keys (`witherlings_gauntlet`) had also
translated the NPC name `Witherlings` (confirmed present in `npcs.php`) to `Иссохшие` — the
it_IT/ko_KR/pt_BR NPC-name-in-prose mistake, caught in the same self-review pass.

**Lesson for the next locale:** after finishing a batch, grep your own output for every
community-jargon word (`gauntlet`, `pull`, `pack`, `add`, `boss`, `skip`, `trash`, `affix`) in the
target script — not just the Latin alphabet, the target language's alphabet too — before
dispatching Codex. A transliteration slip like this is invisible to a spell-checker and easy for a
reviewer to miss if its own prompt is focused elsewhere.

## Codex review triage

**6 real fixes** (all applied via `rewrite.py`):

| File:line | Issue | Fix |
|---|---|---|
| `rules.php` (2 keys) | `Json`-строка casing | `Json` → `JSON` |
| `services.php:52` | `Пробуждённый` (ё) vs the pre-existing `enemies.php` term `Пробужденный` (no ё) | matched the established spelling |
| `js.php` + `view_compendium.php` (3 keys) | "Cast time" translated as `Время накладывания` (more associated with buff application) | standard MMO term `Время произнесения` |
| `js.php:enemy_forces_checkpoint_tooltip_spans_floors` | accusative-case slip, "охватывает этажей" | "охватывает этажи" |
| `view_common.php` (3 `.views` keys) | Russian declension bug: raw (non-Laravel-pluralized) `%s просмотров` is wrong for counts ending 1-4 (`21 просмотров` should be `21 просмотр`) — the source format has no plural bucket to add a fix to | reworded to the count-invariant `Просмотров: %s` |

**~20 false positives: correctly-translated object/location names flagged for reverting to
English.** Checked each against either an official `spells.php`/`dungeons.php`/`enemies.php`
source or the multi-locale precedent already in SKILL.md before rejecting:

- `Cannon`, `Grounding Field`, `Slipstream`, `Cursed Spire of Ny'alotha`, `Cave of Mam'toth`,
  `Den of Sseratus`, `Witherbark Prisoner`, the Stratholme postboxes — none of these are NPC/boss
  names or on the curated exact-tooltip-name list (`Iron Gate`, `Activation Rune`, `The Black
  Anvil`, `Stairwell Door`, `Heavy Door`, `Supply Room Door`, `Temple Door` and similar). They are
  generic descriptive object/location labels, which the project rule translates — and the
  majority of already-done locales (`it_IT_ai`/`ko_KR_ai`/`pt_BR_ai`/`de_DE_ai`) translated these
  same keys too.
- **All 9 Ulduar teleporter destination names** — `fr_FR_ai`'s own Codex review already settled
  this exact question and is recorded in this file: the 2 with an official `dungeons.php` source
  (`The Inner Sanctum of Ulduar`, `The Prison of Yogg-Saron`) and the 7 without one both stayed
  translated "for consistency", explicitly overriding a reviewer's "keep English" suggestion for
  the same reason it applies here.
- **3 catalogued spell/buff names** (`Blazing Aegis` → `Пылающая эгида`, `Burning Chain` →
  `Горящая цепь`, `Stolen Power` → `Похищенная сила`) — all three confirmed present in
  `spells.php` with an official translation before writing, matching the `pt_BR_ai` precedent for
  the first two. The reviewer's proper-noun rule doesn't carve out this exception, but SKILL.md's
  actual rule does: catalogued spell/item names use their official localisation, not English; only
  NPC/boss/creature names and the curated exact-tooltip list stay English.

**~20 false positives: `view_admin.php` "missing translations".** That file is permanently
excluded from this workflow (admin-only UI, English by convention) — `localization:sync` added
fresh empty stubs there as a side effect of this pass, which is expected and matches every prior
locale's commits. None of these were ever in the work list.

**~50 false positives: stylistic rewrite preferences with no actual defect**, mostly a repeated
complaint about "Враги могут быть одним из: X, Y или Z" (~20 near-identical enemy-variant
sentences) being flagged as an ungrammatical plural-subject/singular-predicate construction — it
is not; this is a completely idiomatic, common Russian phrasing pattern. A few one-off wording
preferences (`Очистить` vs `Сбросить`, `Мои избранные` vs `Избранное`) were also skipped as
judgment calls, not errors.

## Glossary established in this pass

See the "Russian glossary" section in `SKILL.md` (searchable by that heading) for the full table —
not duplicated here to avoid drift between the two copies.

## Notes for the next locale

- `zh_CN_ai` is next, `zh_TW_ai` after it. See `handovers/zh_CN_ai.md` for pre-measured numbers.
- Before dispatching a Codex review, re-read your own diff once for community-jargon words
  transliterated into the target alphabet by mistake (see "the mistake caught before dispatching
  the review" above) — cheaper to catch yourself than to rely on the reviewer catching it.
- When a reviewer flags a "proper noun should stay English" finding, check which of the two
  distinct rules actually applies before accepting it: (1) NPC/boss/creature names and the curated
  exact-tooltip-name list stay English, always; (2) catalogued spell/item names use their official
  `spells.php` translation, never English and never invented. A reviewer that doesn't distinguish
  these two will over-flag category (2) as violations of rule (1) — cross-check against
  `spells.php` before reverting anything back to English.
