#!/usr/bin/env bash
# Status line for Claude Code, checked into the repo so it's identical across machines.
# Wired up via .claude/settings.json (statusLine), which locates this script from workspace.project_dir.
# Layout:  <ctx tokens> 5h:<bar> HH:MM 7d:<bar> cx:<bar> ............ <worktree>:<port> <model>
input=$(cat)
script_dir=$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd)

# Context window usage (e.g. "52.5k"), coloured by how full the window is — same green/yellow/red
# thresholds as the rate bars below. Renders nothing before the first API response of a session,
# when the payload carries no usage yet.
context_part=$(echo "$input" | python3 -c "
import sys, json
esc = chr(27)
d = json.load(sys.stdin)
c = d.get('context_window') or {}
used = (c.get('total_input_tokens') or 0) + (c.get('total_output_tokens') or 0)
if used:
    pct = c.get('used_percentage') or 0
    color = '32' if pct < 50 else '33' if pct < 80 else '31'
    label = ('%.1fk' % (used / 1000)) if used >= 1000 else str(used)
    print(esc + '[01;' + color + 'm' + label + esc + '[00m')
" 2>/dev/null)

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

# Codex account usage (5h/weekly-style bar, same rendering as the Claude rate bars above).
# The account/rateLimits/read call goes through the Codex app-server, which can take the better
# part of a second on a cold broker start — never call it inline here. Instead, read a cache file
# and refresh it in the background (detached, non-blocking) whenever it's stale. The cache is
# machine-global under $HOME (Codex usage is an account quota, not tied to any one repo/worktree),
# same convention as the session-worktree markers below.
codex_cache="$HOME/.claude/statusline/codex-usage-cache.json"
codex_refresh_script="$script_dir/codex-usage-refresh.mjs"
if [ -f "$codex_refresh_script" ] && command -v node >/dev/null 2>&1; then
    cache_mtime=$(stat -c %Y "$codex_cache" 2>/dev/null || echo 0)
    cache_age=$(( $(date +%s) - cache_mtime ))
    if [ "$cache_age" -gt 300 ]; then
        # Touch first so a burst of concurrent status-line renders (multiple sessions) only
        # spawns one refresh, not one per render — the next renders see a "fresh enough" mtime.
        mkdir -p "$(dirname "$codex_cache")" && touch "$codex_cache"
        ( node "$codex_refresh_script" >/dev/null 2>&1 & disown ) 2>/dev/null
    fi
fi

codex_part=$(python3 -c "
import json, sys
esc = chr(27)
def bar(p, w=20):
    filled = round(p / 100 * w)
    color = '32' if p < 50 else '33' if p < 80 else '31'
    return esc + '[' + color + 'm' + '█' * filled + '░' * (w - filled) + esc + '[00m'
try:
    with open('$codex_cache') as f:
        d = json.load(f)
except Exception:
    sys.exit(0)
if not d.get('available'):
    sys.exit(0)
primary = d.get('primary') or {}
pct = primary.get('usedPercent')
if pct is None:
    sys.exit(0)
# Short windows (under a day, e.g. a 5h-style bucket) are worth a reset clock like the Claude 5h
# bar; long windows (weekly) aren't, matching how the 7d bar above omits it too.
duration = primary.get('windowDurationMins')
resets_at = primary.get('resetsAt')
suffix = ''
if duration is not None and duration < 1440 and resets_at is not None:
    import datetime
    suffix = ' ' + datetime.datetime.fromtimestamp(resets_at).strftime('%H:%M')
print('cx:' + bar(pct) + suffix)
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
# session-keyed marker (written automatically by sh/worktree.sh create, via bind-worktree.sh, using
# $CLAUDE_CODE_SESSION_ID). Marker holds the worktree's absolute path. Markers live under $HOME
# (machine-local runtime state), not in the repo.
wt_part=""
session_id=$(echo "$input" | python3 -c "
import sys, json
print(json.load(sys.stdin).get('session_id', ''))
" 2>/dev/null)

# Self-heal: drop any marker whose worktree no longer exists — covers markers left behind by
# sessions that have since ended (their own status line never renders again to clean up), so the
# marker dir doesn't grow without bound.
marker_dir="$HOME/.claude/statusline/session-worktree"
if [ -d "$marker_dir" ]; then
    for m in "$marker_dir"/*; do
        [ -f "$m" ] || continue
        mp=$(cat "$m" 2>/dev/null)
        [ -n "$mp" ] && [ -d "$mp" ] || rm -f "$m" 2>/dev/null
    done
fi

marker="$marker_dir/$session_id"
if [ -n "$session_id" ] && [ -f "$marker" ]; then
    wt_path=$(cat "$marker" 2>/dev/null)
    if [ -n "$wt_path" ] && [ -d "$wt_path" ]; then
        wt_name=${wt_path##*/}
        wt_port=$(grep -m1 '^WORKTREE_HTTP_PORT=' "$wt_path/.env" 2>/dev/null | cut -d= -f2)
        wt_part=$(printf '\033[01;36m%s\033[00m' "$wt_name")
        [ -n "$wt_port" ] && wt_part=$(printf '%s:\033[01;32m%s\033[00m' "$wt_part" "$wt_port")
    fi
fi

# Assemble left side
left=""
[ -n "$context_part" ] && left="$context_part"
[ -n "$rate_part" ] && left="${left}${left:+ }${rate_part}"
[ -n "$codex_part" ] && left="${left}${left:+ }${codex_part}"

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
