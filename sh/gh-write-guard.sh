#!/usr/bin/env bash
#
# PreToolUse hook (Bash matcher) that blocks plain `gh` write commands (PR/issue create, edit,
# comment, ready, close, reopen, merge, review, label, or `gh api` with a write HTTP method) and
# tells the caller to use sh/gh-bot.sh instead, per CLAUDE.md's "every write ... through
# sh/gh-bot.sh" rule (#3924). Read commands (`gh pr view`, `gh issue list`, `gh pr checks`, ...)
# and anything already routed through sh/gh-bot.sh are left alone. See .claude/settings.json for
# the hook wiring.
#
# Reads the PreToolUse hook JSON payload on stdin; see:
#   https://docs.claude.com/en/docs/claude-code/hooks

set -euo pipefail

input="$(cat)"
cmd="$(printf '%s' "$input" | jq -r '.tool_input.command // empty')"

if [[ -z "$cmd" ]]; then
    exit 0
fi

# Already routed through the bot wrapper - nothing to guard.
if [[ "$cmd" == *gh-bot.sh* ]]; then
    exit 0
fi

# keystone-guru-bot is not a collaborator on keystoneguru-infra (org owner-only add, see the
# keystoneguru-infra-workflow skill), so sh/gh-bot.sh always 404s/GraphQL-errors there - it is
# not a case sh/gh-bot.sh can ever satisfy. Writes targeting that repo use plain `gh` with the
# `:robot:` body prefix as their only (documented, permanent) authorship signal.
#
# The exemption is decided by where the write actually lands, never by the command merely mentioning
# the repo somewhere: a bare substring test would let any write to any repo through by naming
# keystoneguru-infra in a title, body, filename or shell comment.
#
#   1. An explicit target - `--repo`/`-R <owner>/<name>`, or a `repos/<owner>/<name>` REST path -
#      exempts the command only when EVERY target it names is keystoneguru-infra. A command that
#      touches both repos is guarded, since the keystone.guru half of it belongs on the bot.
#   2. With no explicit target at all, `gh` falls back to the repository of the working directory,
#      so the checkout's own `origin` remote decides - read from git config, not from `$cmd`.
#
# A write to keystoneguru-infra issued from elsewhere without naming it (`cd /path/to/infra && gh
# pr comment ...`) is guarded rather than exempted: pass `--repo RaiderIO/keystoneguru-infra` and it
# is let through. Failing towards the guard is the safe direction.
mapfile -t gh_repo_targets < <(
    printf '%s\n' "$cmd" \
        | grep -oE '(--repo|-R)[[:space:]=]+[^[:space:]]+|(^|[^[:alnum:]_/-])repos/[A-Za-z0-9._-]+/[A-Za-z0-9._-]+' \
        | sed -E 's/^(--repo|-R)[[:space:]=]+//; s#^[^[:alnum:]_/-]*repos/##' \
        | tr -d "\"'"
)

is_infra_target() {
    [[ "${1##*/}" == 'keystoneguru-infra' ]]
}

if [[ ${#gh_repo_targets[@]} -gt 0 ]]; then
    all_infra=1
    for target in "${gh_repo_targets[@]}"; do
        if ! is_infra_target "$target"; then
            all_infra=0
            break
        fi
    done

    if [[ $all_infra -eq 1 ]]; then
        exit 0
    fi
else
    hook_cwd="$(printf '%s' "$input" | jq -r '.cwd // empty')"
    if [[ -n "$hook_cwd" ]]; then
        origin_remote="$(git -C "$hook_cwd" remote get-url origin 2>/dev/null || true)"
        if [[ "${origin_remote%.git}" == */keystoneguru-infra ]]; then
            exit 0
        fi
    fi
fi

write_pattern='(^|[;&|]|[[:space:]])gh[[:space:]]+(pr|issue)[[:space:]]+(create|edit|comment|ready|close|reopen|merge|review)([[:space:]]|$)'
label_pattern='(^|[;&|]|[[:space:]])gh[[:space:]]+(pr|issue)[[:space:]]+.*(--add-label|--remove-label)'
api_write_pattern='(^|[;&|]|[[:space:]])gh[[:space:]]+api[[:space:]].*-X[[:space:]]*(POST|PATCH|DELETE|PUT)'

if [[ "$cmd" =~ $write_pattern ]] || [[ "$cmd" =~ $label_pattern ]] || [[ "$cmd" =~ $api_write_pattern ]]; then
    reason='This looks like a plain `gh` write (create/edit/comment/ready/close/reopen/merge/review/label/api write). Per CLAUDE.md, route every GitHub write through sh/gh-bot.sh instead, so activity is attributed to keystone-guru-bot. Only fall back to plain gh (with a :robot: prefix in the body) if sh/gh-bot.sh itself fails.'
    jq -n --arg reason "$reason" '{
        hookSpecificOutput: {
            hookEventName: "PreToolUse",
            permissionDecision: "deny",
            permissionDecisionReason: $reason
        }
    }'
fi

exit 0
