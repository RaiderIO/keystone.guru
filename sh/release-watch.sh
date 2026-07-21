#!/usr/bin/env bash
#
# release-watch.sh — track and drive a full release (both repos) from one terminal.
#
# A release means: release-deploy.yml runs in this repo (build jobs, then a
# repository_dispatch to RaiderIO/keystoneguru-infra for staging, then again for
# production after a manual gate). This script polls both repos, correlates the
# infra runs to the release-deploy dispatch jobs, runs the staging/production
# verification checks, and can drive the production approval gate.
#
# See the `release-watch` skill for the full write-up.
#
# Usage:
#   sh/release-watch.sh [vX.Y.Z] [--watch-only] [--interval <seconds>] [--run-id <id>]
#
#   vX.Y.Z            Tag to watch. Default: the newest release-deploy.yml run's tag.
#   --watch-only       Never prompt for / perform production gate approval.
#   --interval <secs>  Poll interval in seconds. Default: 15.
#   --run-id <id>      Explicit release-deploy run id (skips tag -> run lookup).
#
# Re-entrant: every cycle re-derives state from the GitHub API. Safe to start, stop
# (Ctrl-C), and restart at any point in a release.
#
# Exit status: 0 once every job/infra-run/verification concluded successfully;
# 1 if the loop finished with something failed (a build/deploy job, an infra run,
# or a verification check that never passed within its timeout).
set -euo pipefail

REPO_MAIN="RaiderIO/keystone.guru"
REPO_INFRA="RaiderIO/keystoneguru-infra"
DEPLOY_STAGING_JOB="Deploy to Staging"
DEPLOY_PRODUCTION_JOB="Deploy to Production"
ASSETS_BASE_URL="https://assets.keystone.guru"
STAGING_URL="https://staging.keystone.guru/"
PRODUCTION_URL="https://keystone.guru/"
VERIFY_TIMEOUT_SECONDS=300
# How far back (before the dispatch job's completedAt) infra runs are still considered
# for correlation, to absorb clock skew between the two repos/APIs.
INFRA_CORRELATION_BUFFER_SECONDS=600

VERSION_ARG=""
RUN_ID_ARG=""
WATCH_ONLY=false
INTERVAL=15

# Associative array used only to de-duplicate the "EVENT:" log lines between cycles.
# Purely cosmetic — every decision in the script is re-derived fresh each cycle, so
# losing this state (e.g. on a restart) only causes a few events to be re-announced.
declare -A LOGGED_EVENTS=()

usage() {
    cat <<'EOF'
Usage: sh/release-watch.sh [vX.Y.Z] [--watch-only] [--interval <seconds>] [--run-id <id>]

  vX.Y.Z             Tag to watch. Default: the newest release-deploy.yml run's tag.
  --watch-only        Never prompt for / perform production gate approval.
  --interval <secs>   Poll interval in seconds. Default: 15.
  --run-id <id>       Explicit release-deploy run id (skips tag -> run lookup).
EOF
}

die() {
    echo "ERROR: $*" >&2
    exit 1
}

log() {
    printf '[%s] %s\n' "$(date -u +%H:%M:%S)" "$*"
}

# Print an "EVENT:" line the first time this exact key is seen in this process.
log_event_once() {
    local key="$1" message="$2"
    if [[ -z "${LOGGED_EVENTS[$key]+x}" ]]; then
        LOGGED_EVENTS[$key]=1
        log "EVENT: $message"
    fi
}

require_deps() {
    local cmd
    for cmd in gh jq curl date; do
        command -v "$cmd" >/dev/null 2>&1 || die "required dependency '$cmd' not found on PATH"
    done
}

parse_args() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --watch-only)
                WATCH_ONLY=true
                shift
                ;;
            --interval)
                [[ $# -ge 2 ]] || die "--interval requires a value"
                INTERVAL="$2"
                shift 2
                ;;
            --run-id)
                [[ $# -ge 2 ]] || die "--run-id requires a value"
                RUN_ID_ARG="$2"
                shift 2
                ;;
            -h|--help)
                usage
                exit 0
                ;;
            v*)
                VERSION_ARG="$1"
                shift
                ;;
            *)
                die "unrecognized argument: $1"
                ;;
        esac
    done

    [[ "$INTERVAL" =~ ^[0-9]+$ ]] && [[ "$INTERVAL" -gt 0 ]] || die "--interval must be a positive integer"
}

