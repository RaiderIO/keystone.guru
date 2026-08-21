# Handover: translating `ko_KR_ai` (#4165)

**Status: Done, 2026-08-21.** All 769 in-scope keys filled, gate green, `composer run analyse`
clean, `composer run fix` only realigning whitespace. Formal 하십시오체 register (-습니다/-세요)
confirmed correct throughout before starting (336 `-습니다` hits, 80 `-세요` hits, near-zero
informal `-다`/`-이다` hits in the files this workflow touches) - no cleanup needed. Commits
`387dc5bfd` (translation) and `6fd4251e4` (review fixes) on branch
`4165-regenerate-ai-translations`, PR #4209.

**Codex review found ~90 issues. The overwhelming majority were NOT bugs — they were the it_IT
lesson, twice over:**

1. **Out-of-scope findings (~15).** This pass's work-list touched only `mapping.map_icons.{bfa,
   cata,checkpoints,classic,df,legion,mdt,mop,sl,tww,wotlk}`, never `mapping.map_icons.midnight` or
   `mapping.checkpoints.*` sections that were already filled before this pass started. Codex's
   review prompt asked it to review the whole locale's translation quality against `en_US`, not
   just the commit diff, so it flagged pre-existing content it assumed was part of this pass (e.g.
   `mapping.php:353,364-365,373,392-398,403-406,413-418,437,447,450` — all pre-existing
   `midnight.*` entries with completely different, already-correct wording). **Gate every finding
   against `git show <sha> -- lang/ko_KR_ai/<file> | grep '^+'` before touching anything** — this
   pass's false-positive-from-out-of-scope rate (~15 of ~90) was five times `fr_FR_ai`'s (3 of 18),
   because the review prompt didn't scope Codex to the diff explicitly enough. Next locale: tell
   Codex explicitly "only report on lines present in `git show <sha> -- lang/<locale>`", not just
   "review the commit".
2. **The it_IT NPC/boss-name mistake, from the reviewer's side this time (~70).** Almost every
   "official Korean name ignored" finding pointed at an NPC, creature, or boss name inside
   `mapping.php` prose (`Ramstein the Gorger`, `Vengeful Fleshreaper` — literally the same two
   names `it_IT_ai`'s handover names as the canonical example — plus ~65 more: `Gilnid`,
   `Rhahk'Zor`, `Arthas`, `Chromie`, `Loken`, `Brann`, `Festergut`, `Rotface`, `Sindragosa`,
   `Valithria Dreamwalker`, every Firebrand/Scarshield/Spirestone/Razorfen/Shadowforge/Anvilrage
   mob-type name, etc.). This is the **established, deliberate** project convention — SKILL.md's
   "Proper nouns stay in English inside prose" rule, confirmed unanimous across `de_DE_ai`,
   `es_ES_ai`, `es_MX_ai`, `fr_FR_ai` — not a defect. My review prompt tried to pre-empt this
   ("do NOT flag this as an error unless a specific name has an official Korean localization...")
   but that exception clause itself contradicts the actual rule (names stay English **even when**
   npcs.php has an official localization — that's the whole point of the it_IT lesson). **Next
   locale: drop the exception clause entirely** — tell the reviewer flatly that NPC/boss/creature
   names in `mapping.php` prose are never translated, full stop, regardless of npcs.php coverage.

**29 findings were real and applied** in `6fd4251e4`:

- **`df.algeth_ar_academy`'s 5 stat-buff labels vs the pre-existing `midnight.algeth_ar_academy`
  block — the `fr_FR_ai` lesson, repeated exactly.** Algeth'ar Academy appears twice in
  `mapping.php` under two different top-level game-version keys (`midnight` already had a filled
  block; `df` was this pass's empty one for the *same real dungeon*). I didn't check the sibling
  block before translating, so `versatility_increased` came out as an invented "다재다능" against
  the existing "유연성", `mastery_rating_increased` as "특화 수치" against "숙련도", etc. Fixed to
  match. **Do a `grep -c "'<dungeon_slug>' => \["  lang/en_US/mapping.php` for every dungeon you
  translate** before writing anything — a count of 2 means check the other block first.
- **5 Dragonflight Pledge Pin names** (`mapicontypes.php`) used an invented "서약 핀" instead of
  the official "충성의 핀" sitting in this locale's own `spells.php:4683-4687` — the exact
  "check `spells.php` before inventing a game term" mistake the skill's own German section warns
  about, for the skill's own worked example (`Pledge Pin`).
- **4 Title-Case object labels** (`Heavy Door`, `Stairwell Door`, `Supply Room Door`, `Sewer Gate`)
  were translated instead of kept English, breaking the `es_MX_ai`-established rule that exact
  in-game tooltip names stay English while lowercase/descriptive labels (its own cited example is
  `"workshop door"`) get translated. I had applied this rule inconsistently — `Iron Gate`,
  `Activation Rune`, `Temple Door` were correctly left English on instinct, but the four above
  were not, with no principled reason for the difference.
- **Mechanical fixes:** `Json` → `JSON` casing (2 keys); a redundant `#%d위` double rank-marker (1
  key); an invented `네루빔` for the officially-attested `네루비안` (`npcs.php:2593` etc., 1 key);
  9 `unlocks_after_*` keys ending abruptly in `잠금 해제`/`잠금 해제.` instead of the
  `잠금 해제됩니다.` every sibling key in the same batch used — a plain consistency slip within
  my own diff, not a convention question; 2 `주문` → `플레이어 주문` keys disambiguating from
  NPC-cast spells shown elsewhere in the same UI.

**Two clusters of findings were checked against `ko_KR_ai`'s own pre-existing (non-empty, filled
before this pass) values and correctly rejected** — matches the `es_MX_ai`/`fr_FR_ai` pattern of
"the locale beats the reviewer":

- `Affix` → `어픽스`: rejected, `affixes_label` was already `접사` before this pass (`js.php:93`).
- `Ad-free giveaway` → `광고 제거 혜택`: rejected, `ad_free_giveaway_label` was already
  `광고 없는 상태 제공` (`js.php:430`) before this pass — kept the matching `광고 없음 제공`
  phrasing this pass used for the new profile-page keys.
- `Weight` (arrow) → `굵기`: rejected, `weight_label`/`path_weight_label`/
  `enemypatrol_weight_label` all already used `무게` (3:1 majority) before this pass; only
  `brushline_weight_label` used `굵기`.
- `PUG-friendly` → `공팟 친화적`: rejected, `pug_friendly` was already `쉽게 참여 가능`
  (`view_home.php:38`) before this pass; matched it for the new `view_dungeonroute.php` key.
- Event-feed strings (`Casts :name`, `Added to database`, etc.) flagged for not ending in
  `-습니다`: rejected — these are deliberately terse nominal-style log-feed entries (an activity
  feed reads naturally as a list of short labels, not full sentences), not a register break.

One judgment call left open, not applied: `view_dungeonroute.php`'s `archetypes.title` (`"Title"` →
label `타이틀`) — Codex suggested `칭호` (the WoW term for an earned in-game title) on the theory
that "the route the top 0.5% use to push rating" implies an achievement title. Plausible but not
verifiable either way without more context on what this UI element actually shows; left as the
loanword `타이틀`, which is also a defensible standalone read of "Title" as a route category label.

