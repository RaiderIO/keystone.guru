#!/usr/bin/env python3
"""Gate for a translation pass. Fails loudly rather than warning.

Asserts, comparing a before/after dump of the locale (see dump_locale.php):
  1. the key set is identical - nothing added, dropped or re-nested;
  2. every previously non-empty value is byte-identical - existing translations untouched;
  3. every work-list key is now non-empty, and no key outside the work list changed;
  4. every :placeholder, {n}/[n,*] plural segment and HTML tag of the source survives.

Usage: verify.py <before.json> <after.json> <worklist.json> [rewrites.json]

Passing a rewrites file (rewrite.py's input) permits exactly those keys to change from their
recorded "from" to their recorded "to" - every other existing value must still be untouched.
"""
import json
import re
import sys

PLACEHOLDER = re.compile(r':[a-zA-Z_][a-zA-Z0-9_]*')
TAG = re.compile(r'</?[a-zA-Z][^>]*>')
# Deliberately strict: no space/sign flags, so a literal '10% off' is not read as a token.
SPRINTF = re.compile(r'%%|%(?:[0-9]+\$)?[0-9]*(?:\.[0-9]+)?[bcdeEfFgGosuxX]')

before, after, worklist = (json.load(open(p, encoding='utf-8')) for p in sys.argv[1:4])
rewrites = json.load(open(sys.argv[4], encoding='utf-8')) if len(sys.argv) > 4 else {}
failures: list[str] = []


def fail(message: str) -> None:
    failures.append(message)


if before.keys() != after.keys():
    for key in set(before) ^ set(after):
        fail(f'key set changed: {key}')

for key in before.keys() & after.keys():
    was, now = before[key], after[key]
    if key in rewrites:
        if was != rewrites[key]['from']:
            fail(f'rewrite source drifted: {key}\n    expected: {rewrites[key]["from"]!r}\n    before  : {was!r}')
        elif now != rewrites[key]['to']:
            fail(f'rewrite not applied: {key}\n    expected: {rewrites[key]["to"]!r}\n    after   : {now!r}')
    elif was not in ('', None):
        if was != now:
            fail(f'existing translation modified: {key}\n    before: {was!r}\n    after : {now!r}')
    elif key in worklist:
        if now in ('', None):
            fail(f'work-list key still empty: {key}')
    elif now not in ('', None):
        fail(f'key outside the work list was filled: {key} -> {now!r}')

for key, source in worklist.items():
    translated = after.get(key)
    if translated in ('', None):
        continue
    if sorted(PLACEHOLDER.findall(source)) != sorted(PLACEHOLDER.findall(translated)):
        fail(f'placeholders differ: {key}\n    en: {source!r}\n    tr: {translated!r}')
    if sorted(SPRINTF.findall(source)) != sorted(SPRINTF.findall(translated)):
        fail(f'sprintf tokens differ: {key}\n    en: {source!r}\n    tr: {translated!r}')
    if source.count('|') != translated.count('|'):
        fail(f'plural segment count differs: {key}\n    en: {source!r}\n    tr: {translated!r}')
    if sorted(re.findall(r'\{\d+\}|\[\d+,[^\]]*\]', source)) != sorted(re.findall(r'\{\d+\}|\[\d+,[^\]]*\]', translated)):
        fail(f'plural range markers differ: {key}\n    en: {source!r}\n    tr: {translated!r}')
    if sorted(TAG.findall(source)) != sorted(TAG.findall(translated)):
        fail(f'HTML tags differ: {key}\n    en: {source!r}\n    tr: {translated!r}')

if failures:
    print(f'FAILED ({len(failures)})')
    for failure in failures:
        print(f'  {failure}')
    sys.exit(1)

filled = sum(1 for key in worklist if after.get(key) not in ('', None))
untouched = sum(1 for k, v in before.items() if v not in ('', None) and k not in rewrites)
print(f'OK - {filled}/{len(worklist)} work-list keys filled, {len(rewrites)} rewritten, '
      f'{len(after)} keys intact, {untouched} existing translations unchanged')