# --- time helpers ------------------------------------------------------------

epoch_of() {
    local ts="$1"
    if [[ -z "$ts" || "$ts" == "null" ]]; then
        return 1
    fi
    date -d "$ts" +%s 2>/dev/null
}

now_epoch() {
    date -u +%s
}

fmt_duration() {
    local secs="$1"
    if [[ "$secs" -lt 0 ]]; then
        secs=0
    fi
    printf '%dm%02ds' "$((secs / 60))" "$((secs % 60))"
}

# Elapsed time for a started_at/completed_at pair (completed_at empty -> still running).
elapsed_str() {
    local started="$1" completed="$2" start_e end_e
    start_e=$(epoch_of "$started") || { echo "-"; return; }
    if [[ -n "$completed" && "$completed" != "null" ]]; then
        end_e=$(epoch_of "$completed") || end_e=$(now_epoch)
    else
        end_e=$(now_epoch)
    fi
    fmt_duration "$((end_e - start_e))"
}

# --- target resolution (once, at startup) ------------------------------------

resolve_target() {
    if [[ -n "$RUN_ID_ARG" ]]; then
        RUN_ID="$RUN_ID_ARG"
        local head_branch
        head_branch=$(gh run view "$RUN_ID" --repo "$REPO_MAIN" --json headBranch --jq '.headBranch' 2>/dev/null) \
            || die "could not find release-deploy run $RUN_ID in $REPO_MAIN"
        if [[ -n "$VERSION_ARG" ]]; then
            VERSION="$VERSION_ARG"
            if [[ "$VERSION" != "$head_branch" ]]; then
                log "WARN: given version $VERSION does not match run $RUN_ID's tag ($head_branch); using $VERSION anyway"
            fi
        else
            VERSION="$head_branch"
        fi
        return
    fi

    if [[ -n "$VERSION_ARG" ]]; then
        VERSION="$VERSION_ARG"
        RUN_ID=$(gh run list --repo "$REPO_MAIN" --workflow=release-deploy.yml \
            --json databaseId,headBranch --limit 50 \
            --jq "[.[] | select(.headBranch == \"$VERSION\")][0].databaseId // empty")
        [[ -n "$RUN_ID" ]] || die "no release-deploy run found for tag $VERSION"
        return
    fi

    local pair
    pair=$(gh run list --repo "$REPO_MAIN" --workflow=release-deploy.yml --limit 1 \
        --json databaseId,headBranch --jq '.[0] | "\(.databaseId) \(.headBranch)"' 2>/dev/null)
    [[ -n "$pair" ]] || die "no release-deploy runs found in $REPO_MAIN"
    RUN_ID="${pair%% *}"
    VERSION="${pair#* }"
}

# --- release-deploy run --------------------------------------------------------

# Fetches the release-deploy run's url + jobs into globals RELEASE_RUN_URL / RELEASE_JOBS_JSON.
# Returns 1 (without dying) on transient API failure so the main loop can retry.
fetch_release_run() {
    local output
    if ! output=$(gh run view "$RUN_ID" --repo "$REPO_MAIN" --json url,jobs 2>&1); then
        log "WARN: failed to fetch release-deploy run $RUN_ID: $output"
        return 1
    fi
    RELEASE_RUN_URL=$(jq -r '.url' <<<"$output")
    RELEASE_JOBS_JSON=$(jq -c '.jobs' <<<"$output")
    return 0
}

job_field() {
    local job_json="$1" field="$2"
    jq -r --arg f "$field" '.[$f] // empty' <<<"$job_json"
}

is_job_failed() {
    local conclusion="$1"
    [[ "$conclusion" == "failure" || "$conclusion" == "cancelled" || "$conclusion" == "timed_out" || "$conclusion" == "action_required" ]]
}

