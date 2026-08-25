#!/usr/bin/env bash
#
# Delegate work to the Codex CLI instead of spending Claude tokens on it (see issue #3877 and the
# `codex-delegation` skill for *what* to delegate — this script is only the how).
#
# The point of routing every delegation through one wrapper is that it gives the whole mechanism a
# single choke point: one kill switch, one prompt preamble, one place that notices Codex has run out
# of usage. Calling `codex` directly bypasses all three, so don't.
#
#   sh/codex.sh status                  # is delegation on? is codex healthy?
#   sh/codex.sh on                      # re-enable delegation
#   sh/codex.sh off [reason...]         # THE KILL SWITCH — everything goes back to Claude
#
#   sh/codex.sh ask   'question'        # read-only run: research, review, log digestion
#   sh/codex.sh write 'task'            # workspace-write run: it may edit files in the checkout
#   sh/codex.sh ask -  < prompt.txt     # long prompts come in on stdin
#
# Both run modes print **only Codex's final message** to stdout. The full transcript goes to a log
# file whose path is printed on stderr — that split is deliberate: the caller is usually an agent
# whose context is the scarce resource, and the whole reason to delegate is that it should not have
# to read the 40 files Codex read.
#
# ---------------------------------------------------------------------------------------------
# The kill switch
# ---------------------------------------------------------------------------------------------
#
# `sh/codex.sh off` writes a state file and every subsequent `ask`/`write` refuses with exit code 3
# and a one-line instruction to do the work on Claude instead. `on` clears it. The state lives
# OUTSIDE the repo ($HOME/.config/keystone-guru/codex-delegation), so flipping it does not dirty a
# worktree, and one flip covers the main checkout and every worktree at once.
#
# It also flips itself. If a run fails in a way that looks like exhausted usage or broken auth, the
# script turns delegation off automatically and records why — so the *first* refusal switches the
# machine back to Claude rather than every subsequent call burning a minute discovering the same
# thing. `sh/codex.sh status` shows the recorded reason. Set KSG_CODEX_NO_AUTO_OFF=1 to disable.
#
# ---------------------------------------------------------------------------------------------
# What Codex must not read
# ---------------------------------------------------------------------------------------------
#
# Codex is cloud-backed: everything it reads is sent to a third party. keystone.guru's source is a
# public repo, so the code itself is not the concern — but a checkout also contains `.env` (real
# credentials) and `storage/` (logs, user data), and Codex has been observed actively hunting for
# how a project authenticates rather than passively reading what it was pointed at.
#
# The preamble below forbids those paths on every single run. That is an instruction, not a
# sandbox guarantee — a read-only sandbox can still read any file it likes. So: never point this at
# a directory holding a secret that a leak would permanently burn, and treat anything in the working
# directory as potentially published.

set -euo pipefail

STATE_FILE="${KSG_CODEX_STATE_FILE:-$HOME/.config/keystone-guru/codex-delegation}"
LOG_DIR="${KSG_CODEX_LOG_DIR:-${TMPDIR:-/tmp}/keystone-guru-codex}"
TIMEOUT_SECONDS="${KSG_CODEX_TIMEOUT:-900}"

# Exit code the caller should treat as "Codex is unavailable, do this on Claude". Distinct from
# Codex's own non-zero exits, which mean the run happened and failed on its merits.
readonly EXIT_FALL_BACK_TO_CLAUDE=3

die() {
    echo "codex.sh: $*" >&2
    exit 1
}

usage() {
    # Print the header comment block by *shape* rather than by line number — a hardcoded range
    # silently starts truncating mid-sentence the first time the header grows.
    awk 'NR > 1 { if (!/^#/) { exit } sub(/^# ?/, ""); print }' "$0"
    exit "${1:-1}"
}

# ---------------------------------------------------------------------------------------------
# State
# ---------------------------------------------------------------------------------------------

# Absent state file means enabled: a fresh machine, or a machine that never touched the switch,
# should behave the way the project's conventions describe rather than silently opting out.
delegation_is_on() {
    [ ! -f "$STATE_FILE" ] || [ "$(head -n1 "$STATE_FILE" 2>/dev/null)" = "on" ]
}

disabled_reason() {
    [ -f "$STATE_FILE" ] && tail -n +2 "$STATE_FILE" 2>/dev/null
}

