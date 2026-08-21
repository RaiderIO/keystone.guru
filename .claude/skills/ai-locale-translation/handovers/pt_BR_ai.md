# Handover: translating `pt_BR_ai` (#4165)

**Status: Done, 2026-08-21.** All 769 in-scope keys filled, gate green, `composer run analyse`
clean, `composer run fix` only realigning whitespace (`mapping.php`, `view_home.php`). Informal
*você* register throughout (121 pre-existing `você` hits, 0 `tu` hits across the locale before
this pass) — no register cleanup needed, all new keys written as *você*. Commits `627042c43`
(translation) and `118e952fe` (review fixes) on branch `4165-regenerate-ai-translations`.

**Codex review found 34 issues, 32 real.** Two recurring categories, both already documented
lessons in this file that this pass still tripped on:

1. **NPC/boss names inside `mapping.php` prose translated instead of kept English** —
   `Hackclaw's War-Band`, `Bracken Warscourge`, `Khajin the Unyielding`, `Watcher Irideus`,
   `the Seven`. All five have official `npcs.php` localizations and I used them, which is
   backwards — this is the exact it_IT/ko_KR mistake the "Proper nouns stay in English inside
   prose" rule exists to prevent. Confirmed against the four other already-done locales
   (`de_DE_ai`/`es_ES_ai`/`fr_FR_ai`/`ko_KR_ai` all kept these five English; only `it_IT_ai` — the
   locale that made this mistake originally — had translated them too, so it was not a reliable
   cross-check here).
2. **Official `spells.php`/`affixes.php` terms not read first** — `Enrage` invented as "Fúria"
   (a noun, "rage") instead of the official `spells.php` verb "Enfurecer"; `Awakened` invented as
   "Desperto" instead of the official `affixes.php` "Despertado".

Other real fixes: `view_compendium.php` translated "counter" (as in "this spell can be countered
by X") as `contra-atacar` (counter-*attack*, i.e. retaliate) across 7 keys — wrong verb, the
correct sense is `neutralizar`; "landing through :property" (bypassing an immunity) was
`acertando através de` (landing *through*) when `acertando apesar de` (landing *despite*) is the
correct sense in Portuguese; `Json` → `JSON` casing (2 keys); a gender/pronoun mismatch where a
singular "patrol" was referred back to with plural feminine pronouns; `pat` (WoW jargon for
"patrol") left untranslated when every other locale translated it and the rest of this diff
consistently uses `patrulha`; and a handful of naturalness/register polish items (a decimal-comma
fix, "tag the boss" mistranslated as "mark", a couple of reworded sentences).

**Two findings were checked and rejected** — the six `view_admin.php` "missing translations" are
in the deliberately excluded admin-UI file (0 keys from it were ever in this pass's work list);
`controller.php`'s "trabalhos" for `jobs` was flagged as a bad word choice, but it matches this
locale's own pre-existing sibling key (`thumbnail_regenerate_result`) two lines above it — the
suggested "tarefas" would have broken that established internal consistency instead of fixing
anything. Always gate a reviewer's finding against the locale's own pre-existing usage before
applying it, same lesson `fr_FR_ai`'s handover already recorded.

## Glossary established in this pass

| English | Portuguese | English | Portuguese |
|---|---|---|---|
| Dungeon | masmorra | Floor | andar |
| Route | rota | Pull | pull (não traduzido) |
| Pack | pacote (já dominante, 4+:1, antes desta passagem) | Enemy forces | forças inimigas (forma completa); abreviação **FI** introduzida para os pills/tooltips/snackbars de checkpoint |
| Affix | afixo (traduzido, já estabelecido em `affixes.php`) | Boss | chefe (já estabelecido) |
| Checkpoint | checkpoint (não traduzido, já estabelecido em `js.php`) | Compendium | Compêndio (traduzido - sem convenção prévia, seguiu o padrão de outros locales) |
| Weight (espessura de linha) | Peso (já estabelecido em `js.php`) | Icon | ícone (já estabelecido) |
| Season | Temporada (já estabelecido) | Teeming | Enxameante (lido de `affixes.php` - note que `js.php`'s `enemypack_teeming_label` já tinha "Pululante" pré-existente, uma inconsistência pré-existente não tocada por esta passagem) |
| Spell | feitiço (já estabelecido) | Crowd control | controle de multidão (já estabelecido em `affixes.php`) |
| Faction | facção (já estabelecido) | Drake (Oculus) | Draco (lido de `npcs.php`: Emerald/Amber/Ruby Drake -> Draco Esmeralda/Âmbar/Rubi) |
| Teleporter | Teletransportador (já estabelecido em `mapping.php`, preferido sobre "Teleportador" de `npcs.php` que é um nome próprio de NPC não relacionado) | Gong | Gong (não traduzido, consistente com todos os outros locales) |

Add a row here whenever a new locale forces a decision worth keeping.

## Proper nouns / exact tooltip names kept in English

Followed the established project rule strictly: NPC/creature/boss names inside `mapping.php`
prose stay English always (`Ramstein the Gorger`, `Loken`, `Arthas`, `Chromie`, `Festergut`,
`Rotface`, `Vengeful Fleshreapers`, `Sindragosa`, `Valithria Dreamwalker`, every
Firebrand/Shadowforge/Anvilrage/Razorfen/Spirestone/Blackhand mob-type name, etc.), even where
`npcs.php` has an official Portuguese localization. Title-Case exact in-game object/tooltip names
also stayed English, matching the `es_MX_ai`-established convention and its `ko_KR_ai`-corrected
scope (`Iron Gate`, `Activation Rune`, `The Black Anvil`, `Stairwell Door`, `Heavy Door`,
`Supply Room Door`, `Temple Door`, `Altar of the Deeps`, `Blackrock Altar`, `Treasure Chest`,
`Large Solid Chest`, and similar clickable-object labels throughout the classic dungeons).

Two items had official `spells.php` translations and were used instead of inventing new text —
`Blazing Aegis` -> "Égide Fulgurante" (ids 374812/374839/374842/375046/392666) and `Burning Chain`
-> "Corrente Ardente" (id 372824) — both in `mapping.map_icons.df.neltharus`. This is the
"grep spells.php before translating a spell/item/ability name" rule from SKILL.md paying off:
these read like generic item names but are actually catalogued spells with real localisations.

**`algeth_ar_academy` appears twice** (`midnight` and `df` top-level keys, same dungeon under two
different game-version keys — the `fr_FR_ai`/`ko_KR_ai` lesson). The `midnight` block was already
translated before this pass; the `df` block's 7 keys were matched to it exactly (including the
`+`/no-`+` and `%`-placement differences between the two source strings, which the two blocks
genuinely disagree on in `en_US` itself).

## Notes for the next locale

- No new terminology decisions were contested against another already-done locale beyond what is
  listed above — `pt_BR_ai` was translated from scratch (no `es_ES_ai`/`es_MX_ai`-style shared
  machine-translation origin to compare against for Portuguese).
- Watch `js.php`'s pre-existing `enemypack_teeming_label` => "Pululante" vs `affixes.php`'s
  authoritative `Enxameante` — a pre-existing drift this pass did not touch (not in scope, the
  workflow never overwrites non-empty values) but worth knowing if a future normalisation pass
  ever tackles it.
- `ru_RU_ai`, `zh_CN_ai`, `zh_TW_ai` remain after this one.