print_job_line() {
    local job_json="$1" indent="${2:-  }"
    local name status conclusion started completed url label
    name=$(job_field "$job_json" name)
    status=$(job_field "$job_json" status)
    conclusion=$(job_field "$job_json" conclusion)
    started=$(job_field "$job_json" startedAt)
    completed=$(job_field "$job_json" completedAt)
    url=$(job_field "$job_json" url)

    if [[ "$status" == "completed" ]]; then
        label="$conclusion"
    else
        label="$status"
    fi

    printf '%s[%-9s] %-34s %s\n' "$indent" "$label" "$name" "$(elapsed_str "$started" "$completed")"

    log_event_once "job:$name:$status:$conclusion" "job '$name' -> $label"

    if [[ "$status" == "completed" ]] && is_job_failed "$conclusion"; then
        printf '%sFAILED: %s -> %s\n' "$indent" "$name" "$url"
    fi
}

# --- infra run correlation & tracking -----------------------------------------

# Finds the keystoneguru-infra Deploy run for $stage (deploy-staging|deploy-production)
# and $version, created at/after $since_epoch (minus a clock-skew buffer). Prefers the
# LATEST matching run (a stage can be re-dispatched after an earlier infra failure).
#
# The infra workflow's displayTitle comes in two forms: the bare event type
# ("deploy-staging", current behaviour) or "Deploy deploy-staging (vX.Y.Z)" once the
# infra run-name PR lands — the stage filter accepts both (note "deploy-staging" and
# "deploy-production" can never falsely substring-match each other). The version is
# confirmed via the displayTitle when it already carries a "(vX.Y.Z)" suffix (no per-run
# job fetch needed), otherwise via the job name "Deploy <stage> (vX.Y.Z)"; a same-stage
# run for a DIFFERENT version never matches.
#
# Echoes the run id on success; returns 1 and echoes nothing if none is found yet.
find_infra_run() {
    local stage="$1" version="$2" since_epoch="$3"
    local list
    if ! list=$(gh run list --repo "$REPO_INFRA" --workflow=Deploy --event=repository_dispatch \
        --json databaseId,createdAt,displayTitle --limit 30 2>&1); then
        log "WARN: failed to list $REPO_INFRA runs: $list"
        return 1
    fi

    local threshold=$((since_epoch - INFRA_CORRELATION_BUFFER_SECONDS))
    local ids
    ids=$(jq -r --arg stage "$stage" \
        '[.[] | select(.displayTitle == $stage or (.displayTitle | contains($stage)))] | .[].databaseId' <<<"$list")

    local id created created_e title job_name
    for id in $ids; do
        created=$(jq -r --argjson id "$id" '.[] | select(.databaseId == $id) | .createdAt' <<<"$list")
        created_e=$(epoch_of "$created") || continue
        if [[ "$created_e" -lt "$threshold" ]]; then
            # `list` is newest-first; everything after this is older still, stop scanning.
            break
        fi
        title=$(jq -r --argjson id "$id" '.[] | select(.databaseId == $id) | .displayTitle' <<<"$list")
        if [[ "$title" == *"($version)"* ]]; then
            # run-name form already confirms the version; skip the per-run job fetch.
            echo "$id"
            return 0
        fi
        if [[ "$title" != "$stage" ]]; then
            # run-name form carrying a different version — not our run.
            continue
        fi
        # Bare-displayTitle form: confirm the version via the job name.
        job_name=$(gh run view "$id" --repo "$REPO_INFRA" --json jobs --jq '.jobs[0].name // empty' 2>/dev/null) || continue
        if [[ "$job_name" == *"($version)"* ]]; then
            echo "$id"
            return 0
        fi
    done
    return 1
}

