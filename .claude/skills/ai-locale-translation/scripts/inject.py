#!/usr/bin/env python3
"""Fills empty translation values in a locale's lang files, in place.

Only ever rewrites a line whose value is literally '' - every other byte of every file is
left untouched, so the diff is exactly the set of keys that were filled.

Usage: inject.py <locale> <translations.json>   (dotted keys, first segment = file name)
"""
import json
import re
import sys
from pathlib import Path

# An empty value is written as '' or "" - localization:sync picks the quote style from the
# en_US source, so a source string containing an apostrophe leaves behind a double-quoted stub.
LEAF = re.compile(r"^(?P<indent>\s*)'(?P<key>(?:[^'\\]|\\.)*)'(?P<pad>\s*)=> (?:''|\"\"),\s*$")
OPEN = re.compile(r"^\s*'(?P<key>(?:[^'\\]|\\.)*)'\s*=> \[\s*$")
CLOSE = re.compile(r'^\s*\],?\s*$')


def php_single_quoted(value: str) -> str:
    return "'" + value.replace('\\', '\\\\').replace("'", "\\'") + "'"


def main() -> int:
    locale, translations_path = sys.argv[1], sys.argv[2]
    translations = json.load(open(translations_path, encoding='utf-8'))

    by_file: dict[str, dict[str, str]] = {}
    for dotted, value in translations.items():
        file_name, _, rest = dotted.partition('.')
        by_file.setdefault(file_name, {})[rest] = value

    filled, unmatched = 0, dict(translations)
    for file_name, wanted in sorted(by_file.items()):
        path = Path('lang') / locale / f'{file_name}.php'
        if not path.exists():
            print(f'!! {path} does not exist - run localization:sync first', file=sys.stderr)
            continue

        stack: list[str] = []
        out = []
        for line in path.read_text(encoding='utf-8').splitlines(keepends=True):
            open_match = OPEN.match(line)
            leaf_match = LEAF.match(line)
            if leaf_match:
                dotted = '.'.join(stack + [leaf_match.group('key')])
                if dotted in wanted:
                    line = (f"{leaf_match.group('indent')}'{leaf_match.group('key')}'"
                            f"{leaf_match.group('pad')}=> {php_single_quoted(wanted[dotted])},\n")
                    filled += 1
                    unmatched.pop(f'{file_name}.{dotted}', None)
            elif open_match:
                stack.append(open_match.group('key'))
            elif CLOSE.match(line) and stack:
                stack.pop()
            out.append(line)

        path.write_text(''.join(out), encoding='utf-8')

    print(f'filled {filled}/{len(translations)} keys in {locale}')
    for key in unmatched:
        print(f'  UNMATCHED {key}', file=sys.stderr)
    return 1 if unmatched else 0


if __name__ == '__main__':
    sys.exit(main())
