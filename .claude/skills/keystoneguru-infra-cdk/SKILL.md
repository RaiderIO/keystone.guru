---
name: keystoneguru-infra-cdk
description: >
  How to make and VERIFY changes to the keystoneguru-infra CDK app (the sibling
  RaiderIO/keystoneguru-infra repo that owns AWS: ECS services, queue workers, task
  sizing). Covers the repo layout, the queue-worker config surface (queue:work flags,
  --memory, LOG_LEVEL, per-worker desiredCount / cpu / memoryLimit), and the reliable
  `cdk synth` verification recipe plus the traps that silently produce no output. Use when
  editing ECS task definitions, queue workers, memory/CPU, or any CDK construct here, or
  when a worker crashes/exits (e.g. queue worker exit code 12). NOT for the app-side
  build/deploy pipeline (use deployment-pipeline) or the release changelog (create-release).
---

# keystoneguru-infra CDK

The AWS infrastructure is a **CDK (TypeScript)** app in the sibling checkout
`../keystoneguru-infra` (absolute: `/home/wouterkoppenol/Git/private/keystoneguru-infra`).
All commands below run from its `cdk/` subdir. This is a **separate repo** from keystone.guru —
its own git history; the keystone.guru worktree workflow does **not** apply. Do not commit,
push, or deploy here without explicit user say-so (infra deploys are gated; production is
never touched without an in-conversation go-ahead).

## Layout

- `cdk/bin/cdk.ts` — the app entrypoint. Instantiates **top-level stacks** directly on the
  `App`: `keystoneguru-network`, `keystoneguru-data`, `keystoneguru-config`,
  **`keystoneguru-services`** (all ECS services incl. queue workers), `keystoneguru-migrate`,
  `keystoneguru-pipeline`, `keystoneguru-monitoring`, `keystoneguru-eu-ecr-replication`.
  It is CDK Pipelines, but the ECS **service stacks are top-level, not nested in the
  pipeline** — so `keystoneguru-services` synthesizes to a plain top-level template.
- `cdk/lib/services-stack.ts` — `QUEUE_WORKER_CONFIGS` array (per-worker id / queuePrefix /
  visibilityTimeout / cpu / memoryLimit / **stagingDesiredCount**) + the `.map(...)` that
  builds each `QueueWorkerServiceDefinition`. This is where per-worker knobs live.
- `cdk/lib/constructs/services/queue-worker/queue-worker-service-definition.ts` — builds the
  staging + production `QueueWorkerStagedService` pair. Staging `desiredCount` defaults to 0
  (off, to save cost) unless `stagingDesiredCount` is set; production is always 1. `LOG_LEVEL`
  env is set here for both stages.
- `cdk/lib/constructs/services/queue-worker/queue-worker-staged-service.ts` — the **shared**
  container template for ALL queue workers: the `php artisan queue:work ...` command,
  healthcheck (`pgrep -f "php artisan queue:work"`), volume mounts. Editing the command here
  affects every worker.
- `cdk/lib/constructs/services/staged-service.ts` — base; `memoryLimit: number` (required)
  → Fargate `memoryLimitMiB`, `cpu`, etc.

## Queue-worker knobs

- **`queue:work` gets an explicit `--memory`** = `Math.floor(this.props.memoryLimit * 0.8)`
  (80% of the task's hard limit, leaving headroom so the graceful memory-limit exit fires
  before an ECS OOM-kill). Without an explicit `--memory`, Laravel defaults to **128 MB** and
  the worker exits after one heavy job. **Exit code 12 = `Worker::EXIT_MEMORY_LIMIT`, a
  graceful restart-me signal, NOT a crash** (a true OOM is exit 137 / SIGKILL). See the log:
  a `handleEnd {"result":true}` + `... DONE` immediately before the exit means the job
  succeeded and the worker hit its `--memory` ceiling.
- **`--timeout`** = `visibilityTimeout − 5s`.
- **Per-worker sizing** is in `QUEUE_WORKER_CONFIGS` (`services-stack.ts`): `cpu`,
  `memoryLimit` (MiB), `visibilityTimeout`, `stagingDesiredCount`. Thumbnail is the memory
  outlier at 3072 MiB (→ `--memory=2457`); the rest are 1024 MiB (→ `--memory=819`).
- **Turning a worker on for staging**: set `stagingDesiredCount: 1` on that worker's config
  entry. Do **not** edit the shared `desiredCount` in the service-definition — that would
  start every staging worker at once.
- **`LOG_LEVEL`** is `warning`. Raising it to `debug` makes the combat-log workers log every
  parsed event (huge CloudWatch volume + memory/IO). Only do it temporarily to investigate.

## AWS credentials: use the SSO profile, not the one in the README

There are **two** profiles for the same account, and `README.md` documents the wrong one:

- `868970774940_AdministratorAccess` — static session keys pasted into `~/.aws/credentials`.
  This is what the repo's `auth_aws` alias exports. It expires and can only be renewed by
  copy-pasting from the SSO portal. When it lapses you get
  `ExpiredToken ... GetCallerIdentity`.
- **`AdministratorAccess-868970774940`** — backed by the `RaiderIO` `sso-session` in
  `~/.aws/config`. Refresh with **`aws sso login --sso-session RaiderIO`** (needs a TTY — have
  the user run it via `! aws sso login --sso-session RaiderIO`), then
  `export AWS_PROFILE=AdministratorAccess-868970774940`. The SDK renews it automatically for
  the life of the session. Use this one.

Note `auth_aws` only exports the profile and logs into ECR — it does **not** refresh an
expired session, so it will not fix an `ExpiredToken`.

## Verifying a change (do this before handing off)