# Prints the current state of an infra run: status/conclusion, elapsed, and (while
# in progress) the currently running step. Sets INFRA_STATUS / INFRA_CONCLUSION /
# INFRA_COMPLETED_AT globals for the caller.
print_infra_run() {
    local run_id="$1" label="$2" indent="${3:-  }"
    local output
    if ! output=$(gh run view "$run_id" --repo "$REPO_INFRA" --json url,status,conclusion,createdAt,updatedAt,jobs 2>&1); then
        log "WARN: failed to fetch infra run $run_id: $output"
        INFRA_STATUS=""
        INFRA_CONCLUSION=""
        INFRA_COMPLETED_AT=""
        return 1
    fi

    local url status conclusion created updated current_step display
    url=$(jq -r '.url' <<<"$output")
    status=$(jq -r '.status' <<<"$output")
    conclusion=$(jq -r '.conclusion // empty' <<<"$output")
    created=$(jq -r '.createdAt' <<<"$output")
    updated=$(jq -r '.updatedAt' <<<"$output")

    if [[ "$status" == "completed" ]]; then
        display="$conclusion"
        INFRA_COMPLETED_AT="$updated"
    else
        display="$status"
        INFRA_COMPLETED_AT=""
        current_step=$(jq -r '.jobs[0].steps[]? | select(.status == "in_progress") | .name' <<<"$output" | head -1)
        [[ -n "$current_step" ]] && display="$display ($current_step)"
    fi

    printf '%s%s infra run: %s [%s] %s\n' "$indent" "$label" "$url" "$display" "$(elapsed_str "$created" "$INFRA_COMPLETED_AT")"

    log_event_once "infra:$run_id:$status:$conclusion" "infra $label run $run_id -> $display"

    if [[ "$status" == "completed" && "$conclusion" != "success" ]]; then
        printf '%sFAILED: infra %s run -> %s\n' "$indent" "$label" "$url"
    fi

    INFRA_STATUS="$status"
    INFRA_CONCLUSION="$conclusion"
    return 0
}

# --- verification --------------------------------------------------------------

check_http_200() {
    local url="$1" code
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 -I "$url" 2>/dev/null) || code="000"
    [[ "$code" == "200" ]]
}

check_html_flip() {
    local url="$1" version="$2" seen
    seen=$(curl -s --max-time 10 "$url" 2>/dev/null | grep -oE "compiled/[^/\"']+/" | head -1) || seen=""
    [[ "$seen" == "compiled/${version}/" ]]
}

# Runs the 4 verification checks for $stage (staging|production) and prints PASS/FAIL
# for each. Sets VERIFY_ALL_PASS=true/false. $infra_completed_at (may be empty) is used
# to decide whether the timeout has elapsed for checks that are still failing.
verify_stage() {
    local stage="$1" base_url="$2" infra_completed_at="$3" indent="${4:-    }"
    local js_url css_url mapctx_url
    js_url="${ASSETS_BASE_URL}/compiled/${VERSION}/js/app-${VERSION}.js"
    css_url="${ASSETS_BASE_URL}/compiled/${VERSION}/css/app-${VERSION}.css"
    mapctx_url="${ASSETS_BASE_URL}/compiled/${VERSION}/mapcontext/static/en_US.js"

    local deadline_e=""
    if [[ -n "$infra_completed_at" ]]; then
        local completed_e
        completed_e=$(epoch_of "$infra_completed_at") && deadline_e=$((completed_e + VERIFY_TIMEOUT_SECONDS))
    fi

    VERIFY_ALL_PASS=true
    local name_url name url result

    for name_url in "assets js|$js_url" "assets css|$css_url" "mapcontext static|$mapctx_url"; do
        name="${name_url%%|*}"
        url="${name_url#*|}"
        if check_http_200 "$url"; then
            result="PASS"
            log_event_once "verify:$stage:$name:pass" "$stage verification '$name' passed"
        else
            result="FAIL"
            VERIFY_ALL_PASS=false
        fi
        printf '%s[%s] %s -> %s\n' "$indent" "$result" "$name" "$url"
    done

    if check_html_flip "$base_url" "$VERSION"; then
        result="PASS"
        log_event_once "verify:$stage:html:pass" "$stage html now references compiled/${VERSION}/"
    else
        result="FAIL"
        VERIFY_ALL_PASS=false
    fi
    printf '%s[%s] %s html references compiled/%s/\n' "$indent" "$result" "$stage" "$VERSION"

    VERIFY_TIMED_OUT=false
    if [[ "$VERIFY_ALL_PASS" != true && -n "$deadline_e" && "$(now_epoch)" -gt "$deadline_e" ]]; then
        VERIFY_TIMED_OUT=true
        printf '%sFAILED: %s verification did not pass within %ds of the infra deploy finishing\n' \
            "$indent" "$stage" "$VERIFY_TIMEOUT_SECONDS"
    fi
}

# --- production gate -----------------------------------------------------------

