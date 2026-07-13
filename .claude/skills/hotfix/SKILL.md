---
name: hotfix
description: >
  Patch a released version in place without cutting a new release: upload changed
  non-compiled files (PHP, Blade, lang, config) to the hotfix S3 bucket with
  `php artisan make:hotfix`, then redeploy the SAME version so ECS overlays them at
  container startup. Use when a staging/production bug must be fixed now and the fix
  touches no compiled assets. NOT for JS/CSS/SCSS or JS-consumed translations - those
  are baked into the release assets and require a new release (create-release skill).
---

# Hotfixing a released version

A hotfix overwrites files inside the already-deployed image at container startup, keyed by
release tag. No new tag, no asset rebuild - the deployed version string stays the same.

## What can and cannot be hotfixed

**Hotfixable** - anything read by PHP at runtime:
- `app/**`, `routes/**`, `config/**`, `database/**` PHP files
- Blade templates (`resources/views/**` - rendered at runtime, not compiled into assets)
- `lang/**` translations **used by Blade/PHP**

**NOT hotfixable** - anything baked into `compiled/<tag>/...` assets at release build time:
- `resources/assets/js/**`, `resources/assets/sass/**` (webpack bundle, immutable in
  S3/Cloudflare - see deployment-pipeline skill)
- Translations consumed by **JavaScript** (`lang.get(...)`) - exported into the JS bundle
  at build time
- The map context (`make:mapcontext*` output) and the `version` file

If the fix needs any of those, stop: cut a new release instead.

## How it works

1. `php artisan make:hotfix <version>` collects changed files and uploads each to the
   hotfix bucket (`s3_hotfixes` disk, bucket `keystoneguru-services-hotfixbucket...`) under
   the key `<version>/<repo-relative-path>` (e.g. `v15.4.1/app/Http/Requests/Foo.php`).
2. On every ECS deploy, the bootstrap (init) container
   (`keystoneguru-infra: cdk/lib/constructs/app-code-bootstrap-container.ts`) runs
   `aws s3 cp s3://<hotfix-bucket>/<tag>/ ... --recursive` and then `cp -a` the result over
   `/var/www/html/` **before** the app containers start. Nginx's `/tmp/app-code` copy is
   made after the overlay, so hotfixed files are picked up everywhere.
3. Because keys are prefixed by tag, a hotfix only ever applies to deploys of that exact
   version. The next release ignores it.

So a hotfix only takes effect after **redeploying the same version**.

## File selection - read this before running the command

`MakeHotfix` finds files via `git diff --name-only HEAD` - i.e. **every tracked file that
differs from HEAD, staged or unstaged**. Consequences:

- A **new** file must be `git add`ed first, or it is invisible to the command.
- **Unrelated working-tree edits get uploaded too.** Either keep the tree clean, or upload
  surgically with `--file` (one file per invocation):
  `php artisan make:hotfix v15.4.1 --file=app/Http/Requests/Foo.php`
- Deleted files are skipped - you cannot hotfix-remove a file (the overlay only adds/replaces).
- Test files don't need to be uploaded (nothing runs them in the deployed containers) -
  prefer `--file` runs for just the production code.

## Runbook

1. **Confirm the checkout matches the deployed release.** The uploaded content is whatever
   is on disk; it must be based on the deployed tag:
   `git tag --points-at HEAD` should include the version being hotfixed (or check out the
   tag first if master has moved on).
2. Make the fix in the main checkout (this is the exception to the worktree default - the
   command reads this checkout's git state). Write/adjust a test, run it, and prove it
   fails without the fix (`git stash push -- <files>` / run / `git stash pop`).
3. Stage everything you created/changed; run `composer run fix` and `composer run analyse`.
4. Upload, inside the app container (needs the release row in the local DB and AWS creds
   in the container env - both are present in the dev stack):
   ```sh
   docker compose exec -T app php artisan make:hotfix v15.4.1 --file=app/Http/Requests/Foo.php
   ```
   Repeat per file. The version argument includes the `v` prefix and must exist in the
   `releases` table ("Release not found!" otherwise).
5. **Verify the upload**:
   ```sh
   docker compose exec -T app php artisan tinker --execute \
     'print_r(Storage::disk("s3_hotfixes")->allFiles("v15.4.1"));'
   ```
6. **Establish a RED baseline on the target environment BEFORE redeploying.** Reproduce the
   bug against staging/production with a deterministic request (e.g. curl with the exact
   failing POST payload - fetch the page for the CSRF token + session cookie first). A green
   result later proves nothing unless this step was red: a scripted browser flow can silently
   sidestep the bug (this exact trap produced a false "verified on staging" for the v15.4.1
   hotfix - the headless form omitted the broken field entirely and passed on unpatched code).
7. **Redeploy the same version.** Staging:
   ```sh
   gh api repos/RaiderIO/keystoneguru-infra/dispatches \
     -f event_type=deploy-staging -f 'client_payload[version]=v15.4.1'
   ```
   Watch it: `gh run list --repo RaiderIO/keystoneguru-infra --limit 3` /
   `gh run view <id> --repo RaiderIO/keystoneguru-infra`.
   **Production** uses `event_type=deploy-production` - never dispatch it without Wotuu
   explicitly saying so in the current conversation.
8. **Confirm containers actually ROLLED - a green workflow is not enough.** The version is a
   deploy-time CloudFormation parameter, so a same-version redeploy synthesizes an identical
   template; without the per-stage deploy-nonce (infra PR #34), `cdk deploy` reports
   `keystoneguru-services (no changes) / 0s` and **nothing restarts - the hotfix is not
   applied**. Check the CDK step output for that no-op signature, and verify ECS task
   `startedAt` timestamps changed / the bootstrap container logged "Downloading the following
   hotfixes" with your file list. If it no-op'd, force a roll
   (`aws ecs update-service --force-new-deployment` per service, or ask Wotuu).
9. **Verify GREEN with the exact same request as step 6** on the target environment.
10. **Land the fix permanently.** The hotfix dies with the version tag: commit the same
   change to `master` through the normal flow (in the main checkout: leave it staged for
   Wotuu to commit, or move it to an issue branch/MR when asked) so the next release
   contains it.

## Gotchas

- The S3 keys are never cleaned up automatically; re-running `make:hotfix` for the same
  version overwrites keys, which is fine. Stale hotfixes for old tags are inert.
- The overlay happens on **every** container start of that version (ECS restarts included),
  so a bad hotfix keeps coming back until you overwrite the key or deploy a new version.
- `make:hotfix` must run where git can see the changes - the app container's `/var/www`
  bind mount of the main checkout. Do not run PHP on the host.
- Local `mapping:sync` cron drift (dungeondata seeder JSONs) shows up in `git diff HEAD`
  and would be swept into a bucket-wide (no `--file`) upload - another reason to prefer
  `--file`.
