#!/usr/bin/env python3
"""Pairs a {key: new_value} map with the locale's current values into rewrite.py's input.

Keeps the "from" side machine-generated, so an overwrite pass can never be guarded by a
mistyped copy of the old string.

Usage: make_rewrites.py <locale_dump.json> <new_values.json> <out.json>
"""
import json
import sys

dump = json.load(open(sys.argv[1], encoding='utf-8'))
new = json.load(open(sys.argv[2], encoding='utf-8'))

out, problems = {}, []
for key, value in new.items():
    if key not in dump:
        problems.append(f'{key}: not present in the locale')
    elif dump[key] == value:
        problems.append(f'{key}: new value is identical to the current one')
    elif dump[key] in ('', None):
        problems.append(f'{key}: currently empty - use inject.py, not rewrite.py')
    elif [line[:len(line) - len(line.lstrip())] for line in dump[key].split('\n')] != \
            [line[:len(line) - len(line.lstrip())] for line in value.split('\n')]:
        # Multi-line values carry meaningful indentation; a reworded line must not reflow it.
        problems.append(f'{key}: line count or leading whitespace changed')
    else:
        out[key] = {'from': dump[key], 'to': value}

json.dump(out, open(sys.argv[3], 'w', encoding='utf-8'), ensure_ascii=False, indent=4)
print(f'{len(out)} rewrites written to {sys.argv[3]}')
for problem in problems:
    print(f'  SKIPPED {problem}', file=sys.stderr)
sys.exit(1 if problems else 0)
