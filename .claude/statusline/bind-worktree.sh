#!/usr/bin/env bash
# Bind/unbind the worktree a Claude session owns, so the status line can show its name + port.
#
#   bind-worktree.sh bind   <session_id> <worktree_abs_path>
#   bind-worktree.sh unbind <session_id>
#
# The status line (statusline.sh) reads $HOME/.claude/statusline/session-worktree/<session_id> (an
# abs worktree path) and renders "<name>:<port>" on the right. Call bind right after creating a
# worktree for a session, and unbind when tearing it down. Stale markers (worktree gone) are
# auto-cleaned by the status line. Markers live under $HOME (machine-local runtime state), not the repo.
set -euo pipefail

dir="$HOME/.claude/statusline/session-worktree"
mkdir -p "$dir"

action=${1:-}
session_id=${2:-}

case "$action" in
    bind)
        wt_path=${3:-}
        [ -n "$session_id" ] && [ -n "$wt_path" ] || { echo "usage: bind-worktree.sh bind <session_id> <worktree_abs_path>" >&2; exit 2; }
        printf '%s' "$wt_path" > "$dir/$session_id"
        echo "bound $session_id -> $wt_path"
        ;;
    unbind)
        [ -n "$session_id" ] || { echo "usage: bind-worktree.sh unbind <session_id>" >&2; exit 2; }
        rm -f "$dir/$session_id"
        echo "unbound $session_id"
        ;;
    *)
        echo "usage: bind-worktree.sh {bind <session_id> <worktree_abs_path>|unbind <session_id>}" >&2
        exit 2
        ;;
esac
