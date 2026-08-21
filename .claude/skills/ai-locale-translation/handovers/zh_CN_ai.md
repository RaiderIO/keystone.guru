# Handover: translating `zh_CN_ai` (#4165)

**Status: Done, 2026-08-21.** All 769 in-scope keys filled, gate green. Formal *您* register
throughout (256 pre-existing `您` hits against 39 `你` hits before this pass, 317:39 after — the
new `你` hits are all pre-existing `js.php` tutorial-intro drift this pass never touched, see
below) — no register cleanup needed for the keys this pass filled. Commits `a2937a247`
(translation) and `743f3dc30` (review fixes) on branch `4165-regenerate-ai-translations`.

**Codex review flagged a large list of findings; only ~15 were real.** Most of the "must remain
English" category was checked against actual multi-locale precedent and rejected — full triage
below, because the reasoning matters more than the count for the next locale.

## Codex review triage

**Real fixes (11, applied via `rewrite.py`):**

| File:key | Issue | Fix |
|---|---|---|
| `mapping.php` `crusaders_square_enemies`, `hall_of_the_keepers_entrance`, `unlocks_after_infusion_chamber` | Three dungeon/floor location names (`Crusader's Square`, `Hall of the Keepers`, `Infusion Chamber`) all have real `dungeons.php` translations (`十字军广场`, `守护者大厅`, `注能室`) that should have been used instead of English — these are *not* NPC names, they're catalogued location names, a different rule | used the official `dungeons.php` translation |
| `js.php` `scheduling_label` | `排程` didn't match the `计划发布` term this same batch's sibling `scheduled_publish_*` keys already established | `计划发布` |
| `view_common.php` `heatmap_render_order_title`/`_behind` | "on top of / behind" is layer order, not vertical position; `在下方` reads as "below" spatially | reworded to `渲染在敌人上层还是下层` / `置于下层` |
| `view_profile.php` `patreon_status_for_admin` | `您好，我自己！` is a stiff literal rendering of the "Hello me!" joke | `嗨，是我！` keeps both the joke and the register break the English intends |
| `view_profile.php` `member_since` | `自 :date 起开始创作路线` — `起` and `开始` are redundant | `自 :date 起创作路线` |
| `mapping.php` `unferried_spirits_variant`, `pack_size_warning`, `stair_patrol` | **Self-caught, not from the review**: 3 of my own "pack" translations used `小队` (squad) instead of this locale's own already-established `包` (`js.php`'s pre-existing `enemypack` label) — an internal-consistency slip within my own commit, found while triaging the review | `小队` → `包` |

**Rejected: the "must remain English" category on df/wotlk object names (~20 findings) — checked
against actual precedent, not accepted on the reviewer's assertion alone.** The review claimed
`Decaying Cauldron`, `Cleansed Rot`, `Altar of Decay`, `Infused Mushroom`, `Qaleshi Goulash`,
`Stairwell Door`, `Dragonkiller Lance`, `Crumbling Rock Vein`, `Ghost Trap`,
`Queen Ansurek Shadecaster`, `Eternal Flame`, `Ancient Nerubian Device`, `Release Valves`,
`Empowering Blood Orb`, `Gas/Ooze Release Valve`, `Teleporter`, `Cursed Spire of Ny'alotha`,
`Abandoned Mole Machine`, `Treasure Chest`, `Viewing Room Door`, `Temple Door` must all stay
English as "exact object/item tooltip names". Grepping the same keys across the 8 already-done
locales showed this is **not a fixed rule — it's genuinely mixed, decided per-item by each prior
locale**:

- `Teleporter` is translated by **every single one** of the 5 locales that reached it
  (`Téléporteur`, `Teletransportador` ×2, `Телепорт`, `Teleporter`) — the review's claim it "must
  remain English" is flatly contradicted by unanimous precedent. Kept my `传送器`.
- `Cursed Spire of Ny'alotha` is translated by 6 of 8 locales (`de`, `it`, `ru`, `pt`, `es_ES`,
  `ko`); only `es_MX`/`fr_FR` left it English. Kept my `奈奥罗萨的诅咒尖塔`.
- `Decaying Cauldron`/`Altar of Decay`/`Infused Mushroom` lean the other way (5/7 and 2/3 kept
  English respectively) — genuinely closer to the reviewer's read, but still not universal (`it_IT`
  and `ko_KR` translated them). Left as translated; a case could be made either way and this isn't
  worth another round-trip over.
- `Dragonkiller Lance`, `Ghost Trap`, `Ancient Nerubian Device`, `Empowering Blood Orb`,
  `Gas/Ooze Release Valve` are roughly 50/50 across the 7 other locales.

**Lesson for the next locale:** when a reviewer asserts "X must stay English" for something that
isn't an NPC/boss/creature name or on the curated exact-tooltip list (`Iron Gate`,
`Activation Rune`, `The Black Anvil`, `Shadowforge Key`, `Supply Room Door`, `Large Solid Chest`
and similar), **grep the term across the other finished locales before accepting or rejecting** —
this whole category of map-icon object/pickup label has no single settled convention, every locale
before this one made its own per-item call, and a reviewer's confident-sounding blanket assertion
is not evidence of a rule that doesn't actually exist. `ru_RU_ai`'s handover already documented
this exact pattern for `Cannon`/`Grounding Field`/`Slipstream`/`Cursed Spire of Ny'alotha`/etc. —
this pass re-confirmed it holds for a fresh batch of `df`/`wotlk` object names too.

**Rejected: `view_admin.php` "untranslated additions" (3 findings).** That file is permanently
excluded from this workflow — `localization:sync` added fresh empty stubs there as a side effect,
same as every prior locale's pass. Never in the work list.

**Rejected: `js.php` "formal-register breaks" on `intro_*` tutorial keys (10 findings).** These
lines *do* appear as `+` in `git show a2937a247`, but only because filling other keys in the same
array shifted the `=>` alignment column for the whole file — the Chinese text itself is
byte-identical before/after (confirmed via `verify.py`, which passed clean on "every previously
non-empty value is byte-identical"). This is pre-existing gpt-3.5-era informal-register drift this
pass never touched, the same category the register-precheck at the top of this handover already
flagged as "some may be pre-existing informal-register drift... rather than a deliberate choice".
Fixing it would be a legitimate normalisation pass (like `de_DE_ai`'s *Sie→du* cleanup) but that
needs explicit authorization the way `de_DE_ai`/`es_ES_ai` got it — not something to fold into a
"fix my mistakes" review-response commit. **Flagging as a real opportunity for a future explicitly
authorized pass**, not applied here.

## Chinese (Simplified) glossary

See the "Chinese (Simplified) glossary" section in `SKILL.md` (searchable by that heading) — not
duplicated here to avoid drift between the two copies.

## Notes for the next locale

- `zh_TW_ai` is the last of the ten `*_ai` locales, but **Wotuu said to skip it** — it's not
  offered on the site right now (2026-08-21 instruction). #4165 can close once this handover and
  the SKILL.md table update land, unless a later session is told otherwise.
- The `git show <sha> | grep '^+'` diff-scoping check has a false-positive mode worth knowing
  about: a line can appear as `+` purely from column realignment (another key in the same array
  changed length, shifting everyone's `=>` padding) with byte-identical translated text. Don't
  trust "it's a `+` line" alone — check whether the *value* actually changed, which `verify.py`'s
  byte-identical assertion already proves either way.
- When a reviewer flags an object/pickup label (not NPC, not catalogued spell) as "must stay
  English", treat that as a hypothesis to check against the other locales' actual choices for the
  same key, not a rule to apply on the reviewer's confidence alone — see the triage above.
