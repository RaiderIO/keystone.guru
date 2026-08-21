#!/usr/bin/env python3
"""Proves a localization:sync run changed no existing translation.

Compares a dump taken before the sync with one taken after: every key that was non-empty before
must still exist with a byte-identical value. New keys (the stubs sync adds) are fine.

Usage: check_sync.py <pre_sync.json> <post_sync.json>
"""
import json
import sys

pre, post = (json.load(open(p, encoding='utf-8')) for p in sys.argv[1:3])
failures = []
for key, value in pre.items():
    if value in ('', None):
        continue
    if key not in post:
        failures.append(f'existing key dropped by sync: {key}')
    elif post[key] != value:
        failures.append(f'existing translation changed by sync: {key}\n    before: {value!r}\n    after : {post[key]!r}')

if failures:
    print(f'SYNC FAILED ({len(failures)})')
    for failure in failures:
        print(f'  {failure}')
    sys.exit(1)
added = len(post) - len(pre)
print(f'sync OK - {added} keys added, every existing translation unchanged')
