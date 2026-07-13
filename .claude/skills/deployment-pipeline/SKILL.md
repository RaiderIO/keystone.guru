---
name: deployment-pipeline
description: >
  Map of how keystone.guru builds and deploys — the tag-triggered GitHub Actions pipeline
  (frontend assets, map context, Docker images), the cross-repo deploy into
  RaiderIO/keystoneguru-infra (Cloudflare + S3 assets, ECR/ECS, staging/production gates),
  and the operational gotchas. Use when the user asks how deploys/assets/CI work, why an
  asset 404s, how staging/production deploy, or where a pipeline step lives. NOT for
  authoring a release changelog (use create-release) and NOT the legacy PHP-Deployer flow.
---

# Deployment pipeline

How code and assets reach staging/production. This is a **map + gotchas**, not a line-by-line
transcript — the pipeline is under active rework (#3320 "automate everything", #3331
decommission legacy PHP-Deployer), so **verify against the current workflow files** before
asserting details.

Two repos:
- `RaiderIO/keystone.guru` (this repo) — builds artifacts, dispatches deploys.
- `RaiderIO/keystoneguru-infra` (sibling checkout at `../keystoneguru-infra`) — the CDK app
  that owns AWS (ECR, ECS, S3, the deploy workflow). It receives `repository_dispatch` events.

## The one mental model to hold

**Everything is keyed by the release tag string** (e.g. `v15.2.3`), not a commit hash
(changed in #3320; before that it was the commit hash and caused asset 404s when the
tag commit ≠ the master merge commit).

The single source of truth at runtime is the **`version` file** at the app root. It is baked
into the image and read agnostically by `ksgCompiledAsset` (`app/Helpers/CustomHelper.php`),
`SavesToFile` (map context), and `ViewService`. Whatever string it contains becomes the
`compiled/<version>/...` path for every asset and map-context request.

## Trigger map (this repo, `.github/workflows/`)

- **`release-deploy.yml`** — on `push` tag `v*`. The whole deploy. Jobs:
  - `build-assets` — `npm --output_version=<tag> run production`; uploads
    `public/{js,css,webfonts}` → `s3://<bucket>/compiled/<tag>/...`.
  - `build-map-context` — `php-ci-setup`, then **overwrites the `version` file with the tag**,
    runs `make:mapcontext*`, uploads `storage/mapcontext` → `s3://<bucket>/compiled` (lands at
    `compiled/<tag>/mapcontext/...`).
  - `build-push-images` — builds the app + worker + cron images, pushes to ECR tagged `<tag>`.
    The app image (`docker-compose/app-aws`) goes to `ksg-php-fpm`/`ksg-reverb`/`ksg-swoole`; the
    worker image (`docker-compose/app-worker` → `docker-compose/app-worker-aws`, the only one with
    Puppeteer/Chrome) goes to `ksg-queue-worker`. The image's `version` file is written from
    `GIT_REF` in the respective `*-aws/Dockerfile`.
  - `deploy-staging` — `needs` all three build jobs; dispatches `deploy-staging` to infra.
  - `deploy-production` — `needs: deploy-staging`; **gated by the `production` GitHub
    Environment** (required reviewers). Dispatches `deploy-production` to infra on approval.