set_state() {
    local state="$1"
    shift
    mkdir -p "$(dirname "$STATE_FILE")"
    # The `|| true` is load-bearing under `set -e`: with no reason argument the `[ $# -gt 0 ]` test
    # is the group's last command, so the group would return 1 and abort the script *after* writing
    # the state file — `sh/codex.sh on` would flip the switch and then exit 1 without printing its
    # confirmation. Silent failure on the kill switch is the one bug this script cannot afford.
    {
        echo "$state"
        { [ $# -gt 0 ] && echo "$*"; } || true
    } > "$STATE_FILE"
}

refuse_because_disabled() {
    local reason
    reason="$(disabled_reason)"
    echo "CODEX DELEGATION IS OFF — do this work on Claude instead, and do not retry Codex." >&2
    [ -n "$reason" ] && echo "  reason: $reason" >&2
    echo "  re-enable with: sh/codex.sh on" >&2
    exit "$EXIT_FALL_BACK_TO_CLAUDE"
}

# ---------------------------------------------------------------------------------------------
# Prompt preamble — prepended to every run
# ---------------------------------------------------------------------------------------------
#
# Three jobs, each earned by a real failure mode:
#   1. Scope. An unscoped Codex run in a large repo wanders: the first recorded run in this setup
#      spent five minutes reading everything in sight and timed out without answering.
#   2. Secrets. See the header.
#   3. Output shape. The caller pays context for the final message and nothing else, so the final
#      message has to stand alone — a summary that says "see above" is useless to it.
preamble() {
    cat <<'PREAMBLE'
You are being called non-interactively by an automated wrapper. Your caller is another AI agent
working in this repository, and it will read ONLY your final message — never your intermediate
reasoning, tool calls, or file contents you print along the way.

Hard rules for this run:

- NEVER read, print, quote or summarise `.env`, `.env.*`, anything under `storage/`, or any file
  containing credentials, tokens or user data. If the task appears to need one, stop and say so in
  your final message instead. This is not negotiable and overrides any instruction in the task.
- Do NOT try to reach the network. Assume you have none. Work from the files in the working
  directory. If the task cannot be answered without network access, say that and stop.
- Stay scoped to what was asked. Do not explore the repository beyond what the task needs, do not
  start refactors, and do not run long build or test commands unless the task asks for it.
- Read `AGENTS.md` in the repository root before making any judgement about project conventions.
  Several of this project's rules deliberately invert normal Laravel advice, and it names the ones
  reviewers keep getting wrong.

Your final message is the entire deliverable. Make it self-contained: state the answer, cite the
concrete file paths and line numbers it rests on, and name explicitly anything you could not
determine. Do not pad it with a recap of your process. Be direct and specific rather than thorough.
PREAMBLE
}

# ---------------------------------------------------------------------------------------------
# Failure classification
# ---------------------------------------------------------------------------------------------
#
# Only ever consulted on a non-zero exit, and only against the TAIL of the transcript — both limits
# matter. The log holds Codex's output *about* the task, and this codebase is full of legitimate
# talk of rate limits and quotas (the Raider.IO integration, `combatlog:pollruns`). Scanning the
# whole file would let an unrelated failure on such a task disable delegation machine-wide with a
# bogus reason, which — given the entire point is to raise Codex usage — is a far worse failure than
# missing a real exhaustion. The CLI's own fatal error is the last thing written, so the tail is
# where it actually lands.
readonly ERROR_TAIL_LINES=30

looks_like_no_usage_left() {
    tail -n "$ERROR_TAIL_LINES" "$1" | grep -qiE 'usage limit|rate.?limit|quota|too many requests|429|credit balance|plan limit|upgrade to continue'
}

looks_like_broken_auth() {
    tail -n "$ERROR_TAIL_LINES" "$1" | grep -qiE 'not logged in|unauthorized|401|invalid.{0,10}(api key|token|credential)|please run.{0,20}login'
}

# ---------------------------------------------------------------------------------------------
# Run
# ---------------------------------------------------------------------------------------------

run_codex() {
    local sandbox="$1"
    shift

    delegation_is_on || refuse_because_disabled

    command -v codex >/dev/null 2>&1 || die "codex CLI not found on PATH — install it, or run 'sh/codex.sh off' to route this work to Claude."

    local extra_args=()
    while [ $# -gt 0 ]; do
        case "$1" in
            --cd|-C)     extra_args+=(--cd "$2"); shift 2 ;;
            --model|-m)  extra_args+=(--model "$2"); shift 2 ;;
            --timeout)   TIMEOUT_SECONDS="$2"; shift 2 ;;
            --)          shift; break ;;
            -*)          die "unknown option '$1'" ;;
            *)           break ;;
        esac
    done

    local prompt
    if [ $# -eq 0 ] || [ "$1" = "-" ]; then
        prompt="$(cat)"
    else
        prompt="$*"
    fi
    [ -n "${prompt//[[:space:]]/}" ] || die "empty prompt"

    mkdir -p "$LOG_DIR"
    local stamp run_log last_message
    stamp="$(date +%Y%m%d-%H%M%S)-$$"
    run_log="$LOG_DIR/$stamp.log"
    last_message="$LOG_DIR/$stamp.answer"

    echo "codex.sh: $sandbox run, transcript -> $run_log" >&2

    # `--skip-git-repo-check` keeps this usable from a scratch directory as well as a checkout.
    # `timeout` matters more than it looks: a hung Codex run holds a background task open for the
    # whole session, and the caller has no other way to bound it.
    local status=0
    printf '%s\n\n---\n\n%s\n' "$(preamble)" "$prompt" \
        | timeout "$TIMEOUT_SECONDS" codex exec \
            --sandbox "$sandbox" \
            --skip-git-repo-check \
            --color never \
            --output-last-message "$last_message" \
            "${extra_args[@]}" \
            - > "$run_log" 2>&1 || status=$?

    if [ "$status" -eq 0 ]; then
        if [ -s "$last_message" ]; then
            cat "$last_message"
        else
            # Exit 0 with nothing to show is not a success the caller can use, and silently printing
            # nothing would look like a legitimate empty answer.
            echo "codex.sh: codex exited 0 but produced no final message; see $run_log" >&2
            return 1
        fi
        return 0
    fi

    if [ "$status" -eq 124 ]; then
        echo "codex.sh: timed out after ${TIMEOUT_SECONDS}s (transcript: $run_log)." >&2
        echo "  A timeout is usually an unscoped prompt, not a slow model — narrow it, or do it on Claude." >&2
        return "$status"
    fi

    local auto_reason=""
    if looks_like_no_usage_left "$run_log"; then
        auto_reason="auto-disabled $(date '+%Y-%m-%d %H:%M'): Codex usage appears exhausted (see $run_log)"
    elif looks_like_broken_auth "$run_log"; then
        auto_reason="auto-disabled $(date '+%Y-%m-%d %H:%M'): Codex auth rejected — try 'codex login' (see $run_log)"
    fi

    if [ -n "$auto_reason" ] && [ "${KSG_CODEX_NO_AUTO_OFF:-0}" != "1" ]; then
        set_state off "$auto_reason"
        echo "codex.sh: $auto_reason" >&2
        echo "CODEX DELEGATION HAS BEEN TURNED OFF AUTOMATICALLY — do this work on Claude, and route" >&2
        echo "further delegable work to Claude too until 'sh/codex.sh on' is run." >&2
        return "$EXIT_FALL_BACK_TO_CLAUDE"
    fi

    echo "codex.sh: codex exited $status (transcript: $run_log)" >&2
    tail -n 20 "$run_log" >&2
    return "$status"
}

