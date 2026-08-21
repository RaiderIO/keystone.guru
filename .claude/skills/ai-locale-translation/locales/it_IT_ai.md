# `it_IT_ai` — Italian style sheet

| | |
|---|---|
| Register | **Informal *tu*** throughout (*tuo/tua*, *clicca*, *seleziona*), never formal *Lei*. |
| Register check | `grep -rniE "\b(Lei\|Suo\|Sua\|Suoi\|Sue\|clicchi\|selezioni)\b" lang/it_IT_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — review by hand: every one of the 7 hits after the pass was a third-person possessive ("il **suo** dungeon" = *its*), not formal address. Positive check: `\b(tu\|tuo\|tua\|tuoi\|tue\|clicca\|seleziona)\b` had 164→181 hits. |
| Last full pass | 2026-08-21, #4165 — 769 keys filled, ~90 post-review rewrites (75 of them reverting NPC names to English). |
| Normalisation done | none needed. |

## Glossary

| English | Italian | English | Italian |
|---|---|---|---|
| Dungeon | dungeon (untranslated; `breadcrumbs.php` admin keys say *Spedizioni*, ignore) | Floor | piano |
| Route | contested, lean **percorso** (41:11 in `view_common.php`, 36:8 in `js.php`) over *rotta* — follow the surrounding file | Pull | pull (untranslated) |
| Pack | **pacchetto** (translated — unlike French/Spanish; `js.php` `enemypack`, `mapping.php:367`) | Boss | Boss (untranslated) |
| Affix | affisso (translated) | Enemy forces | forze nemiche; **FN** abbreviation for checkpoint pills/tooltips/snackbars |
| Checkpoint | Checkpoint (untranslated loanword) | Weight (line thickness) | Peso |
| Icon | icona | Teeming | Abbondante (`affixes.php`) |
| Raid marker | marcatore di incursione (Raid → *incursione*, established) | Season | Stagione |

## Locale-specific conventions

- "Pack" must stay *pacchetto* — the one internal-consistency slip this locale made was drifting
  to *gruppo* in 6 keys.
- Title-Case exact object names (`Iron Gate`, `Activation Rune`, `The Black Anvil`) stay English.

## Known pre-existing drift (not to be fixed without explicit authorisation)

- `percorso` vs `rotta` split; `Spedizioni` in two admin breadcrumb keys.

## History

- 2026-08-21 #4165 — 769 keys. Codex: ~90 findings, almost all one systemic mistake — the pass had
  translated every NPC/creature/boss name in `mapping.php` prose using its official `npcs.php`
  name (`Vengeful Fleshreaper` → *Mieticarne Vendicativo*), against the unanimous convention of the
  four earlier locales. ~75 keys reverted. This is the origin of the **absolute** "NPC names stay
  English even when `npcs.php` has a localisation" rule in `SKILL.md`.
