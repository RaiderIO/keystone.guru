#!/usr/bin/env python3
"""Replaces existing translation values in a locale's lang files, in place.

Unlike inject.py this DOES overwrite non-empty values, so every entry must state the exact
value it expects to find. A mismatch is refused, never guessed - that guard is what makes an
overwrite pass reviewable.

Input JSON: {"dotted.key": {"from": "<current value>", "to": "<new value>"}, ...}

Usage: rewrite.py <locale> <rewrites.json>
"""
import json
import re
import sys
from pathlib import Path

KEY = re.compile(r"^(?P<indent>\s*)'(?P<key>(?:[^'\\]|\\.)*)'(?P<pad>\s*)=> ")
OPEN = re.compile(r"^\s*'(?P<key>(?:[^'\\]|\\.)*)'\s*=> \[\s*$")
CLOSE = re.compile(r'^\s*\],?\s*$')


def single_quoted(value: str) -> str:
    return "'" + value.replace('\\', '\\\\').replace("'", "\\'") + "'"


def double_quoted(value: str) -> str:
    return '"' + value.replace('\\', '\\\\').replace('"', '\\"').replace('$', '\\$') + '"'


def main() -> int:
    locale, rewrites_path = sys.argv[1], sys.argv[2]
    rewrites = json.load(open(rewrites_path, encoding='utf-8'))

    by_file: dict[str, dict[str, dict[str, str]]] = {}
    for dotted, change in rewrites.items():
        file_name, _, rest = dotted.partition('.')
        by_file.setdefault(file_name, {})[rest] = change

    done, failed, matched = 0, [], set()
    for file_name, wanted in sorted(by_file.items()):
        path = Path('lang') / locale / f'{file_name}.php'
        text = path.read_text(encoding='utf-8')

        # Walk lines to find where each target key's value literal starts, then match the literal
        # against the expected value encoded both ways - no PHP string decoder needed.
        stack: list[str] = []
        offset, edits = 0, []
        for line in text.splitlines(keepends=True):
            key_match = KEY.match(line)
            if key_match and not OPEN.match(line):
                dotted = '.'.join(stack + [key_match.group('key')])
                if dotted in wanted:
                    start = offset + key_match.end()
                    expected = wanted[dotted]['from']
                    for literal in (single_quoted(expected), double_quoted(expected)):
                        if text.startswith(literal + ',', start):
                            edits.append((start, start + len(literal),
                                          single_quoted(wanted[dotted]['to'])))
                            matched.add(f'{file_name}.{dotted}')
                            break
                    else:
                        failed.append(f'{file_name}.{dotted}: current value is not the expected one')
            elif OPEN.match(line):
                stack.append(OPEN.match(line).group('key'))
            elif CLOSE.match(line) and stack:
                stack.pop()
            offset += len(line)

        for start, end, replacement in reversed(edits):
            text = text[:start] + replacement + text[end:]
            done += 1
        path.write_text(text, encoding='utf-8')

    for dotted in rewrites:
        if dotted not in matched and not any(dotted in failure for failure in failed):
            failed.append(f'{dotted}: key not found in the file')

    print(f'rewrote {done}/{len(rewrites)} values in {locale}')
    for failure in failed:
        print(f'  FAILED {failure}', file=sys.stderr)
    return 1 if failed else 0


if __name__ == '__main__':
    sys.exit(main())
