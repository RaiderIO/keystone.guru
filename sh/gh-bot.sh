#!/usr/bin/env bash
#
# Run `gh` as the agent's bot account instead of the human account that `gh auth login` stored on
# this machine, so PR/comment authorship is real signal rather than a `:robot:` prefix convention
# (see issue #3924).
#
# Usage is a straight pass-through — anything you would hand to `gh`, hand to this instead:
#
#   sh/gh-bot.sh pr create --repo RaiderIO/keystone.guru --base master --title '...' --body-file b.md
#   sh/gh-bot.sh api -X POST repos/RaiderIO/keystone.guru/issues/123/comments -F body=@comment.md
#   sh/gh-bot.sh api user --jq .login          # self-check: must print the bot login, not Wotuu
#
# The bot token is exported as GH_TOKEN for this process only. `gh` prefers GH_TOKEN over the
# credential in ~/.config/gh/hosts.yml, so the human's own `gh auth login` session is left entirely
# untouched — plain `gh` in the same shell still runs as the human.
#
# There is deliberately NO fallback to plain `gh`, and no path on which this script posts as anyone
# other than the bot. A missing token is a hard failure, and so is a token belonging to some *other*
# account — both would otherwise end with a comment attributed to the human while the caller
# believed it had posted as the bot, which is the exact ambiguity this script exists to remove.

set -euo pipefail

BOT_LOGIN="${KSG_BOT_GH_LOGIN:-keystone-guru-bot}"
TOKEN_FILE="${KSG_BOT_GH_TOKEN_FILE:-$HOME/.config/keystone-guru/bot-gh-token}"

die() {
    echo "gh-bot.sh: $*" >&2
    exit 1
}

# ~/.local/bin/gh (2.96+) shadows the apt-installed /usr/bin/gh (2.4.0) in an interactive shell, but
# a hook, cron entry or subagent may not inherit that PATH — and 2.4.0 fails on `gh pr edit` with a
# Projects-classic GraphQL error. Resolve the newer binary explicitly rather than trusting PATH.
GH_BIN="${KSG_GH_BIN:-}"
if [ -z "$GH_BIN" ]; then
    if [ -x "$HOME/.local/bin/gh" ]; then
        GH_BIN="$HOME/.local/bin/gh"
    else
        GH_BIN="$(command -v gh || true)"
    fi
fi
[ -n "$GH_BIN" ] && [ -x "$GH_BIN" ] || die "no gh binary found (looked at \$KSG_GH_BIN, ~/.local/bin/gh, \$PATH)"

token="${KSG_BOT_GH_TOKEN:-}"
if [ -z "$token" ] && [ -f "$TOKEN_FILE" ]; then
    token="$(tr -d '[:space:]' < "$TOKEN_FILE")"
fi

if [ -z "$token" ]; then
    die "no token for '$BOT_LOGIN'.
  Set \$KSG_BOT_GH_TOKEN, or write the fine-grained PAT to $TOKEN_FILE (chmod 600).
  Until the bot account is provisioned, use plain 'gh' with a ':robot:' prefixed body instead.
  Setup steps: see the 'worktree-docker' skill, 'Posting to GitHub as the bot account'."
fi

[ $# -gt 0 ] || die "no arguments — this is a pass-through wrapper around gh"

# Export rather than `exec env GH_TOKEN=… gh …`: an env-prefixed exec puts the PAT in the new
# process's argv, where it is readable by any local process via /proc/<pid>/cmdline and is recorded
# persistently by execve process accounting. Exporting is behaviour-identical and leaks neither.
export GH_TOKEN="$token"
export GH_HOST=github.com

# Confirm the token really belongs to the bot before handing it to gh. Without this check the
# "never silently posts as the human" guarantee would cover only a *missing* token — a stale,
# swapped or copy-pasted-from-the-wrong-place PAT would post under whoever actually owns it, with
# no visible sign, which is precisely the ambiguity this script exists to remove. One extra HTTP
# round trip per invocation is a fair price; set KSG_BOT_GH_SKIP_VERIFY=1 inside a loop that has
# already verified once.
if [ "${KSG_BOT_GH_SKIP_VERIFY:-0}" != "1" ]; then
    # Branch on gh's exit status, not on whether output is empty: `gh api` prints the error body
    # (e.g. {"message":"Bad credentials"}) to *stdout* on an HTTP error, so `|| true` plus an
    # emptiness test would sail past a 401 and then report the JSON blob as the account name.
    if ! actual_login="$("$GH_BIN" api user --jq .login 2>/dev/null)"; then
        die "GitHub rejected the token (expired, revoked, or blocked by org policy — fine-grained
  PATs can need org-owner approval). Re-check it, or use plain 'gh' with a ':robot:' prefixed body
  in the meantime."
    fi
    [ "$actual_login" = "$BOT_LOGIN" ] || die "refusing to run: this token belongs to
  '$actual_login', not '$BOT_LOGIN'. Posting with it would attribute agent activity to the wrong
  account — the exact failure this wrapper exists to prevent. Fix the token, or call plain 'gh'
  directly if posting as '$actual_login' is really what you meant."
fi

exec "$GH_BIN" "$@"