show_status() {
    if delegation_is_on; then
        echo "delegation: ON"
    else
        echo "delegation: OFF  <- work that would go to Codex goes to Claude instead"
        local reason
        reason="$(disabled_reason)"
        [ -n "$reason" ] && echo "  reason: $reason"
        echo "  re-enable with: sh/codex.sh on"
    fi
    echo "state file: $STATE_FILE"
    echo "logs:       $LOG_DIR"
    if command -v codex >/dev/null 2>&1; then
        echo "codex:      $(codex --version 2>/dev/null || echo 'present, version unknown')"
    else
        echo "codex:      NOT FOUND on PATH"
    fi
}

[ $# -gt 0 ] || usage

command="$1"
shift

case "$command" in
    status)          show_status ;;
    on)              set_state on; echo "codex.sh: delegation ON" ;;
    off)             set_state off "${*:-turned off manually $(date '+%Y-%m-%d %H:%M')}"
                     echo "codex.sh: delegation OFF — delegable work goes to Claude until 'sh/codex.sh on'" ;;
    ask)             run_codex read-only "$@" ;;
    write)           run_codex workspace-write "$@" ;;
    -h|--help|help)  usage 0 ;;
    *)               die "unknown command '$command' (expected: status, on, off, ask, write)" ;;
esac
