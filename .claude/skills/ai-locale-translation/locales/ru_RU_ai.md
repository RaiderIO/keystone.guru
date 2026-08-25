# `ru_RU_ai` — Russian style sheet

| | |
|---|---|
| Register | **Formal *Вы*** (capitalised *Вы/Вам/Ваш/Вас* in direct address) throughout — 435:1 before the pass, 476:1 after. Never *ты*. |
| Register check | `grep -rnE "\b(ты\|тебя\|тебе\|тобой\|твой\|твоя\|твои\|твоё)\b" lang/ru_RU_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — expect ≤1 (one legacy hit). |
| Last full pass | 2026-08-25, #4320 — 32 catch-up keys (the spell tuning Compendium strings from #4113/#4313), on top of the 2026-08-24, #4299 — 47 catch-up keys (the #4277 upgrade-draft strings, the enemy-failure cluster verdicts and suggestions, and three new map-icon labels) on top of the 2026-08-21 #4165 pass (769 keys, 6 post-review rewrites + 6 self-caught). |
| Normalisation done | none needed. |

## Glossary

| English | Russian | English | Russian |
|---|---|---|---|
| Dungeon | Подземелье | Route | Маршрут |
| Pack | Пак | Boss | Босс |
| Enemy forces | Силы врага; **СВ** abbreviation for checkpoint pills/tooltips/snackbars | Compendium | Компендиум |
| Checkpoint | Контрольная точка | Teeming | Кишащий (`affixes.php`) |
| Floor | Уровень | Weight (line thickness) | Толщина |
| Icon | Иконка | Sidebar | Боковая панель |
| Thumbnail | Эскиз (`js.php`) | Season | Сезон |
| Teleporter | Телепорт | Classification | Классификация |
| Aura | Аура | Debuff | Дебафф |
| Awakened | Пробужденный (no ё — matches `enemies.php`) | Cast time | Время произнесения (not *Время накладывания*) |
| Gauntlet | **gauntlet** — literal Latin script, never *гонтлет* | Blazing Aegis / Burning Chain / Stolen Power | Пылающая эгида / Горящая цепь / Похищенная сила (`spells.php`) |
| Upgrade draft (#4277) | черновик обновления | Waystone (map icon type) | Путевой камень |
| Spell Tuning (#4113/#4320) | Балансировка заклинаний | Build (WoW client build) | Сборка |

## Locale-specific conventions

- Community jargon stays in **Latin script** (`gauntlet`, `pull`, `pack`, …) — a transliteration
  into Cyrillic is the locale's characteristic slip; grep your own output for Cyrillic
  transliterations before review.
- Raw `%s` counts have no plural bucket: write count-invariant forms (`Просмотров: %s`) instead of
  `%s просмотров`, which is wrong for counts ending in 1–4.
- "Враги могут быть одним из: X, Y или Z" for enemy-variant lists is idiomatic; a reviewer will
  flag it as a number-agreement error — it is not.
- Ulduar teleporter destinations and generic object/location labels (`Cannon`, `Grounding Field`,
  `Slipstream`, `Cursed Spire of Ny'alotha`, `Cave of Mam'toth`, `Den of Sseratus`) are translated,
  following multi-locale precedent.

## Known pre-existing drift (not to be fixed without explicit authorisation)

- None recorded.

## History

- 2026-08-21 #4165 — 769 keys. Self-caught before review: `гонтлет` ×5 and `Witherlings` →
  *Иссохшие*. Codex: 97 findings, 6 real (casing, ё-spelling, cast time, one case ending, the
  declension bug); ~20 were correctly-translated object names flagged as "must stay English", ~20
  `view_admin.php` stubs, ~50 style preferences.
- 2026-08-24 #4299 — 47-key catch-up ahead of the v15.19.0 release cut. Self-caught before review:
  four new strings had Latin `pack` against the locale's established **Пак**, rewritten to the
  declined *пак/пака/паке*. Codex review: 12 findings, all applied. Declined: the reviewer's claim
  of an official Blizzard name for `Waystone`, cited to a hotfix URL that cannot be checked from
  here and supported by no in-repo source — *Путевой камень* stays a coined rendering.
- 2026-08-25 #4320 — 32-key catch-up for the spell tuning Compendium (#4113/#4313), found by the v15.20.0 release pre-flight. *Балансировка заклинаний* coined for Spell Tuning, *Сборка* for build (a real word, not a transliteration, so the Latin-script jargon rule does not apply). Codex review: 5 findings, 4 applied — `changed_to` → the gender-neutral *меняется на*, and `changed_spells` rewritten to the count-invariant *Изменено заклинаний: :count* in both buckets. Declined: *— число сравнённых сборок* for `builds compared`; the locale's own `count_suffix` siblings already use the genitive-plural form (*заклинаний каталогизировано*).