0. **Two diffs that answer different questions.** For a refactor whose whole claim is "the
   deployed resources do not change", compare **synth against synth** first — it needs no AWS
   credentials and is exact:
   ```sh
   node_modules/.bin/cdk synth keystoneguru-services --app "npx ts-node --prefer-ts-exts bin/cdk.ts" 2>/dev/null > /tmp/after.yaml
   git stash && node_modules/.bin/cdk synth ... > /tmp/before.yaml && git stash pop
   ```
   Then compare the **parsed** templates, not the raw YAML — moving code changes the order
   resources are emitted in, and `Resources` is a *map*, so ordering is meaningless to
   CloudFormation. A raw `diff` will show hundreds of lines for a no-op change. Compare the key
   sets and per-key bodies of `Resources`/`Outputs`/`Parameters` with a few lines of Python +
   `yaml.safe_load`. Expect `CDKMetadata` to differ on any construct-tree change — it is a
   deflate64 telemetry blob, not infrastructure.

1. **Build** (type-check). The project `tsconfig` target is fine; a bare `tsc <file>` is not.
   ```sh
   cd cdk && npm run build 2>&1 | grep -vE "scripts/discord-notifier|^> |^$"
   ```
   `scripts/discord-notifier/index.ts` has **pre-existing** errors (missing `@aws-sdk` deps) —
   filter them out. No output after filtering = your code compiles clean.

2. **Synth + grep the rendered CloudFormation.** This is the reliable recipe:
   ```sh
   cd cdk && node_modules/.bin/cdk synth keystoneguru-services \
     --app "npx ts-node --prefer-ts-exts bin/cdk.ts" 2>/dev/null > /tmp/services.yaml
   grep -o "\-\-memory=[0-9]*" /tmp/services.yaml | sort | uniq -c   # e.g. 819 x10, 2457 x2
   grep -A1 "Name: LOG_LEVEL" /tmp/services.yaml | grep -o "Value: [a-z]*" | sort | uniq -c
   ```
   Output is **YAML** (`Name:`/`Value:` pairs, an Environment array — not JSON), so grep with
   YAML-style patterns, not `"quoted"` JSON keys. For per-service `DesiredCount`, walk each
   `Type: AWS::ECS::Service` block and read the nearby `aws:cdk:path` to get the service name.

### Trap: `cdk diff` reports a phantom MapTilesBucketPolicy replacement
`cdk diff keystoneguru-services` (whose default is now the **change-set** method — it prints
"Hold on while we create a read-only change set...") reports, on a completely clean tree:

```
[~] AWS::S3::BucketPolicy MapTilesBucket/Policy MapTilesBucketPolicy071951A5 replace
 └─ [~] Bucket (requires replacement)
     ├─ [-] keystoneguru-services-maptilesbucket90892eb9-vgblrqsyeo46
     └─ [+] {{changeSet:KNOWN_AFTER_APPLY}}
```

**This is a false positive.** Verified 2026-07-31: the deployed and synthesized
`MapTilesBucketPolicy071951A5` are byte-identical (same `{"Ref": "MapTilesBucket90892EB9"}`,
same `Fn::GetAtt` policy document), and the deployed logical IDs match the synthesized ones.
CloudFormation renders unresolved `Ref`/`GetAtt` values in a change set as
`{{changeSet:KNOWN_AFTER_APPLY}}`, and CDK's differ reads that as a changed property — and
since `Bucket` is immutable on an `AWS::S3::BucketPolicy`, it escalates to "requires
replacement". Nothing is actually replaced.

It reproduces on `main` with no local changes, so **confirm that before believing any
replacement warning**: `git stash`/`git checkout main`, re-run, and compare.

**There is no local fix, and upgrading will not help** — tested against CLI `2.1134.0` via
`npx --yes aws-cdk@latest diff ...`, which reports it identically. Don't bundle a CLI bump
hoping it clears this.

Two things that *do* work:

1. **`--method=template`** gives the true resource-level answer here:
   ```sh
   node_modules/.bin/cdk diff keystoneguru-services --exclusively --method=template \
     --app "npx ts-node --prefer-ts-exts bin/cdk.ts"
   ```
   But note AWS documents template-method as the **less** accurate one in general: it flags any
   change to a replacement-forcing property as a replacement, even when purely cosmetic. It is
   right *in this case*, not universally. On a change with real risk, run **both** — template
   for the resource-level set, change-set for genuine replacement detection, discounting the
   `MapTilesBucketPolicy` line.
2. **Compare the deployed template to the synthesized one directly**, which is exact and
   sidesteps the differ entirely:
   ```sh
   aws cloudformation get-template --stack-name keystoneguru-services --query TemplateBody --output json
   ```
   then compare per-resource bodies against the synth (see step 0). This is what proved the
   phantom: 293 of 294 deployed resources byte-identical, the only difference `CDKMetadata`.

This matters most where the acceptance criterion *is* the diff — e.g. splitting a stage out of
`keystoneguru-services` (#44), where "the diff shows only staging removals" is the safety gate.
A known-benign phantom is exactly how a real replacement gets waved through.

Also note `cdk diff` writes its **diff body to stderr**, not stdout. `2>/dev/null` throws away
the very thing you were trying to read, and filtering stderr with `grep -v '^\s'` drops the
indented diff lines. Capture both and `sed -n '/^Stack /,$p'`.

### Trap: `npx cdk synth --output DIR` silently writes nothing here
Running `npx cdk synth` (no stack selector, or `--output DIR`) exits 0 but produces **no**
cloud assembly on disk — `npx` mis-resolves the CLI and the assembly never lands. Always use
the **local** binary `node_modules/.bin/cdk` and **name the stack** (`keystoneguru-services`);
a single named stack prints its template to **stdout**, which you capture and grep. Docker is
available and not the blocker (the discord-notifier asset bundles fine).
