# `ru_RU_ai` — Russian style sheet

| | |
|---|---|
| Register | **Formal *Вы*** (capitalised *Вы/Вам/Ваш/Вас* in direct address) throughout — 435:1 before the pass, 476:1 after. Never *ты*. |
| Register check | `grep -rnE "\b(ты\|тебя\|тебе\|тобой\|твой\|твоя\|твои\|твоё)\b" lang/ru_RU_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — expect ≤1 (one legacy hit). |
| Last full pass | 2026-08-21, #4165 — 769 keys filled, 6 post-review rewrites + 6 self-caught. |
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