Full finding table: Codex task `task-mt2p06g8-mdoky5` (session
`01a02374-733d-7e51-838f-0db243828ec5`).

## Numbers to expect for the next locale

```bash
docker compose exec -T app php artisan localization:sync en_US <locale>
docker compose exec -T app php artisan translate:scan \
    --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation
```

Same 769-key work-list as every locale since `es_ES_ai`'s re-sync — no new `en_US` keys have
landed. `ko_KR_ai` had 3,042 empty keys total (3,069 after re-sync), 745→769 in scope after
re-sync, 2,300 deliberately skipped, 1 absent (`logic` — an empty scan bucket, not a real key,
ignore as always).

## Register: formal 하십시오체 (-습니다/-세요)

```bash
grep -ohE "습니다|세요" lang/ko_KR_ai/*.php | grep -v -E "spells\.php|validation\.php|datatables\.php|npcs\.php|dungeons\.php|view_admin\.php" | sort | uniq -c
```

336 `-습니다` + 80 `-세요` hits before this pass, near-zero informal hits — this locale is formal
throughout, unlike German/Spanish/Italian's informal register and matching French's formality
(though French uses *vous* pronoun-based formality; Korean's is verb-ending-based, a different
mechanism entirely). All new keys in this pass were written in `-습니다`/`-세요` form.