approve_gate() {
    local run_id="$1" run_url="$2"
    local pending env_id
    if ! pending=$(gh api "repos/${REPO_MAIN}/actions/runs/${run_id}/pending_deployments" 2>&1); then
        log "ERROR: failed to read pending deployments: $pending"
        log "Approve manually: $run_url"
        return
    fi
    env_id=$(jq -r '.[0].environment.id // empty' <<<"$pending")
    if [[ -z "$env_id" ]]; then
        log "No pending deployment found for run $run_id (already approved, or the gate closed). Check: $run_url"
        return
    fi

    local body response
    body=$(jq -nc --argjson envid "$env_id" \
        '{environment_ids: [$envid], state: "approved", comment: "Approved via release-watch.sh"}')
    if response=$(gh api --method POST "repos/${REPO_MAIN}/actions/runs/${run_id}/pending_deployments" --input - <<<"$body" 2>&1); then
        log "Approved production deploy for run $run_id."
    else
        log "Approval request was rejected: $response"
        log "Approve manually in the browser: $run_url"
    fi
}

# Offers (and, on explicit confirmation, performs) production gate approval. Never
# approves without the user typing the exact version string in this cycle.
maybe_handle_gate() {
    local run_id="$1" run_url="$2" staging_verified="$3"
    log "GATE: waiting for approval - $run_url"

    if [[ "$WATCH_ONLY" == true ]]; then
        log "  (--watch-only: not prompting for approval)"
        return
    fi
    if [[ "$staging_verified" != true ]]; then
        log "  (staging verification has not fully passed yet; holding off on prompting)"
        return
    fi
    if [[ ! -t 0 ]]; then
        log "  (stdin is not a TTY; cannot prompt for approval interactively)"
        return
    fi

    local answer
    read -r -p "  Type '${VERSION}' to approve the production deploy, or press Enter to skip this cycle: " answer || answer=""
    if [[ "$answer" == "$VERSION" ]]; then
        approve_gate "$run_id" "$run_url"
    else
        log "Gate approval skipped this cycle (will ask again next cycle)."
    fi
}

# --- one polling cycle ----------------------------------------------------------

CYCLE=0

