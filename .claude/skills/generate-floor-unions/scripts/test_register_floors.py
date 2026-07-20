#!/usr/bin/env python3
"""
Golden regression tests for register_floors.py.

The registration pipeline is deterministic (SIFT + fixed-seed RANSAC on the
pinned OpenCV/numpy in Dockerfile), so each fixture dungeon's `match` output is
compared field-by-field against a committed golden file. This guards the match
tuning (own-art masking, off-image guard, edge-corr / review-band thresholds)
against silent regressions when the script is changed.

The fixture images live in the repo under scripts/test_fixtures/<name>/ so the
suite is self-contained, with the matching golden in scripts/test_expected/. They
are stored as 256-colour indexed PNGs (quantized, no dither) - ~40% smaller than
the source RGB with no measurable effect on the match (verified: placements move
<0.02 map units / edge_corr, no placement gained or lost). KSG_FLOOR_UNION_FIXTURES
can still point at another image root; a fixture whose images are missing is
skipped, not failed.

Run (inside the pinned Docker image, via the wrapper):
    .claude/skills/generate-floor-unions/scripts/test.sh
    KSG_FLOOR_UNION_FIXTURES=/other/root .../test.sh    # alternate image root
    .../test.sh --regenerate                             # rewrite goldens after
                                                         # an intentional change

The image root holds one sub-directory per fixture (see FIXTURES below).
"""

import json
import os
import sys
import tempfile
import unittest

import register_floors

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
EXPECTED_DIR = os.path.join(SCRIPT_DIR, 'test_expected')
FIXTURE_ROOT = os.environ.get('KSG_FLOOR_UNION_FIXTURES',
                              os.path.join(SCRIPT_DIR, 'test_fixtures'))

# name -> fixture image layout; images live in FIXTURE_ROOT/<dir>/. Calibration
# dungeons (Skyreach, Magister's Terrace) are the #3614 ground truth; the rest
# are the #3616 hardening set.
FIXTURES = {
    'skyreach': {
        'dir': 'skyreach',
        'facade': 'combined.png',
        'floors': ['cut_1.png', 'cut_2.png'],
    },
    'magisters_terrace': {
        'dir': 'magisters_terrace',
        'facade': 'combined.png',
        'floors': [f'cut_{i}.png' for i in range(1, 8)],
    },
    'ruby_life_pools': {
        'dir': 'ruby_life_pools',
        'facade': 'cut_3.png',
        'floors': ['cut_1.png', 'cut_2.png'],
    },
    'kings_rest': {
        'dir': 'kings_rest',
        'facade': 'cut_2.png',
        'floors': ['cut_1.png'],
    },
    'the_blinding_vale': {
        'dir': 'the_blinding_vale',
        'facade': 'cut_2.png',
        'floors': ['cut_1.png'],
    },
    'temple_of_sethraliss': {
        'dir': 'temple_of_sethraliss',
        'facade': 'cut_3.png',
        'floors': ['cut_1.png', 'cut_2.png'],
    },
    'voidscar_arena': {
        'dir': 'voidscar_arena',
        'facade': 'cut_4.png',
        'floors': ['cut_1.png', 'cut_2.png', 'cut_3.png'],
    },
    'murder_row': {
        'dir': 'murder_row',
        'facade': 'cut_4.png',
        'floors': ['cut_1.png', 'cut_2.png', 'cut_3.png'],
    },
    'altar_of_fangs': {
        'dir': 'altar_of_fangs',
        'facade': 'cut_4.png',
        'floors': ['cut_1.png', 'cut_2.png', 'cut_3.png'],
    },
    # Only the two active floors; cut_1 (Dreamer's Passage) is an inactive,
    # not-on-facade floor and is intentionally excluded.
    'den_of_nalorakk': {
        'dir': 'den_of_nalorakk',
        'facade': 'cut_4.png',
        'floors': ['cut_2.png', 'cut_3.png'],
    },
}

# Tolerances. The pipeline is bit-for-bit deterministic on the pinned image, but
# a small margin keeps the goldens robust across an OpenCV/numpy re-pin.
TOL = {'lat': 0.5, 'lng': 0.5, 'size': 1.0, 'rotation': 0.5, 'edge_corr': 0.02}
COMPARED_FIELDS = ('floor_image', 'instance', 'lat', 'lng', 'size', 'rotation',
                   'edge_corr', 'needs_review')


def fixture_paths(spec):
    """Absolute (facade, [floors]) for a fixture, or None if any image absent."""
    base = os.path.join(FIXTURE_ROOT, spec['dir'])
    facade = os.path.join(base, spec['facade'])
    floors = [os.path.join(base, name) for name in spec['floors']]
    if not os.path.exists(facade) or not all(os.path.exists(f) for f in floors):
        return None
    return facade, floors