- **`php-tests.yml` / `js-tests.yml` / `phpstan.yml`** — on `pull_request` only (push trigger
  was removed in #3320 to kill duplicate runs). Share the composite action
  `.github/actions/php-ci-setup` (installs PHP/Lua/Rust deps, migrates+seeds, writes the
  `version` file from `git rev-parse HEAD`).

There is **no** push-to-branch deploy. Deploys only happen on a tag (and the infra side).

## The cross-repo deploy (`keystoneguru-infra`)

- `deploy-staging`/`deploy-production` here `curl` a `repository_dispatch` to infra with
  `client_payload.version = <tag>` (needs the `INFRA_DISPATCH_TOKEN` secret — the default
  `GITHUB_TOKEN` can't dispatch cross-repo).
- Infra `.github/workflows/deploy.yml` writes that version into SSM `/ksg/<stage>/version`;
  the CDK app builds every ECS service image URI as `repo:<version>`, so ECS pulls the
  matching ECR image.
- Assets: `cdk/lib/services-stack.ts` — `AssetsBucket` is an S3 **static-website** bucket
  fronted by **Cloudflare** at `assets.keystone.guru` (NOT CloudFront). The GitHub OIDC role
  (`arn:aws:iam::868970774940:role/ksg-github-actions`) is granted read/write on `compiled/*`.

## Full happy path

1. Author the release notes and create the draft GitHub Release + release issue (see
   `create-release`). Release notes live on the GitHub Release, not committed to `master`.
   Feature work is already on `master` — there is no release MR to merge under the trunk model.
2. `git tag -a v<X.Y.Z> <sha> && git push origin v<X.Y.Z>` cuts the tag **on master** (the tag
   runs `release-deploy.yml` *as it exists at the tagged commit*).
3. Tag push → 3 build jobs in parallel → `deploy-staging` auto → staging ECS rolls (~5-6 min).
4. Approve the `production` environment gate when staging looks good → production deploys the
   same image + assets.

## Verify a release end-to-end

Preferred: `sh/release-watch.sh <tag>` (or no argument for the newest release-deploy run) —
it does all of the below itself in a re-entrant polling loop, correlates the infra runs, and
can drive the production gate. See the `release-watch` skill. The manual steps below are
useful for spot-checks or when reasoning about a single piece.

```bash
# Assets (the thing that used to 404) — expect 200:
curl -sI https://assets.keystone.guru/compiled/<tag>/js/app-<tag>.js
curl -sI https://assets.keystone.guru/compiled/<tag>/css/app-<tag>.css
curl -sI https://assets.keystone.guru/compiled/<tag>/mapcontext/static/en_US.js

# Watch the run (jobs, not overall status — see gotcha):
gh run view <run-id> --repo RaiderIO/keystone.guru

# Confirm staging flipped to the new version (poll its HTML):
curl -s https://staging.keystone.guru/ | grep -oE "compiled/[^/\"']+/js/app-[^\"']+\.js" | head -1
```

## Gotchas (the expensive-to-rediscover ones)

- **Immutable + tag-keyed assets.** Uploaded with `cache-control: immutable` behind Cloudflare.
  So **never re-cut an existing tag** with different content, and a **JS/CSS change requires a
  new release** (new tag). The PHP-only hotfix path (mirror files into S3 and overwrite on the
  running container, no tag change) does **not** refresh compiled JS/CSS.
- **Dry-run without burning a version:** push a throwaway `v<X.Y.Z>-rc1` tag off `master`
  (it already carries the current `release-deploy.yml`). It also auto-deploys to staging.
- **Ordering:** cut the real tag only after the changelog commit is on `master`, else the tag runs master's
  older workflow (missing the asset/map-context jobs).
- **Pushing workflow changes:** the `gh` OAuth token lacks `workflow` scope, so an HTTPS push
  that touches `.github/workflows/*` is rejected. The user must push over SSH (`! git push ...`),
  or `gh auth refresh -h github.com -s workflow`.
- **`gh pr edit` is broken on this repo** (deprecated classic-Projects GraphQL). Edit PR bodies
  via `gh api --method PATCH repos/RaiderIO/keystone.guru/pulls/<n> -F body=@<file>`.
- **Run status parks on `waiting`** while `deploy-production` sits on the gate — an
  `until [ status = completed ]` poll hangs forever. Poll **job** conclusions instead.

## Key files

- This repo: `.github/workflows/release-deploy.yml`, `.github/actions/php-ci-setup/action.yml`,
  `docker-compose/app-aws/Dockerfile` (the `version` file), `app/Helpers/CustomHelper.php`
  (`ksgCompiledAsset`), `app/Console/Commands/MapContext/*`.
- Infra: `cdk/lib/services-stack.ts` (buckets, OIDC grant), `.github/workflows/deploy.yml`
  (dispatch → SSM → ECS).