run_cycle() {
    CYCLE=$((CYCLE + 1))

    if ! fetch_release_run; then
        return 1
    fi

    echo
    echo "================================================================"
    echo "release-watch  ${VERSION}  (release-deploy run ${RUN_ID})"
    echo "run url: ${RELEASE_RUN_URL}"
    printf 'cycle %d   %s   interval %ds\n' "$CYCLE" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$INTERVAL"
    echo "================================================================"

    local all_terminal=true
    local any_failure=false

    echo "BUILD JOBS"
    local build_jobs
    build_jobs=$(jq -c --arg staging "$DEPLOY_STAGING_JOB" --arg production "$DEPLOY_PRODUCTION_JOB" \
        'map(select(.name != $staging and .name != $production)) | sort_by(.name) | .[]' <<<"$RELEASE_JOBS_JSON")
    while IFS= read -r job_json; do
        [[ -z "$job_json" ]] && continue
        print_job_line "$job_json"
        local status conclusion
        status=$(job_field "$job_json" status)
        conclusion=$(job_field "$job_json" conclusion)
        [[ "$status" == "completed" ]] || all_terminal=false
        is_job_failed "$conclusion" && any_failure=true
    done <<<"$build_jobs"

    # --- staging ---
    echo
    echo "STAGING"
    local staging_job staging_status staging_conclusion staging_completed
    staging_job=$(jq -c --arg n "$DEPLOY_STAGING_JOB" 'map(select(.name == $n))[0] // empty' <<<"$RELEASE_JOBS_JSON")
    local staging_verified=false

    if [[ -z "$staging_job" ]]; then
        echo "  (job not present in this run yet)"
        all_terminal=false
    else
        print_job_line "$staging_job"
        staging_status=$(job_field "$staging_job" status)
        staging_conclusion=$(job_field "$staging_job" conclusion)
        staging_completed=$(job_field "$staging_job" completedAt)

        [[ "$staging_status" == "completed" ]] || all_terminal=false
        is_job_failed "$staging_conclusion" && any_failure=true

        if [[ "$staging_status" == "completed" && "$staging_conclusion" == "success" ]]; then
            local since_e infra_id
            since_e=$(epoch_of "$staging_completed") || since_e=$(now_epoch)
            if infra_id=$(find_infra_run "deploy-staging" "$VERSION" "$since_e"); then
                log_event_once "infra-found:staging:$infra_id" "infra staging run found: $infra_id"
                print_infra_run "$infra_id" "staging"
                if [[ "$INFRA_STATUS" != "completed" ]]; then
                    all_terminal=false
                elif [[ "$INFRA_CONCLUSION" != "success" ]]; then
                    any_failure=true
                else
                    echo "  verification:"
                    verify_stage "staging" "$STAGING_URL" "$INFRA_COMPLETED_AT"
                    if [[ "$VERIFY_ALL_PASS" == true ]]; then
                        staging_verified=true
                    elif [[ "$VERIFY_TIMED_OUT" == true ]]; then
                        any_failure=true
                    else
                        all_terminal=false
                    fi
                fi
            else
                echo "  infra run: not found yet, waiting..."
                all_terminal=false
            fi
        fi
    fi

    # --- production ---
    echo
    echo "PRODUCTION"
    local production_job production_status production_conclusion production_completed production_url
    production_job=$(jq -c --arg n "$DEPLOY_PRODUCTION_JOB" 'map(select(.name == $n))[0] // empty' <<<"$RELEASE_JOBS_JSON")

    if [[ -z "$production_job" ]]; then
        echo "  (job not present in this run yet)"
        all_terminal=false
    else
        production_status=$(job_field "$production_job" status)
        production_conclusion=$(job_field "$production_job" conclusion)
        production_completed=$(job_field "$production_job" completedAt)
        production_url=$(job_field "$production_job" url)

        if [[ "$production_status" == "waiting" ]]; then
            printf '  [%-9s] %-34s %s\n' "waiting" "$DEPLOY_PRODUCTION_JOB" "-"
            all_terminal=false
            maybe_handle_gate "$RUN_ID" "$production_url" "$staging_verified"
        else
            print_job_line "$production_job"
            [[ "$production_status" == "completed" ]] || all_terminal=false
            is_job_failed "$production_conclusion" && any_failure=true

            if [[ "$production_status" == "completed" && "$production_conclusion" == "success" ]]; then
                local since_e infra_id
                since_e=$(epoch_of "$production_completed") || since_e=$(now_epoch)
                if infra_id=$(find_infra_run "deploy-production" "$VERSION" "$since_e"); then
                    log_event_once "infra-found:production:$infra_id" "infra production run found: $infra_id"
                    print_infra_run "$infra_id" "production"
                    if [[ "$INFRA_STATUS" != "completed" ]]; then
                        all_terminal=false
                    elif [[ "$INFRA_CONCLUSION" != "success" ]]; then
                        any_failure=true
                    else
                        echo "  verification:"
                        verify_stage "production" "$PRODUCTION_URL" "$INFRA_COMPLETED_AT"
                        if [[ "$VERIFY_ALL_PASS" != true ]]; then
                            if [[ "$VERIFY_TIMED_OUT" == true ]]; then
                                any_failure=true
                            else
                                all_terminal=false
                            fi
                        fi
                    fi
                else
                    echo "  infra run: not found yet, waiting..."
                    all_terminal=false
                fi
            fi
        fi
    fi

    echo
    if [[ "$all_terminal" == true ]]; then
        if [[ "$any_failure" == true ]]; then
            echo "SUMMARY: FAILED — see FAILED lines above for details."
        else
            echo "SUMMARY: SUCCESS — all jobs concluded, staging verified, production verified."
        fi
        echo "================================================================"
        ALL_TERMINAL=true
        ANY_FAILURE="$any_failure"
        return 0
    fi

    echo "SUMMARY: in progress..."
    echo "================================================================"
    ALL_TERMINAL=false
    ANY_FAILURE="$any_failure"
    return 0
}

main() {
    require_deps
    parse_args "$@"
    resolve_target

    log "Watching ${VERSION} (release-deploy run ${RUN_ID} in ${REPO_MAIN})"
    [[ "$WATCH_ONLY" == true ]] && log "Running in --watch-only mode: production gate approval is disabled."

    while true; do
        ALL_TERMINAL=false
        ANY_FAILURE=false

        run_cycle || true

        if [[ "$ALL_TERMINAL" == true ]]; then
            if [[ "$ANY_FAILURE" == true ]]; then
                exit 1
            else
                exit 0
            fi
        fi

        sleep "$INTERVAL"
    done
}

main "$@"
