---
name: codex-delegation
description: Route delegable work to the Codex CLI via sh/codex.sh instead of spending Claude tokens on it — repo research and code archaeology, long log/CI-failure digestion, cold review of a plan before implementing, and mechanical fix loops (PHPStan, test backfill). Use when you are about to dispatch an Explore or general-purpose subagent, read a long CI log or test dump, sanity-check a plan you wrote, or grind a repetitive verifiable loop. Also covers the kill switch for when Codex usage runs out and everything must go back to Claude. Not for cold-reviewing a PR (cold-reviewer-codex agents) and not for work that depends on this conversation's history.
---

# Delegating to Codex

Wotuu pays for a Codex subscription alongside Claude and it sits mostly idle. Moving work onto it is
a cost decision, not a quality one: **prefer Codex wherever it is as good as Claude, and stay on
Claude where it isn't.** Both halves of that sentence are load-bearing.

The mechanism is one wrapper — `sh/codex.sh` — because a single choke point is what gives the whole
thing a kill switch, a secrets preamble, and one place that notices Codex has run out of usage.
**Never call `codex` directly.**

## The core arbitrage

A Claude `Explore`/`general-purpose` subagent already keeps file dumps out of your context — but it
still bills Claude. Swapping it for `sh/codex.sh ask` returns you the same-sized summary and moves
the bill. Your context cost is identical; the token cost is not.

So the trigger to reach for this is not "is this hard" — it's **"is the work token-heavy and
judgement-light?"** Reading 60 files to answer one question is exactly that shape. Deciding what to
do with the answer is not.

## Delegate by default

| Work | Mode | Why it's safe to move |
|---|---|---|
| Repo research: "where does X live", "how does Y work", "does Z exist anywhere" | `ask` | No project conventions needed; the answer is verifiable by opening the cited file |
| Digesting a long CI log, PHPUnit dump, or `composer run analyse` output | `ask` | Pure summarisation of text you'd otherwise page into context |
| Cold read of a plan/issue body **before** implementing it | `ask` | Wins twice — saves tokens *and* Codex can't see which assumption you already talked yourself into |
| PHPStan / PhpCsFixer fix loops | `write` | Mechanically verifiable — rerun `composer run analyse` |
| Test backfill for an untested class | `write` | The suite is the oracle |
| Machine-generated diffs (`lang/**`, seeder JSON) | `ask` | `AGENTS.md` already says skim these, not review them |
| First-pass Sentry triage: stack trace → hypothesis | `ask` | You still decide the fix |

You do not need to ask permission to delegate any of these. Wotuu's standing instruction is to shift
usage onto Codex automatically and only surface it when a specific command needs running by hand.

## Keep on Claude

- **Anything that depends on this conversation's trajectory.** Codex sees a snapshot; the `advisor`
  sees where you've been. Course-correction is not delegable.
- **Cold-reviewing a PR** — that already has its own path (`cold-reviewer-codex` /
  `cold-reviewer-codex-adversarial` agents), which posts the marker comment and `pr cold reviewed`
  label `babysit-prs` checks for. Don't hand-roll it through `sh/codex.sh`.
- **The private security review.** That report is deliberately not public
  (`project_security_review_private`); don't feed it, or the reasoning around it, to a third party.
- **Talking to the user.** Codex output is an input to your answer, never the answer itself.

## Running it

```bash
sh/codex.sh ask   'question'                  # read-only, no network, NO Docker
sh/codex.sh write 'task'                      # may edit files, and CAN run Docker
sh/codex.sh ask - < prompt.txt                # long prompt on stdin
sh/codex.sh ask --timeout 420 --cd <dir> '…'  # default timeout is 900s
```

### `ask` cannot run anything; `write` can

Every PHP command in this project lives inside Docker, and Codex's sandbox blocks the Docker daemon
socket unless network access is granted — an exemption that exists **only in `write` mode**. The
failure is easy to misread: it surfaces as `permission denied while trying to connect to the docker
API`, which looks like a unix permission problem but isn't (the socket is world-writable and Docker
works fine outside the sandbox). `read-only` has no network toggle at all, so there is no way to
give `ask` a test run.

So pick the mode by **what the task must prove**, not by whether you want files changed:

- Needs only reading → `ask`.
- Needs to run tests, PHPStan, or artisan → `write`, even if you expect no edits.

`ask` is told plainly that it has no Docker and must not report the resulting error as a code
finding; `write` is given the `docker compose exec -T app …` invocations and told to state which
commands it actually ran.

