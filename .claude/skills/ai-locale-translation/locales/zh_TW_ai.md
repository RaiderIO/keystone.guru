# `zh_TW_ai` — Chinese (Traditional) style sheet

| | |
|---|---|
| Register | **Formal 您** — 282:20 您:你 in the legacy machine output (measured 2026-08-21, excluded files left out). New keys: 您. |
| Register check | `grep -o '你' lang/zh_TW_ai/{js,view_*,controller,mapping,rules,policy,services}.php \| wc -l` — ~20 legacy hits expected. |
| Last full pass | **never** — skipped on Wotuu's instruction 2026-08-21 (locale not offered on the site). ~745 in-scope keys outstanding if it is ever enabled. |
| Normalisation done | none. |

## Glossary (from the legacy machine output — no hand pass has confirmed these)

| English | Chinese (Traditional) | Notes |
|---|---|---|
| Dungeon | 地城 (35) over 地下城 (12) | contested; follow the surrounding file |
| Route | 路線 | 261 hits, settled |
| Floor | 樓層 | |
| Affix | 詞綴 | |
| Boss | 首領 | |

Everything else: establish from the locale's own `affixes.php`/`spells.php`/`enemies.php`/`npcs.php`
(Blizzard's zh-TW client names differ from zh-CN — **never** convert `zh_CN_ai` values to
Traditional characters as a shortcut) and record the decisions here.

## Locale-specific conventions

- Not yet established. Start from `zh_CN_ai.md`'s conventions as hypotheses to verify, not rules.

## History

- 2026-08-21 #4165 — deliberately skipped. `status.sh --sync` still syncs it (stubs for
  `spellcounters.php`, `spellimmunities.php`, `view_creator.php` were added that day), so it reports
  its full backlog (769) on every status run — that line is expected until the locale is enabled.