## Korean glossary established in `ko_KR_ai` (this #4165 pass)

| English | Korean | Evidence / note |
|---|---|---|
| Dungeon | 던전 | Dominant pre-existing convention |
| Floor | 층 | `js.php` `dungeonfloorswitchmarker*` |
| Pull | 풀 (loanword, untranslated concept, transliterated script) | `view_common.php` `pullsettings.*`, pre-existing |
| Pack | 무리/묶음 (translated, situational) — `enemypack` UI label is 묶음 | `js.php:60,137` pre-existing |
| Affix | 접사 | `js.php:93` pre-existing — do not switch to 어픽스, see rejected findings above |
| Boss | 보스 (loanword) | `js.php`, `npcclassifications.php` pre-existing |
| Checkpoint | 체크포인트 (loanword) | No prior convention; chosen fresh this pass |
| Compendium | 도감 | No prior convention; chosen fresh this pass, used consistently across `view_compendium.php`/`breadcrumbs.php`/`view_common.php` |
| Enemy forces | 적 병력 (full form); **적병** abbreviation for checkpoint pills/tooltips/snackbars | Matches the FK/FE/FN abbreviation pattern other locales introduced for the same tight-UI contexts |
| Weight (line thickness) | 무게 (majority, 3:1 over 굵기) | Pre-existing `js.php` `weight_label`/`path_weight_label`/`enemypatrol_weight_label`; `brushline_weight_label` is the lone `굵기` outlier |
| Teeming | 번성 | `affixes.php:53-55` official |
| Nerubian | 네루비안 | `npcs.php:2593` etc. official — **not** 네루빔, a mistake this pass made and then fixed |
| Pledge Pin | 충성의 핀 | `spells.php:4683-4687` official — **not** 서약 핀, same category of mistake |
| Ad-free (giveaway) | 광고 없음 (제공/상태) | `js.php:430-434` pre-existing, kept over Codex's suggested 광고 제거 혜택 |
| PUG-friendly | 쉽게 참여 가능 | `view_home.php:38` pre-existing |

**Proper nouns (NPC/boss/creature names, and Title-Case exact in-game object names like `Iron
Gate`/`Activation Rune`/`The Black Anvil`/`Heavy Door`/`Stairwell Door`) stay in English/Latin
script inside `mapping.php` prose sentences**, matching the unanimous convention established by
`de_DE_ai`/`es_ES_ai`/`es_MX_ai`/`fr_FR_ai`/`it_IT_ai`. This is not a per-locale choice — see
SKILL.md's "Proper nouns stay in English inside prose" section. Lowercase/descriptive object labels
(`workshop door` → 작업장 문) still get translated; only the Title-Case exact-tooltip-name pattern
is the exception, and the whole point of this handover's review-triage section above is that
distinguishing the two is the single most error-prone judgment call in this workflow, in both
directions (translating a name that should stay English, or leaving an object label English that
should be translated).

## Mechanical notes for whoever does `pt_BR_ai`/`ru_RU_ai`/`zh_CN_ai`/`zh_TW_ai` next

- **Scope every Codex finding against the actual commit diff before triaging it**, not just
  against "does this sound right" — see the out-of-scope section above. Use
  `git show <sha> -- lang/<locale>/<file> | grep '^+'` per file, or dump the whole diff once and
  grep it per finding.
- **Check every dungeon that could plausibly appear twice under different game-version keys**
  (`grep -c "'<slug>' => \["  lang/en_US/mapping.php`) before translating it — this is now the
  second locale in a row (after `fr_FR_ai`) this exact mistake has been caught in.
- When writing the Codex review prompt, state the "proper nouns stay English in mapping.php prose"
  rule as an absolute, not an exception gated on npcs.php coverage — the gated version produced
  ~70 false positives here.

## The one thing that must be true at the end

```
OK - 769/769 work-list keys filled, 29 rewritten, 18918 keys intact, 15849 existing translations unchanged
```

plus `composer run analyse` clean, `composer run fix` leaving only whitespace realignment in
`lang/ko_KR_ai/**`, and the same `view_profile.view.route_count` (plural) /
`view_common.dungeonroute.poster.views` (`%s`) render check every prior pass did — both rendered
correctly (`게시된 경로 없음` / `1개의 게시된 경로` / `5개의 게시된 경로`, `조회수 42회`).
