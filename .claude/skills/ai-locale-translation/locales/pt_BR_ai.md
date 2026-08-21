# `pt_BR_ai` — Brazilian Portuguese style sheet

| | |
|---|---|
| Register | **Informal *você*** throughout (121 pre-existing hits, 0 *tu*). Never *tu* conjugations, never *o senhor/a senhora*. |
| Register check | `grep -rniE "\b(tu\|teu\|tua\|contigo\|senhor\|senhora)\b" lang/pt_BR_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — expect 0. |
| Last full pass | 2026-08-21, #4165 — 769 keys filled, 32 post-review rewrites. |
| Normalisation done | none needed. |

## Glossary

| English | Portuguese | English | Portuguese |
|---|---|---|---|
| Dungeon | masmorra | Floor | andar |
| Route | rota | Pull | pull (untranslated) |
| Pack | pacote (4+:1 established) | Enemy forces | forças inimigas; **FI** abbreviation for checkpoint pills/tooltips/snackbars |
| Affix | afixo | Boss | chefe |
| Checkpoint | checkpoint (untranslated, `js.php`) | Compendium | Compêndio |
| Weight (line thickness) | Peso | Icon | ícone |
| Season | Temporada | Spell | feitiço |
| Crowd control | controle de multidão | Faction | facção |
| Teeming | Enxameante (`affixes.php`) | Teleporter | Teletransportador (`mapping.php`; `npcs.php`'s *Teleportador* is an unrelated NPC) |
| Drake (Oculus) | Draco (Esmeralda/Âmbar/Rubi, `npcs.php`) | Gong | Gong |
| Enrage | Enfurecer (`spells.php`, not *Fúria*) | Awakened | Despertado (`affixes.php`, not *Desperto*) |
| counter (verb, "countered by") | neutralizar (**not** contra-atacar = retaliate) | landing through (an immunity) | acertando apesar de (not *através de*) |
| pat (patrol jargon) | patrulha | Blazing Aegis / Burning Chain | Égide Fulgurante / Corrente Ardente (`spells.php`) |
| jobs (queue) | trabalhos (matches sibling `thumbnail_regenerate_result`) | | |

## Locale-specific conventions

- Decimal comma, not decimal point, in prose numbers.
- "tag the boss" = marcar o chefe in the raid-marker sense, not "mark" in the annotation sense.
- Title-Case exact object names stay English (`Iron Gate`, `Activation Rune`, `The Black Anvil`,
  `Stairwell Door`, `Heavy Door`, `Supply Room Door`, `Temple Door`, `Altar of the Deeps`,
  `Blackrock Altar`, `Treasure Chest`, `Large Solid Chest`).

## Known pre-existing drift (not to be fixed without explicit authorisation)

- `js.php` `enemypack_teeming_label` = "Pululante" vs `affixes.php`'s authoritative "Enxameante".

## History

- 2026-08-21 #4165 — 769 keys. Codex: 34 findings, 32 real — five NPC names translated via
  `npcs.php` (the it_IT mistake again), `Enrage`/`Awakened` invented, `counter` as
  *contra-atacar* across 7 Compendium keys, plus polish. Rejected: `view_admin.php` stubs,
  `trabalhos`→`tarefas`.
