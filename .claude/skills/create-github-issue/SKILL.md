---
name: create-github-issue
description: Use when the user wants to create, file, open, or raise a GitHub issue / story / ticket for this project (e.g. a follow-up task, bug report, or tech-debt item). Covers the reliable `gh` command pattern, shell-escaping pitfalls, and the project's issue conventions. Do not use when creating merge/pull requests.
---

# Create a GitHub Issue

Reliable workflow for filing a GitHub issue in this repo with the `gh` CLI. The recurring
friction is **shell-escaping the issue body** — solve it with a quoted heredoc and you're done.

## Repo facts

- Repo: `RaiderIO/keystone.guru`
- `gh` is authenticated (user `Wotuu`, ssh). Verify once with `gh auth status` if unsure.
- Always pass `--repo RaiderIO/keystone.guru` explicitly so it never depends on cwd.

## The command pattern (use this)

Write the body with a **single-quoted heredoc** (`<<'EOF'`). The quotes around `EOF` stop the
shell from interpreting `$`, backticks, `!`, etc. inside the body — so prose like `$with`,
`Model::$casts`, `$variable`, command snippets, and `#1234` all pass through literally. This is
the single most important trick; without it you'll fight escaping.

```bash
gh issue create --repo RaiderIO/keystone.guru \
  --title "Short imperative title (follow-up to #NNNN)" \
  --body "$(cat <<'EOF'
Body goes here. $with, `code`, and #3300 references are all safe inside <<'EOF'.
EOF
)"
```

The command prints the new issue URL on success (e.g. `https://github.com/RaiderIO/keystone.guru/issues/3302`).
**Always relay that URL back to the user.**

## Escaping rules

- **Body**: inside `<<'EOF' ... EOF'` nothing is interpreted — write plain Markdown, no escaping.
- **Title**: it's a normal double-quoted shell arg, so a literal `$` must be escaped as `\$`
  (e.g. `--title "Reduce \$with N+1"`), or just avoid `$`/backticks in the title.
- Do **not** try to cram a long body inline with `\n` or nested quotes — that's the path that
  wastes time. Use the heredoc.

## Conventions for this project

- **Reference related issues** with `#NNNN` in the body; GitHub auto-links them. If it's a
  follow-up, say so in the title: `(follow-up to #NNNN)`.
- Commits/branches use the issue number prefixed with `#` (e.g. `#3300 Fix ...`, branch
  `3300-some-slug`). Mention the issue number when you later open a PR.
- Optional flags when relevant: `--label "<label>"`, `--assignee "@me"`, `--milestone "<name>"`,
  `--project "<name>"`. Only add labels that already exist (`gh label list --repo RaiderIO/keystone.guru`).

## Recommended issue body structure

For a substantive task/follow-up, make it resumable cold. Include:

1. **Background** — what's already done (link commits/PRs/issues), with concrete before/after data.
2. **Remaining work** — the specific change, with file paths and line hints.
3. **Risks / why it's non-trivial** — blast radius, gotchas, things that break.
4. **Recommended approach** — step-by-step, one safe change at a time.
5. **How to measure / verify** — exact commands, and any measurement gotchas to avoid.
6. **Key files** — paths the implementer will touch.
7. **Definition of done** — tests green (call out any *known pre-existing* failures to ignore),
   `composer run analyse` + `composer run fix` clean, manual verification steps.

## Editing an existing issue's body from a file

If the body is long (e.g. a plan you already wrote to a scratch file), don't reach for
`gh api ... -f body=@path`. Use:

```bash
gh issue edit 3374 --repo RaiderIO/keystone.guru --body-file /path/to/body.md
```

`gh issue edit`/`gh issue create --body-file` read the file's actual contents — no `@` prefix
needed there.

### Gotcha: `gh api -f` vs `-F` (this bit us — issues #3374, #2460, #1431, #1453, #128 all
shipped with a body that was the *literal string* `@/tmp/.../file.md` instead of that file's
contents)

If you do use `gh api` directly to PATCH an issue body:

- `-f body=@/path/to/file` — lowercase `-f` is a **string** field. The `@`-prefixed value is
  sent verbatim as the literal text `@/path/to/file`, it does **not** read the file. This is the
  mistake that happened.
- `-F body=@/path/to/file` — capital `-F` is a **typed** field; only this form dereferences
  `@file` into the file's contents.

Prefer `gh issue edit --body-file` over `gh api` for this reason — it has no `-f`/`-F` trap to
get wrong.

## After creating (or editing)

- Relay the URL to the user.
- New files in this repo should be staged (`git add`) per project rules — but the issue itself
  lives on GitHub, so there's nothing to commit unless you also wrote local files.
- **Verify the body actually landed**: `gh issue view <n> --repo RaiderIO/keystone.guru --json body -q '.body[0:80]'`
  and confirm it's prose, not a bare local path (`/tmp/...`, `@/...`) or anything else that would
  only make sense on your own machine — the GitHub equivalent of telling someone "check out my
  site at http://localhost:80". A scratch-file path in a body is *never* correct; catch it before
  the user does.
