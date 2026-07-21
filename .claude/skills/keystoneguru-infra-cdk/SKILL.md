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

## Verifying a change (do this before handing off)

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

### Trap: `npx cdk synth --output DIR` silently writes nothing here
Running `npx cdk synth` (no stack selector, or `--output DIR`) exits 0 but produces **no**
cloud assembly on disk — `npx` mis-resolves the CLI and the assembly never lands. Always use
the **local** binary `node_modules/.bin/cdk` and **name the stack** (`keystoneguru-services`);
a single named stack prints its template to **stdout**, which you capture and grep. Docker is
available and not the blocker (the discord-notifier asset bundles fine).
