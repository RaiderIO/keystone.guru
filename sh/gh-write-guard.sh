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
