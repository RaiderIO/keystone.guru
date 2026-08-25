# `pt_BR_ai` — Brazilian Portuguese style sheet

| | |
|---|---|
| Register | **Informal *você*** throughout (121 pre-existing hits, 0 *tu*). Never *tu* conjugations, never *o senhor/a senhora*. |
| Register check | `grep -rniE "\b(tu\|teu\|tua\|contigo\|senhor\|senhora)\b" lang/pt_BR_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — expect 0. |
| Last full pass | 2026-08-25, #4320 — 32 catch-up keys (the spell tuning Compendium strings from #4113/#4313), on top of the 2026-08-24, #4299 — 47 catch-up keys (the #4277 upgrade-draft strings, the enemy-failure cluster verdicts and suggestions, and three new map-icon labels) on top of the 2026-08-21 #4165 pass (769 keys, 32 post-review rewrites). |
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
| Upgrade draft (#4277) | rascunho de atualização | Waystone (map icon type) | Pedra de Marco |
| Spell Tuning (#4113/#4320) | Ajustes de Feitiços | Build (WoW client build) | Build (sem tradução) |

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
- 2026-08-24 #4299 — 47-key catch-up ahead of the v15.19.0 release cut. *pacote* for Pack and
  *jardas* for distance, both from the locale's existing usage. Codex review: 11 findings, all
  applied. Declined: the reviewer's claim of an official Blizzard name for `Waystone`, cited to a
  hotfix URL that cannot be checked from here and supported by no in-repo source — *Pedra de
  Marco* stays a coined rendering.
- 2026-08-25 #4320 — 32-key catch-up for the spell tuning Compendium (#4113/#4313), found by the v15.20.0 release pre-flight. *Ajustes de Feitiços* coined for Spell Tuning; *build* kept as the loanword and treated as **feminine** (*a build*, per Blizzard pt-BR), so its agreements are *comparada*/*comparadas*. Codex review: 4 findings, all applied. `changed_spells` written count-invariant (see es_ES_ai's note on the `*_ai` plural-bucket fallback).
