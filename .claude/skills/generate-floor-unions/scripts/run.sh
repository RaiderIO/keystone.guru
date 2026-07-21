#!/usr/bin/env bash
# Run register_floors.py in a throwaway Docker container (no host Python, no
# app-image changes). The image is built once and cached locally.
#
# Usage: run.sh <workdir> <register_floors.py args...>
#   <workdir> is mounted at /work; refer to images/outputs relative to it.
#
# Example:
#   run.sh ~/maps/skyreach match --facade /work/combined.png \
#       --out-dir /work/out /work/cut_1.png /work/cut_2.png
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
IMAGE=ksg-floor-union-registration
WORKDIR="$(cd "$1" && pwd)"
shift

if ! docker image inspect "$IMAGE" >/dev/null 2>&1; then
    docker build -t "$IMAGE" "$SCRIPT_DIR" >&2
fi

# --user keeps output files owned by the invoking user, not root.
docker run --rm \
    --user "$(id -u):$(id -g)" \
    -v "$SCRIPT_DIR":/skill:ro \
    -v "$WORKDIR":/work \
    "$IMAGE" python /skill/register_floors.py "$@"
