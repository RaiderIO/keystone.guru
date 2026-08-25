# `de_DE_ai` — German style sheet

| | |
|---|---|
| Register | **Informal *du*** throughout, never *Sie* — matches the game client. |
| Register check | `grep -nE "\b(Sie\|Ihnen\|Ihre[nmrs]?\|Ihr)\b" lang/de_DE_ai/*.php \| grep -vE "spells\|validation\|datatables\|npcs\|dungeons\|view_admin"` — review hits by hand: sentence-initial *Sie* is often third-person *sie* ("**Sie** laufen zur Mitte der Brücke" = the guards do) and must not be touched. |
| Last full pass | 2026-08-24, #4299 — 47 catch-up keys (the #4277 upgrade-draft strings, the enemy-failure cluster verdicts and suggestions, and three new map-icon labels) on top of the 2026-08-21 #4165 catch-up (24 keys) and the 2026-08-20 pass (745 keys, 212 *Sie*→*du* normalised, 31 post-review rewrites). |
| Normalisation done | *Sie*→*du* register pass (212 keys) — the locale is clean; any *Sie* found now is a new slip, not legacy drift. |

## Glossary

| English | German | English | German |
|---|---|---|---|
| Dungeon | Dungeon | Floor | Etage |
| Route | Route | Pull | Pull |
| Enemy | Gegner | Enemy forces | Feindkräfte (abbrev. `FK`) |
| Pack | Pack | Patrol | Patrouille |
| Spell | Zauber | Schools | Schulen |
| Dispel type | Bannart | Miss types | Fehlschlagarten |
| Counters | Konter | Crowd control | Kontrolleffekte |
| Compendium | Kompendium | Characteristics | Eigenschaften |
| Raid marker | Schlachtzugsmarkierung | Sidebar | Seitenleiste |
| Season | Saison | Thumbnail | Vorschaubild |
| Creator | Creator | Checkpoint | Checkpoint |
| Affix | Affix (untranslated — unlike every Romance locale) | Boss | Boss |
| Prideful | Stolz (the enemy: Manifestation des Stolzes) | Inspiring | Inspirierend |
| Bolstering | Stärkend | Bursting | Explosiv |
| Sanguine | Blutig | Rogue Shroud | Verhüllender Nebel |
| Overpulled | zusätzlich gepullt | PUG | Randomgruppe |
| Ad-free giveaway | werbefreier Zugang | Locked (a chest/door) | verschlossen |
| Pledge Pin (Dragonflight) | Schwurbrosche des … Drachenschwarms (from `spells.php`) | Creator (route creator) | Creator (invariable plural: *die Creator*; `Routen-Creator`, `Empfohlene Creator`) |
| Vanish / Shadowmeld / Feign Death / Invisibility / Cloak of Shadows | Verschwinden / Schattenmimik / Totstellen / Unsichtbarkeit / Mantel der Schatten (`spells.php`; Invisibility's row there is empty) | Divine Shield / Ice Block / Aspect of the Turtle / Blessing of Protection / Blessing of Spellwarding / Anti-Magic Shell | Gottesschild / Eisblock / Aspekt der Schildkröte / Segen des Schutzes / Segen des Zauberschutzes / Antimagische Hülle |
| Upgrade draft (#4277) | Upgrade-Entwurf | Waystone (map icon type) | Wegstein |
| Spell Tuning (#4113/#4320) | Zauber-Anpassungen | Build (WoW client build) | Build (unübersetzt) |

## Locale-specific conventions

- Community jargon (`Pull`, `Pack`, `Add`, `Boss`, `Affix`, `Gauntlet`, `Skip`, `Trash`, `Combat Log`)
  stays English — German is the locale that follows the jargon default most completely.
- Legacy gpt-3.5 output is inconsistent in places (`Dungeon` vs `Verlies`, `Etage` vs `Stockwerk`);
  new keys follow the glossary above, existing values are left alone.

## Known pre-existing drift (not to be fixed without explicit authorisation)

- `Verlies`/`Stockwerk` leftovers in old machine output (see above).

## History

- 2026-08-20 #4165 — first hand-translated pass. Codex found 15 real issues, 4 of them invented
  game terms whose official name was sitting in the locale (`Anstecknadel` vs **Schwurbrosche**,
  `Stolzhaft` vs **Stolz**, `Schurkenhülle` vs **Verhüllender Nebel**, `Inspiration` vs
  **Inspirierend**); the reviewer was itself wrong twice (claimed *Platzend*/*Blutiges Sekret*;
  `affixes.php` says **Explosiv**/**Blutig**).
- 2026-08-21 #4165 — 24-key catch-up: the locale had never been re-synced after `view_creator.php`,
  `spellcounters.php` and `spellimmunities.php` landed in en_US (found by the first `status.sh` run).
  No review dispatched (11 of the 24 are official spell names read from `spells.php`).
- 2026-08-24 #4299 — 47-key catch-up ahead of the v15.19.0 release cut. `Upgrade-Entwurf` chosen
  to match `controller.php`'s existing *Upgrade*. Codex review: 10 findings, all applied — the
  important one being the unit. `:distance` is a yard value computed by our own code, and this
  pass had relabelled it as metres by following `mapping.php` prose; corrected to *Yards*,
  matching the locale's UI strings. Now rule 11 in the skill. Declined: the reviewer's claim of an
  official Blizzard name for `Waystone`, cited to a hotfix URL that cannot be checked from here
  and supported by no in-repo source — *Wegstein* stays a coined rendering.
