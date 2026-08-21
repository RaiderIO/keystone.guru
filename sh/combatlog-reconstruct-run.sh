#!/usr/bin/env bash
#
# Joins the Raider.IO combat log segments of a single run - as downloaded by
# `combatlog:downloadruns --run=<id> --output-dir=<dir>` - back into the one full combat log that
# `combatlog:outputcombatlogroutejson` needs to build an API request body from.
#
# The segments are contiguous: the first opens with CHALLENGE_MODE_START and the last closes with
# CHALLENGE_MODE_END, and each one opens with its own RIO_LOG_VERSION header, which the parser
# recognises mid-file. So the only things that need care are the join order and the joins themselves.
#
#   sh/combatlog-reconstruct-run.sh storage/app/combatlogs/altar_of_fangs 1444862
#
# This is plain text wrangling on host paths under the bind-mounted storage/ - it deliberately does
# not go through `docker compose exec`, and the file it writes is visible to the container at the
# same relative path, which is what the artisan command that consumes it is handed.
#
set -euo pipefail

if [[ $# -lt 2 ]]; then
    echo "usage: $0 <segment dir> <run id> [output file]" >&2
    exit 1
fi

segmentDir="$1"
runId="$2"
outputFile="${3:-${segmentDir}/full_run_${runId}.txt}"

: > "$outputFile"

# Numeric sort, not lexicographic: a plain glob puts segment_10 before segment_2.
find "$segmentDir" -maxdepth 1 -name "run_${runId}_segment_*.txt" -printf '%f\n' \
    | sed -E "s/^run_${runId}_segment_([0-9]+)\.txt$/\1/" \
    | sort -n \
    | while read -r segmentId; do
        cat "${segmentDir}/run_${runId}_segment_${segmentId}.txt" >> "$outputFile"
        # A segment's last line has no trailing newline - without this the next segment's
        # RIO_LOG_VERSION header would be appended to it and both lines would fail to parse.
        printf '\n' >> "$outputFile"
    done

sed -i '/^$/d' "$outputFile"

echo "Wrote $(wc -l < "$outputFile") lines to $outputFile"
