#!/usr/bin/env python3
"""Builds the translation work list for one locale.

A key is in scope when it is BOTH empty in the target locale AND present in
lang/texts_to_translate.json (the `translate:scan` output, which already excludes the
files that must never be hand-translated).

Usage: build_worklist.py <locale> <locale_dump.json> [out.json]
"""
import json
import sys
from collections import Counter

locale, dump_path = sys.argv[1], sys.argv[2]
out_path = sys.argv[3] if len(sys.argv) > 3 else f'worklist_{locale}.json'

# translate:scan also emits empty non-string buckets (e.g. 'logic': []) for files with no
# translatable strings - they are not keys, drop them.
scan = {k: v for k, v in json.load(open('lang/texts_to_translate.json', encoding='utf-8')).items()
        if isinstance(v, str)}
target = json.load(open(dump_path, encoding='utf-8'))

empty = [k for k, v in target.items() if v is None or v == '']
in_scan = [k for k in empty if k in scan]
skipped = [k for k in empty if k not in scan]
absent = [k for k in scan if k not in target]

json.dump({k: scan[k] for k in in_scan}, open(out_path, 'w', encoding='utf-8'),
          ensure_ascii=False, indent=4)

print(f'{locale}: {len(target)} keys, {len(empty)} empty')
print(f'  in scope   : {len(in_scan)} -> {out_path}')
print(f'  skipped    : {len(skipped)} (excluded files) {dict(Counter(k.split(".")[0] for k in skipped))}')
print(f'  absent     : {len(absent)} scan keys missing from the locale entirely (run localization:sync)')
for k in absent:
    print(f'      {k}')
print(f'  by file    : {dict(Counter(k.split(".")[0] for k in in_scan).most_common())}')
# Machine-readable line for status.sh: outstanding = empty in-scope keys + scan keys the locale
# lacks entirely (those become empty stubs as soon as localization:sync runs).
print(f'SUMMARY {locale} in_scope={len(in_scan)} absent={len(absent)} outstanding={len(in_scan) + len(absent)}')
