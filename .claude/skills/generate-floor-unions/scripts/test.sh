#!/usr/bin/env bash
# Run the register_floors.py golden regression tests in the pinned Docker image
# (same image run.sh uses, so tolerances hold). Fixtures live in-repo under
# scripts/test_fixtures/ (mounted via /skill), so no external image root is
# needed; set KSG_FLOOR_UNION_FIXTURES to point at a different one.
#
# Usage:
#   test.sh                                      # run tests (in-repo fixtures)
#   KSG_FLOOR_UNION_FIXTURES=/path test.sh       # alternate image root
#   test.sh --regenerate                         # rewrite goldens after a change
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
IMAGE=ksg-floor-union-registration

if ! docker image inspect "$IMAGE" >/dev/null 2>&1; then
    docker build -t "$IMAGE" "$SCRIPT_DIR" >&2
fi

# /skill is mounted read-write so --regenerate can rewrite test_expected/, and it
# also carries the in-repo test_fixtures/. --user keeps written files owned by the
# invoking user, not root. An override image root is mounted read-only if given.
mounts=(-v "$SCRIPT_DIR":/skill)
if [ -n "${KSG_FLOOR_UNION_FIXTURES:-}" ]; then
    mounts+=(-v "$KSG_FLOOR_UNION_FIXTURES":/fixtures:ro -e KSG_FLOOR_UNION_FIXTURES=/fixtures)
fi

docker run --rm --user "$(id -u):$(id -g)" "${mounts[@]}" \
    "$IMAGE" python /skill/test_register_floors.py "$@"
