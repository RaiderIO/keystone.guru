# `fr_FR_ai` — French style sheet

| | |
|---|---|
| Register | **Formal *vous*** throughout (*veuillez*, *cliquez*, *assurez-vous*) — the opposite of German/Spanish/Italian. Never *tu*. |
| Register check | `grep -rniE "\b(tu\|ton\|ta\|tes\|clique\|sélectionne)\b" lang/fr_FR_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — expect ~0 (1 legacy hit before the pass). Positive check: `\b(vous\|veuillez\|cliquez\|sélectionnez\|assurez-vous)\b` had 179 hits. |
| Last full pass | 2026-08-24, #4299 — 47 catch-up keys (the #4277 upgrade-draft strings, the enemy-failure cluster verdicts and suggestions, and three new map-icon labels) on top of the 2026-08-20 #4165 pass, PR #4209 (769 keys, 27 post-review rewrites). |
| Normalisation done | none needed. |

## Glossary

| English | French | English | French |
|---|---|---|---|
| Dungeon | donjon | Floor | étage |
| Route | itinéraire (181) leans over route (73) — check the surrounding file; `view_dungeonroute.php`, `view_profile.php` already chose | Pull / Pack | pull / pack (untranslated) |
| Affix | affixe (translated, 61:3) | Boss | boss (untranslated, 36:2) |
| Enemy forces | forces ennemies; **FE** abbreviation for checkpoint pills/tooltips/snackbars | Checkpoint | point de contrôle |
| Weight (line thickness) | Poids (4 sibling `*_weight_label` keys) | Icon | icône |
| Teeming | Foisonnant (`affixes.php`) | Drake (Oculus) | Drake (`npcs.php`: `Drake émeraude`/`ambre`/`rubis`), not *Dragonnet* |
| Vanish | Disparition (`spells.php`) | Shadowmeld | Camouflage dans l'ombre |
| Divine Shield | Bouclier divin | Anti-Magic Shell | Carapace anti-magie |
| Blessing of Spellwarding | Bénédiction de protection des sorts | | |
| Upgrade draft (#4277) | brouillon de mise à niveau | Waystone (map icon type) | Pierre de jalon |
| Spell Tuning (#4113/#4320) | Ajustements des sorts | Build (WoW client build) | Build (non traduit) |

## Locale-specific conventions

- Algeth'ar Academy stat-buff labels follow the pre-existing `midnight` block style:
  `+10% Soins reçus` (no space before `%`, no `de`, `Coup Critique` capitalised).
- Ulduar teleporter destinations: the four with an official `dungeons.php` name use it exactly
  (`L'antichambre d'Ulduar`, …); the five without one stay translated for consistency.

## Known pre-existing drift (not to be fixed without explicit authorisation)

- `route` vs `itinéraire` split in older machine output.

## History

- 2026-08-20 #4165 — 769 keys. Codex: 29 findings, 27 real — mostly spell/immunity names invented
  instead of read from `spells.php`, and the `df.algeth_ar_academy` block not matching the
  pre-existing `midnight` block (the origin of the "dungeon appears twice" rule). Two rejected
  (`Poids`, Ulduar names).
- 2026-08-24 #4299 — 47-key catch-up ahead of the v15.19.0 release cut. *route* used throughout
  rather than *itinéraire* — it leads in every file the new keys land in. Codex review: 13
  findings, all applied — the important one being the unit. `:distance` is a yard value computed
  by our own code, and this pass had relabelled it as metres by following `mapping.php` prose;
  corrected to *yards*, matching the locale's UI strings. Now rule 11 in the skill. Declined: the
  reviewer's claim of an official Blizzard name for `Waystone`, cited to a hotfix URL that cannot
  be checked from here and supported by no in-repo source — *Pierre de jalon* stays a coined
  rendering.
