# `es_ES_ai` — Spanish (Spain) style sheet

| | |
|---|---|
| Register | **Informal *tú*** throughout, never *usted*. Plural you is *vosotros* (Spain). |
| Register check | `grep -nE "\b(usted\|Seleccione\|Haga\|Introduzca\|Pulse\|Elija\|Inténtelo\|Asegúrese)\b\|\b[Ss]u ruta\b" lang/es_ES_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — review by hand: `No se puede guardar la ruta` is impersonal, not formal address (the naive `puede` grep reports 165 hits, the real number was ~26). |
| Last full pass | 2026-08-24, #4299 — 47 catch-up keys (the #4277 upgrade-draft strings, the enemy-failure cluster verdicts and suggestions, and three new map-icon labels) on top of the 2026-08-20 #4165 pass (769 keys, 30 *usted*→*tú* normalised). |
| Normalisation done | *usted*→*tú* (30 keys, mostly `leafletdraw.php` imperatives and `js.php`/`view_common.php` possessives). |

## Glossary

| English | Spanish | English | Spanish |
|---|---|---|---|
| Dungeon | mazmorra | Floor | planta |
| Route | ruta | Pull | pull (untranslated) |
| Enemy | enemigo | Enemy forces | fuerzas enemigas (abbrev. `FE`) |
| Pack | pack (untranslated) | Patrol | patrulla |
| Spell | hechizo | Schools | escuelas |
| Dispel type | tipo de disipación | Miss types | tipos de fallo |
| Counters (noun) | contramedidas | Crowd control | control de masas |
| Compendium | compendio | Characteristics | características |
| Raid marker | marcador de banda | Season | temporada |
| Thumbnail | vista previa | Creator | creador |
| Checkpoint | punto de control | Affix | afijo (translated, 17:0 before the pass) |
| Boss | jefe | Prideful | Orgulloso (`enemies.php`) |
| Fel (prefix) | vil | Wyrm | vermis |
| Bolstering / Quaking / Teeming | Reforzante / Temblores / Abundante (`affixes.php` — **differ from es_MX_ai**) | | |
| Upgrade draft (#4277) | borrador de actualización | Waystone (map icon type) | Piedra guía |

## Locale-specific conventions

- Translate from the English, never from `es_MX_ai` (and vice versa). The two locales are ~85%
  byte-identical from the same machine-translation origin but disagree on Blizzard terms
  (Bolstering, Quaking, Teeming, floor = planta vs piso) and on *vosotros* vs *ustedes*.
- `mapping.php` Title-Case exact in-game object names (`Large Solid Chest`, `Iron Gate`,
  `The Black Anvil`, `Activation Rune`) stay English; descriptive lowercase labels are translated.

## Known pre-existing drift (not to be fixed without explicit authorisation)

- None recorded beyond the usual excluded-file *usted* forms (`validation.php`, `datatables.php`).

## History

- 2026-08-20 #4165 — 745 keys planned, 769 filled after re-sync pulled in `spellcounters.php`,
  `spellimmunities.php`, `view_creator.php` and one `controller` key. Register normalisation done in
  the same pass.
- 2026-08-24 #4299 — 47-key catch-up ahead of the v15.19.0 release cut. Floor stayed *planta*;
  *errores* for failure records, matching `clear_failures` in the same UI section. Codex review:
  11 findings, all applied. Declined: the reviewer's claim of an official Blizzard name for
  `Waystone`, cited to a hotfix URL that cannot be checked from here and supported by no in-repo
  source — *Piedra guía* stays a coined rendering.
