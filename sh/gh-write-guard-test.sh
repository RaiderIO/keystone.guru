#!/usr/bin/env bash
#
# Case table for sh/gh-write-guard.sh. Run by hand after touching the guard:
#
#   sh/gh-write-guard-test.sh
#
# The keystoneguru-infra exemption is the part worth testing: it used to be a bare substring match
# on the whole command, so naming the repo in a title, body, filename or comment disabled the guard
# for a write to any repository. Every "infra named but not targeted" case below is that hole.
#
# Exits non-zero on the first mismatch and prints the offending case.

set -euo pipefail

GUARD="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/gh-write-guard.sh"
KSG='/home/wouterkoppenol/Git/private/keystone.guru'
INFRA='/home/wouterkoppenol/Git/private/keystoneguru-infra'

failures=0

# expect: "allow" or "deny"
assert_guard() {
    local expected="$1" description="$2" command="$3" cwd="$4"

    local output actual
    # The guard prints nothing at all when it permits a command - an empty payload IS the allow
    output="$(jq -n --arg c "$command" --arg d "$cwd" '{tool_input: {command: $c}, cwd: $d}' | bash "$GUARD")"
    if [[ -z "${output//[[:space:]]/}" ]]; then
        actual='allow'
    else
        actual="$(printf '%s' "$output" | jq -r '.hookSpecificOutput.permissionDecision // "allow"')"
    fi

    if [[ "$actual" != "$expected" ]]; then
        printf 'FAIL  %s\n      expected %s, got %s\n      cmd: %s\n' \
            "$description" "$expected" "$actual" "$command" >&2
        failures=$((failures + 1))
    else
        printf 'ok    %s\n' "$description"
    fi
}

# Guarded: ordinary keystone.guru writes belong on sh/gh-bot.sh
assert_guard deny 'pr comment on keystone.guru' \
    'gh pr comment 4200 --repo RaiderIO/keystone.guru --body hi' "$KSG"
assert_guard deny 'label edit on keystone.guru' \
    'gh pr edit 1 -R RaiderIO/keystone.guru --add-label "pr can merge"' "$KSG"
assert_guard deny 'api write on keystone.guru' \
    'gh api -X POST repos/RaiderIO/keystone.guru/issues/1/comments -f body=x' "$KSG"
assert_guard deny 'write with no explicit repo, from the keystone.guru checkout' \
    'gh pr comment 4200 --body hi' "$KSG"

# Guarded: keystoneguru-infra is merely NAMED, not targeted - the substring hole
assert_guard deny 'keystone.guru write mentioning infra in the body' \
    'gh pr comment 4200 --repo RaiderIO/keystone.guru --body "see keystoneguru-infra for the CDK"' "$KSG"
assert_guard deny 'keystone.guru write mentioning infra in a filename' \
    'gh pr comment 1 --repo RaiderIO/keystone.guru --body-file /tmp/keystoneguru-infra-notes.md' "$KSG"
assert_guard deny 'keystone.guru issue with infra in a markdown heading' \
    'gh issue create --repo RaiderIO/keystone.guru --title x --body "# keystoneguru-infra"' "$KSG"
assert_guard deny 'one command writing to both repos' \
    'gh pr edit 5 --repo RaiderIO/keystoneguru-infra --add-label x && gh pr edit 1 --repo RaiderIO/keystone.guru --add-label y' "$KSG"

# Exempt: the write actually lands on keystoneguru-infra, where the bot is not a collaborator
assert_guard allow 'infra write via --repo' \
    'gh pr comment 5 --repo RaiderIO/keystoneguru-infra --body hi' "$KSG"
assert_guard allow 'infra write via --repo=' \
    'gh pr comment 5 --repo=RaiderIO/keystoneguru-infra --body hi' "$KSG"
assert_guard allow 'infra write via -R' \
    'gh pr edit 5 -R RaiderIO/keystoneguru-infra --add-label x' "$KSG"
assert_guard allow 'infra api write via a repos/ path' \
    'gh api -X POST repos/RaiderIO/keystoneguru-infra/issues/5/comments -f body=x' "$KSG"
assert_guard allow 'write with no explicit repo, from the infra checkout' \
    'gh pr comment 5 --body hi' "$INFRA"

# Exempt: not a guarded shape to begin with
assert_guard allow 'already routed through the bot wrapper' \
    'sh/gh-bot.sh pr comment 4200 --repo RaiderIO/keystone.guru --body hi' "$KSG"
assert_guard allow 'read command' \
    'gh pr view 4200 --repo RaiderIO/keystone.guru --json body' "$KSG"
assert_guard allow 'api read' \
    'gh api repos/RaiderIO/keystone.guru/pulls/1' "$KSG"

if [[ $failures -gt 0 ]]; then
    printf '\n%d case(s) failed.\n' "$failures" >&2
    exit 1
fi

printf '\nAll cases passed.\n'
