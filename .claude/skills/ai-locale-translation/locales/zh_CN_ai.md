# `zh_CN_ai` — Chinese (Simplified) style sheet

| | |
|---|---|
| Register | **Formal 您** for direct address (256:39 before the pass, 317:39 after). New keys always 您, never 你. |
| Register check | `grep -o '你' lang/zh_CN_ai/{js,view_*,controller,mapping,rules,policy,services}.php \| wc -l` — 39 legacy hits expected (see drift); any increase is a slip. |
| Last full pass | 2026-08-25, #4320 — 32 catch-up keys (the spell tuning Compendium strings from #4113/#4313), on top of the 2026-08-24, #4299 — 47 catch-up keys (the #4277 upgrade-draft strings, the enemy-failure cluster verdicts and suggestions, and three new map-icon labels) on top of the 2026-08-21 #4165 pass (769 keys, 11 post-review rewrites). |
| Normalisation done | none. A 你→您 pass over the 39 legacy `js.php` `intro_*` tutorial keys would be legitimate but was **not** authorised. |

## Glossary

| English | Chinese | English | Chinese |
|---|---|---|---|
| Dungeon | 地下城 | Route | 路线 |
| Pull | 拉怪 | Pack | 包 (`enemypack` label; **not** 小队) |
| Boss | 首领 | Affix | 词缀 |
| Compendium | 图鉴 | Checkpoint | 检查点 |
| Enemy forces | 敌方部队; **敌部** abbreviation for checkpoint pills/tooltips/snackbars only | Weight (line thickness) | 权重 (`path_weight_label`/`enemypatrol_weight_label`; `brushline_weight_label`'s 粗细 is the outlier) |
| Faction | 阵营 | Crowd control | 控场 (`affixes.php`) |
| counter (verb, "countered by") | 反制 (`spells.php` 法术反制 — **not** 反击/反攻 = counter-attack) | Classification | 分类 |
| Aura | 光环 | Debuff | 减益效果 |
| Teeming | 繁盛 (`affixes.php`) | Combat Log | 战斗日志 (translated — locale precedent beats the jargon default) |
| Scheduled publish | 计划发布 (not 排程) | Crusader's Square / Hall of the Keepers / Infusion Chamber | 十字军广场 / 守护者大厅 / 注能室 (`dungeons.php`) |
| Teleporter | 传送器 | Cursed Spire of Ny'alotha | 奈奥罗萨的诅咒尖塔 |
| Upgrade draft (#4277) | 升级草稿 | Waystone (map icon type) | 路标石 |
| Spell Tuning (#4113/#4320) | 法术调整 | Build (WoW client build) | 版本 |

## Locale-specific conventions

- "on top of / behind" for heatmap layers is render order (渲染在敌人上层还是下层), not spatial
  position (not 在下方).
- Keep the tone of jokes ("Hello me!" → 嗨，是我！, not 您好，我自己！).
- Object/pickup labels in `mapping.php` were decided per item against multi-locale precedent
  (`Teleporter` translated like all other locales; `Decaying Cauldron`/`Altar of Decay` translated
  here though most locales kept them English — left as is).

## Known pre-existing drift (not to be fixed without explicit authorisation)

- 39 `你` hits, concentrated in `js.php` `intro_*` tutorial keys and one `affixes.php` description.
- `brushline_weight_label` = 粗细.

## History

- 2026-08-21 #4165 — 769 keys. Codex: long list, 11 real (3 `dungeons.php` location names,
  `scheduling_label` consistency, heatmap wording, 2 polish) + 3 self-caught 小队→包. Rejected:
  ~20 "must stay English" object labels (checked against precedent), `view_admin.php` stubs, 10
  "register breaks" that were realignment-only `+` lines with byte-identical text.
- 2026-08-24 #4299 — 47-key catch-up ahead of the v15.19.0 release cut. 包 for Pack, 楼层 for floor,
  全能 for Versatility, 码 for distance — all from the locale. No new 你 introduced. Codex review: 9
  findings, all applied. Declined: the reviewer's claim of an official Blizzard name for
  `Waystone`, cited to a hotfix URL that cannot be checked from here and supported by no in-repo
  source — *路标石* stays a coined rendering.
- 2026-08-25 #4320 — 32-key catch-up for the spell tuning Compendium (#4113/#4313), found by the v15.20.0 release pre-flight. *法术调整* coined for Spell Tuning, *版本* for build. Codex review: 2 findings, both applied — *伤害的削弱* (nerf applied to the quantity itself) → *降低*, and modifier order in *仅措辞改写*.
