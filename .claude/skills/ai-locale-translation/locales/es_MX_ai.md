# `es_MX_ai` — Spanish (Latin America) style sheet

| | |
|---|---|
| Register | **Informal *tú*** throughout. Plural you is *ustedes* (never *vosotros* — treat any hit as a leftover to fix). |
| Register check | `grep -rniE "\b(seleccione\|haga\|arrastre\|suelte\|continúe\|presione\|elija\|indique\|marque\|está seguro)\b" lang/es_MX_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — expect 0; also `grep -n vosotros lang/es_MX_ai/*.php` expects 0. |
| Last full pass | 2026-08-24, #4299 — 47 catch-up keys (the #4277 upgrade-draft strings, the enemy-failure cluster verdicts and suggestions, and three new map-icon labels) on top of the 2026-08-20 #4165 pass (769 keys, 4 post-review rewrites). |
| Normalisation done | none needed (already *tú*). |

## Glossary

| English | Spanish (MX) | Notes |
|---|---|---|
| Dungeon | **contested**: mazmorra (57) vs calabozo (18) | Not settled — read the surrounding file's lean (`grep -c "mazmorra\|calabozo" lang/es_MX_ai/<file>.php`) and follow it; never default to `mazmorra` the way `es_ES_ai` can |
| Floor | **piso** (not `planta`) | 21+ prior hits in `js.php`, 0 for `planta`; the first draft copied `es_ES_ai`'s `planta` and had to be corrected |
| Route | ruta | |
| Pull / Pack | pull / pack (untranslated) | pack was wrongly translated once; reverted |
| Affix | afijo (24:0) | |
| Boss | Jefe | established (`js.php` `kill_zone_enemy_row_has_boss_label`) |
| Enemy forces | fuerzas enemigas; **FE** abbreviation in checkpoint pills/tooltips | older `js.php` keys spell it out in full |
| Checkpoint | punto de control | |
| Weight (line thickness) | Peso | not `Grosor` — established in `js.php` `*_weight_label` keys |
| Icon | ícono (accented) | |
| Spell / Compendium / Crowd control / Raid marker / Season | hechizo / compendio / control de masas / marcador de banda / temporada | generic UI vocabulary, same as `es_ES_ai` |
| Wyrm / Fel (prefix) | vermis / vil | confirmed from `npcs.php` |
| Mole Machine | Máquina topo | `npcs.php` |
| Bolstering / Quaking / Teeming | **Fortalecedor / Tembloroso / Pululante** | `affixes.php` — differ from `es_ES_ai` |
| Prideful | Orgulloso | `enemies.php` |
| "unlocks a shortcut" | atajo | established phrasing |
| Upgrade draft (#4277) | borrador de actualización | new in #4299 |
| Waystone (map icon type) | Piedra guía | coined in #4299 — no in-repo source |
| Spell Tuning (#4113/#4320) | Ajustes de hechizos | coined this pass — no precedent in any locale |
| Build (WoW client build) | Build (sin traducir) | coined this pass |

## Locale-specific conventions

- Blizzard's es-MX client strings genuinely differ from es-ES — always read game terms from
  `lang/es_MX_ai/{affixes,spells,enemies,npcs}.php`, never from `es_ES_ai`.
- Title-Case exact in-game object/NPC tooltip names in classic-dungeon `mapping.php` notes
  (`Large Solid Chest`, `Iron Gate`, `The Black Anvil`, `Activation Rune`) stay English; lowercase
  descriptive labels (`workshop door`, `east entrance`) are translated.

## Known pre-existing drift (not to be fixed without explicit authorisation)

- The mazmorra/calabozo split.

## History

- 2026-08-20 #4165 — 769 keys. Codex: 4 real defects (`planta`→`piso`, `Awakened`/`Enrage` not read
  from the locale, an invented word `dracoformado`, `pack` translated); the rest were false
  positives from the reviewer not checking the locale's own established values (`Afijo`/`Jefe`/
  `atajo`).
- 2026-08-24 #4299 — 47-key catch-up ahead of the v15.19.0 release cut. Floor kept as *piso* (not
  `es_ES_ai`'s *planta*); *fallos* for failure records, matching this locale's own
  `clear_failures` — es_ES says *errores*, so the two locales genuinely differ here. Codex review:
  9 findings, all applied. Declined: the reviewer's claim of an official Blizzard name for
  `Waystone`, cited to a hotfix URL that cannot be checked from here and supported by no in-repo
  source — *Piedra guía* stays a coined rendering.