def run_match(facade, floors):
    """Run cmd_match into a temp dir and return its placements list.

    Uses the real argparse parser (not a hand-built Namespace) so the goldens are
    generated with the script's actual argument defaults - a default change then
    shows up as a golden drift instead of being silently masked by a stale copy.
    """
    with tempfile.TemporaryDirectory() as out_dir:
        args = register_floors.build_parser().parse_args(
            ['match', '--facade', facade, '--out-dir', out_dir, *floors])
        register_floors.cmd_match(args)
        with open(os.path.join(out_dir, 'placements.json')) as f:
            return json.load(f)['placements']


def golden_path(name):
    return os.path.join(EXPECTED_DIR, f'{name}.json')


def by_floor_position(placements):
    """Group placements per floor_image, each list sorted by rounded position.

    A floor that legitimately places more than once (duplicated-art, e.g.
    Magister's Terrace cut_1) has its instances numbered in RANSAC iteration
    order, which is not stable across a benign input change (e.g. re-quantizing
    the fixture). Comparing by sorted position instead of by instance number
    keeps such a relabel from reading as a spurious regression.
    """
    groups = {}
    for placement in placements:
        groups.setdefault(placement['floor_image'], []).append(placement)
    for group in groups.values():
        group.sort(key=lambda p: (round(p['lat']), round(p['lng'])))
    return groups


class MatchGoldenTest(unittest.TestCase):
    def test_placements_match_golden(self):
        ran_any = False
        for name, spec in FIXTURES.items():
            with self.subTest(dungeon=name):
                paths = fixture_paths(spec)
                if paths is None:
                    self.skipTest(f'images absent under {FIXTURE_ROOT}')
                if not os.path.exists(golden_path(name)):
                    self.fail(f'no golden for {name}; run test.sh --regenerate')
                ran_any = True
                with open(golden_path(name)) as f:
                    expected = json.load(f)
                actual = run_match(*paths)

                got = by_floor_position(actual)
                want = by_floor_position(expected)
                self.assertEqual(
                    sorted(got), sorted(want),
                    f'{name}: floor set changed (a floor gained or lost all its '
                    f'placements)')
                for floor_image, want_group in want.items():
                    got_group = got[floor_image]
                    self.assertEqual(
                        len(got_group), len(want_group),
                        f'{name} {floor_image}: placement count changed '
                        f'(a floor gained/lost a placement, or a phantom '
                        f're-appeared)')
                    for got_p, want_p in zip(got_group, want_group):
                        for field in ('lat', 'lng', 'size', 'rotation',
                                      'edge_corr'):
                            self.assertAlmostEqual(
                                got_p[field], want_p[field], delta=TOL[field],
                                msg=f'{name} {floor_image}: {field} drifted '
                                    f'{got_p[field]} vs golden {want_p[field]}')
                        self.assertEqual(
                            bool(got_p.get('needs_review')),
                            bool(want_p.get('needs_review')),
                            f'{name} {floor_image}: needs_review flag changed')
        if not ran_any:
            self.skipTest(f'no fixture images found under {FIXTURE_ROOT}')

    def test_latlng_pixel_roundtrip(self):
        # Coordinate math is the load-bearing conversion; guard it without images.
        for x, y, w, h in [(0, 0, 1920, 1280), (960, 640, 1920, 1280),
                           (1002, 668, 1002, 668), (250, 400, 500, 333)]:
            lat, lng = register_floors.px_to_latlng(x, y, w, h)
            bx, by = register_floors.latlng_to_px(lat, lng, w, h)
            self.assertAlmostEqual(bx, x, delta=1e-6)
            self.assertAlmostEqual(by, y, delta=1e-6)

    def test_normalize_deg(self):
        cases = {(-180.0): 180.0, 190.0: -170.0, 360.0: 0.0, 45.0: 45.0,
                 -540.0: 180.0}
        for raw, expected in cases.items():
            self.assertAlmostEqual(register_floors.normalize_deg(raw), expected,
                                   delta=1e-9)


def regenerate():
    os.makedirs(EXPECTED_DIR, exist_ok=True)
    for name, spec in FIXTURES.items():
        paths = fixture_paths(spec)
        if paths is None:
            print(f'skip {name}: images absent under {FIXTURE_ROOT}')
            continue
        placements = run_match(*paths)
        trimmed = [{f: p.get(f) for f in COMPARED_FIELDS} for p in placements]
        with open(golden_path(name), 'w') as f:
            json.dump(trimmed, f, indent=2)
        print(f'wrote golden {name} ({len(trimmed)} placements)')


if __name__ == '__main__':
    if '--regenerate' in sys.argv:
        regenerate()
    else:
        unittest.main()
