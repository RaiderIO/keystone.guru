#!/usr/bin/env bash
# Status line for Claude Code, checked into the repo so it's identical across machines.
# Wired up via .claude/settings.json (statusLine), which locates this script from workspace.project_dir.
# Layout:  <branch><dirty> 5h:<bar> HH:MM 7d:<bar> ............ <worktree>:<port> <model>
input=$(cat)

# Session working directory (from JSON): the status line command is not guaranteed to run in the
# session's cwd, so drive every git call off this rather than the process cwd.
cwd=$(echo "$input" | python3 -c "
import sys, json
d = json.load(sys.stdin)
print(d.get('workspace', {}).get('current_dir') or d.get('cwd') or '')
" 2>/dev/null)
[ -z "$cwd" ] && cwd=$PWD

# Git branch + dirty flag
branch=$(git -C "$cwd" --no-optional-locks branch --show-current 2>/dev/null)
dirty=$([ -n "$(git -C "$cwd" --no-optional-locks status --porcelain 2>/dev/null)" ] && printf '*')
git_part=$([ -n "$branch" ] && printf '\033[01;33m%s%s\033[00m' "$branch" "$dirty")

# Rate bars (width 20) + refresh time (HH:MM only).
# Any extra per-model weekly limits (e.g. seven_day_fable) render after the 7d bar.
rate_part=$(echo "$input" | python3 -c "
import sys, json, datetime
esc = chr(27)
d = json.load(sys.stdin)
r = d.get('rate_limits', {})
def bar(p, w=20):
    filled = round(p / 100 * w)
    color = '32' if p < 50 else '33' if p < 80 else '31'
    return esc + '[' + color + 'm' + '█' * filled + '░' * (w - filled) + esc + '[00m'
fh = r.get('five_hour', {})
wd = r.get('seven_day', {})
fh_pct = fh.get('used_percentage')
wd_pct = wd.get('used_percentage')
fh_reset = fh.get('resets_at')
parts = []
if fh_pct is not None:
    time_str = (' ' + datetime.datetime.fromtimestamp(fh_reset).strftime('%H:%M')) if fh_reset is not None else ''
    parts.append('5h:' + bar(fh_pct) + time_str)
if wd_pct is not None:
    parts.append('7d:' + bar(wd_pct))
for key, window in r.items():
    if key in ('five_hour', 'seven_day') or not isinstance(window, dict):
        continue
    pct = window.get('used_percentage')
    if pct is None:
        continue
    label = key.replace('seven_day_', '7d-').replace('_', '-')
    parts.append(label + ':' + bar(pct))
print(' '.join(parts))
" 2>/dev/null)

# Model name
model=$(echo "$input" | python3 -c "
import sys, json
d = json.load(sys.stdin)
m = d.get('model', '')
name = (m.get('display_name') or m.get('id', '')) if isinstance(m, dict) else str(m)
if name.lower().startswith('claude '):
    name = name[7:]
print(name)
" 2>/dev/null)

# Worktree name + nginx port for the worktree this session owns.
# Sessions run in the main repo, so the worktree can't be found from cwd; instead it's recorded in a
# session-keyed marker (written by bind-worktree.sh when Claude creates a worktree). Marker holds the
# worktree's absolute path. Markers live under $HOME (machine-local runtime state), not in the repo.
wt_part=""
session_id=$(echo "$input" | python3 -c "
import sys, json
print(json.load(sys.stdin).get('session_id', ''))
" 2>/dev/null)
marker="$HOME/.claude/statusline/session-worktree/$session_id"
if [ -n "$session_id" ] && [ -f "$marker" ]; then
    wt_path=$(cat "$marker" 2>/dev/null)
    if [ -n "$wt_path" ] && [ -d "$wt_path" ]; then
        wt_name=${wt_path##*/}
        wt_port=$(grep -m1 '^WORKTREE_HTTP_PORT=' "$wt_path/.env" 2>/dev/null | cut -d= -f2)
        wt_part=$(printf '\033[01;36m%s\033[00m' "$wt_name")
        [ -n "$wt_port" ] && wt_part=$(printf '%s:\033[01;32m%s\033[00m' "$wt_part" "$wt_port")
    else
        # Worktree gone — clean up the stale marker so it stops showing.
        rm -f "$marker" 2>/dev/null
    fi
fi

# Assemble left side
left=""
[ -n "$git_part" ] && left="$git_part"
[ -n "$rate_part" ] && left="${left}${left:+ }${rate_part}"

# Assemble right side: worktree[:port] then model
right=""
[ -n "$wt_part" ] && right="$wt_part"
[ -n "$model" ] && right="${right}${right:+ }$(printf '\033[01;35m%s\033[00m' "$model")"

# Right-align
if [ -n "$right" ]; then
    cols=${COLUMNS:-$(tput cols 2>/dev/null || echo 120)}
    left_plain=$(printf '%s' "$left" | sed 's/\x1b\[[0-9;]*m//g')
    right_plain=$(printf '%s' "$right" | sed 's/\x1b\[[0-9;]*m//g')
    pad=$(( cols - ${#left_plain} - ${#right_plain} - 4 ))
    [ "$pad" -lt 3 ] && pad=3
    printf '%s%*s%s' "$left" "$pad" "" "$right"
else
    printf '%s' "$left"
fi
