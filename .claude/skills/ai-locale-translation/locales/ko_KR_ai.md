# `ko_KR_ai` — Korean style sheet

| | |
|---|---|
| Register | **Formal 하십시오체** — verb endings `-습니다` / `-세요` throughout. Never plain `-다`/`-이다` sentence endings in user-facing prose. Terse nominal-style strings (activity-feed entries like `Casts :name`) are deliberately not full sentences and need no `-습니다`. |
| Register check | `grep -ohE "습니다\|세요" lang/ko_KR_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin" \| sort \| uniq -c` — positive signal (336 + 80 before the pass); spot-check new keys by eye for bare `-다` endings. |
| Last full pass | 2026-08-24, #4299 — 47 catch-up keys (the #4277 upgrade-draft strings, the enemy-failure cluster verdicts and suggestions, and three new map-icon labels) on top of the 2026-08-21 #4165 pass, PR #4209 (769 keys, 29 post-review rewrites). |
| Normalisation done | none needed. |

## Glossary

| English | Korean | Notes |
|---|---|---|
| Dungeon | 던전 | |
| Floor | 층 | `js.php` `dungeonfloorswitchmarker*` |
| Pull | 풀 | loanword, transliterated |
| Pack | 무리/묶음 (situational) | `enemypack` UI label is 묶음 (`js.php:60,137`) |
| Affix | 접사 | `js.php:93` — **not** 어픽스 |
| Boss | 보스 | loanword |
| Checkpoint | 체크포인트 | loanword |
| Compendium | 도감 | |
| Enemy forces | 적 병력; **적병** abbreviation for checkpoint pills/tooltips/snackbars | |
| Weight (line thickness) | 무게 (3:1 over 굵기; `brushline_weight_label` is the lone 굵기 outlier) | |
| Teeming | 번성 | `affixes.php` |
| Nerubian | 네루비안 | `npcs.php` — **not** 네루빔 |
| Pledge Pin | 충성의 핀 | `spells.php:4683-4687` — **not** 서약 핀 |
| Ad-free (giveaway) | 광고 없음 (제공/상태) | `js.php:430-434` |
| PUG-friendly | 쉽게 참여 가능 | `view_home.php:38` |
| Versatility / Mastery (Algeth'ar buffs) | 유연성 / 숙련도 | from the pre-existing `midnight.algeth_ar_academy` block — not 다재다능/특화 수치 |
| Spell (player-cast, Compendium) | 플레이어 주문 | disambiguates from NPC-cast 주문 in the same UI |
| "unlocks after …" | … 잠금 해제됩니다. | full verb ending, not a bare 잠금 해제 |
| Upgrade draft (#4277) | 업그레이드 초안 | new in #4299 |
| Waystone (map icon type) | 이정표 돌 | coined in #4299 — no in-repo source |
| Spell Tuning (#4113/#4320) | 주문 수치 조정 | coined this pass — no precedent in any locale |
| Build (WoW client build) | 빌드 | coined this pass |

## Locale-specific conventions

- NPC/boss/creature names stay in Latin script inside `mapping.php` prose. Title-Case exact object
  names (`Iron Gate`, `Heavy Door`, `Stairwell Door`, `Supply Room Door`, `Sewer Gate`,
  `Temple Door`) also stay English; lowercase descriptive labels (`workshop door` → 작업장 문) are
  translated.
- Rank markers: `#%d위` is redundant — use one marker.
- `view_dungeonroute.php` `archetypes.title` is the loanword 타이틀 (judgment call; 칭호 was
  suggested and not adopted).

## Known pre-existing drift (not to be fixed without explicit authorisation)

- `brushline_weight_label` = 굵기 while its siblings say 무게.

## History

- 2026-08-21 #4165 — 769 keys. Codex: ~90 findings, 29 real. ~70 false positives were the reviewer
  flagging NPC names that must stay English (the prompt's exception clause "unless npcs.php has an
  official name" caused it — now stated as absolute in the review prompt); ~15 pointed at lines
  outside the diff (prompt now scopes to the diff). Real: `df.algeth_ar_academy` not matched to the
  `midnight` block, 5 invented Pledge Pin names, 4 object labels translated, mechanical slips.
- 2026-08-24 #4299 — 47-key catch-up ahead of the v15.19.0 release cut. 묶음 for Pack (the
  `enemypack` label) and 유연성 for Versatility, both read from the locale. Codex review: 9 findings,
  all applied — the important one being the unit. `:distance` is a yard value computed by our own
  code, and this pass had relabelled it as metres by following `mapping.php` prose; corrected to
  *야드*, matching the locale's UI strings. Now rule 11 in the skill. Declined: the reviewer's claim
  of an official Blizzard name for `Waystone`, cited to a hotfix URL that cannot be checked from
  here and supported by no in-repo source — *이정표 돌* stays a coined rendering.