**The cost: the Docker exemption is full internet egress, not a socket exemption.** Narrower routes
were tried and all fail (`writable_roots` and `--add-dir` both die inside bwrap with `Can't mkdir
/run/.git`), so it's Docker-with-egress or no Docker. Pass `--no-network` on any `write` run that
doesn't actually need to execute something, and prefer `ask` whenever the task only reads.

Only Codex's **final message** hits stdout; the full transcript goes to a log path printed on
stderr, so your context pays for the answer and nothing else.

**Run it with `run_in_background: true`.** A research run takes minutes and a foreground call blocks
the session for all of it.

### Prompt discipline — this is what decides whether it works

The first recorded run in this setup spent five minutes reading everything in sight and timed out
without answering, because it was pointed at a directory and given a vague question. Codex does not
idle: an unscoped prompt becomes an unscoped exploration.

- **Bound the search.** "Do not read anything outside `routes/console.php` and the classes it
  references" is worth more than any amount of prompt politeness.
- **Ask for a named artefact** — a table, a list of file:line, a yes/no with evidence. "Investigate
  X" is not a task.
- **Say what you already know**, so it doesn't re-derive it.
- The wrapper's preamble already handles secrets, network absence and output shape. Don't repeat
  those; spend your prompt on scope.

### Verify `write` runs — always

Codex reads `AGENTS.md` but **not** the project's `.claude/skills/`, so it has the environment
contract and not the domain conventions. `AGENTS.md` points at the skills catalogue, but that's an
instruction, not a guarantee. Treat every `write` run as an untrusted patch:

- `git diff` it yourself before staging.
- Run the actual verification the task claims to satisfy (`composer run analyse`, the test filter)
  in Docker, from this checkout — don't take Codex's word that it passed.
- This is why `write` is scoped to tasks with a mechanical oracle. If you can't cheaply prove the
  result, do it on Claude.

## The cold reviewer cannot run Docker, and that is not a bug to fix

`cold-reviewer-codex` reviews routinely end with a line like *"Docker-based tests could not be run
because access to the Docker daemon was unavailable."* That is expected and needs no action.

`runAppServerReview` in the Codex plugin's `scripts/lib/codex.mjs` **hardcodes `sandbox:
"read-only"`** and exposes no option to change it, and read-only mode has no network toggle — so the
built-in reviewer structurally cannot reach Docker. The only ways round it are patching a vendored
plugin file (wiped on every plugin update) or abandoning Codex's built-in review engine, and neither
is worth it: `AGENTS.md` already tells reviewers that running the suite is optional and that a
review which couldn't run tests should say so plainly. **Don't re-investigate this**, and don't
treat the line as a review failure or a reason to re-run.

If a specific change genuinely needs Codex to execute the suite, that is a `sh/codex.sh write` task,
not a cold review.

## When Codex runs out — the kill switch

```bash
sh/codex.sh status        # is it on? if off, why?
sh/codex.sh off [reason]  # everything goes back to Claude
sh/codex.sh on            # resume delegating
```

State lives outside the repo (`~/.config/keystone-guru/codex-delegation`), so flipping it doesn't
dirty a worktree and one flip covers the main checkout and every worktree at once.

**Exit code 3 means "Codex is unavailable — do this work on Claude, and stop routing work to
Codex."** Treat it as a state change, not a single failed call: finish the current task on Claude
and don't retry the wrapper for the rest of the session unless the user turns it back on.

The switch also flips itself. On a failure that looks like exhausted usage or rejected auth, the
wrapper records the reason and turns delegation off, so the *first* refusal moves the machine back
to Claude instead of every later call rediscovering it. `sh/codex.sh status` shows what happened;
`sh/codex.sh on` clears it.

Any other non-zero exit is an ordinary failed run — the wrapper prints the tail of the transcript.
Read it, fix the prompt, retry once. If it fails twice, do the work on Claude and say so rather than
grinding.

## Secrets

Codex is cloud-backed: everything it reads leaves the machine. The repo's source is public, so the
code isn't the concern — `.env` and `storage/` are. The wrapper forbids them on every run and
`AGENTS.md` repeats it, but a read-only sandbox can still read any file it likes, so that's an
instruction and not a guarantee.

Practical rule: **never point `--cd` at a directory holding a secret a leak would permanently burn,
and treat everything in the working directory as published.** This has bitten before — a scratch
directory left holding an API history dump got read wholesale on an unrelated run.
