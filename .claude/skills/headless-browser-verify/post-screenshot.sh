#!/usr/bin/env sh
# Post a verification screenshot onto a GitHub PR/issue.
#
# GitHub has no image-upload API, but on a *public* repo an image renders in
# markdown from any raw URL. This hosts the PNG on a dedicated orphan branch
# (`verification-screenshots` by default) that carries no code and is never
# merged, then prints the raw.githubusercontent.com URL to embed in the PR body:
#
#   ![verification](<printed url>)
#
# Usage:
#   post-screenshot.sh <local-png> <dest-path-on-branch>
#   e.g. post-screenshot.sh .chrome-tmp/shot.png pr/3471-search.png
#
# First call creates the orphan branch; later calls append to it.
#
# Requires: gh (authenticated), jq, base64. Public repo only — private-repo raw
# URLs do not render in GitHub markdown.
set -eu

REPO="${KSG_REPO:-RaiderIO/keystone.guru}"
BRANCH="${KSG_SCREENSHOT_BRANCH:-verification-screenshots}"

if [ $# -ne 2 ]; then
    echo "usage: post-screenshot.sh <local-png> <dest-path-on-branch>" >&2
    exit 2
fi
PNG="$1"
DEST="$2"

tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

# Upload the PNG as a git blob (base64; avoids the shell arg-length limit).
base64 -w0 "$PNG" > "$tmp/b64"
jq -n --rawfile c "$tmp/b64" '{content:$c, encoding:"base64"}' > "$tmp/blob.json"
blob="$(gh api -X POST "repos/$REPO/git/blobs" --input "$tmp/blob.json" --jq .sha)"

if parent="$(gh api "repos/$REPO/git/ref/heads/$BRANCH" --jq .object.sha 2>/dev/null)"; then
    # Branch exists: append the file on top of the current commit/tree.
    base_tree="$(gh api "repos/$REPO/git/commits/$parent" --jq .tree.sha)"
    jq -n --arg sha "$blob" --arg dest "$DEST" --arg bt "$base_tree" \
        '{base_tree:$bt, tree:[{path:$dest, mode:"100644", type:"blob", sha:$sha}]}' > "$tmp/tree.json"
    tree="$(gh api -X POST "repos/$REPO/git/trees" --input "$tmp/tree.json" --jq .sha)"
    jq -n --arg t "$tree" --arg p "$parent" \
        '{message:"Add verification screenshot", tree:$t, parents:[$p]}' > "$tmp/commit.json"
    commit="$(gh api -X POST "repos/$REPO/git/commits" --input "$tmp/commit.json" --jq .sha)"
    gh api -X PATCH "repos/$REPO/git/refs/heads/$BRANCH" -f sha="$commit" >/dev/null
else
    # First screenshot: create a parentless (orphan) branch with only this file.
    jq -n --arg sha "$blob" --arg dest "$DEST" \
        '{tree:[{path:$dest, mode:"100644", type:"blob", sha:$sha}]}' > "$tmp/tree.json"
    tree="$(gh api -X POST "repos/$REPO/git/trees" --input "$tmp/tree.json" --jq .sha)"
    jq -n --arg t "$tree" \
        '{message:"Verification screenshots (orphan asset branch, never merged)", tree:$t, parents:[]}' > "$tmp/commit.json"
    commit="$(gh api -X POST "repos/$REPO/git/commits" --input "$tmp/commit.json" --jq .sha)"
    gh api -X POST "repos/$REPO/git/refs" -f ref="refs/heads/$BRANCH" -f sha="$commit" >/dev/null
fi

echo "https://raw.githubusercontent.com/$REPO/$BRANCH/$DEST"
