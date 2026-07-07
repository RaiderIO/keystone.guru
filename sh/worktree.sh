#!/usr/bin/env bash
#
# worktree.sh — manage isolated git worktrees, each with its own minimal Docker stack
# (app + nginx) that shares the main stack's database/redis/etc.
#
# See the `worktree-docker` skill for the full workflow and rationale.
#
# Usage:
#   sh/worktree.sh create <issue>-<slug> [base-ref]   # default base-ref: origin/master
#   sh/worktree.sh down   <issue>-<slug>              # stop the stack, keep the checkout
#   sh/worktree.sh remove <issue>-<slug>              # stop the stack and remove the worktree
#   sh/worktree.sh list                               # list worktrees and their stacks
#
set -euo pipefail

# The main stack's Compose project name and shared network (project name with dots stripped,
# suffixed with the compose network key `keystone.guru`).
MAIN_PROJECT="keystoneguru"
SHARED_NET="${MAIN_PROJECT}_keystone.guru"

# Shared service containers to attach into each worktree network, as "alias:container" pairs.
# The alias matches the service DNS name the app expects (from .env), so no .env host rewrites.
SHARED_SERVICES="
db:keystone.guru-db-prod
db-combatlog:keystone.guru-db-prod-combatlog
redis:keystone.guru-redis
reverb:keystone.guru-reverb
app-swoole:keystone.guru-app-swoole
app-assets:keystone.guru-app-assets
opensearch-node1:keystone.guru-opensearch-node1
influxdb:keystone.guru-influxdb
"

PORT_RANGE_START=8100
PORT_RANGE_END=8199

# Scoped, passphraseless deploy key (write access, RaiderIO/keystone.guru only) for non-interactive
# pushes of worktree branches. `-F /dev/null` bypasses ~/.ssh/config, which maps github.com to a
# passphrase-protected key that would otherwise hang on an askpass prompt.
DEPLOY_KEY="${KSG_WORKTREE_DEPLOY_KEY:-$HOME/.ssh/keystone_worktree_ed25519}"
WORKTREE_GIT_SSH="ssh -F /dev/null -i $DEPLOY_KEY -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
WORKTREES_DIR="$(dirname "$REPO_ROOT")/keystone.guru-worktrees"

die() {
    echo "ERROR: $*" >&2
    exit 1
}

sanitize() {
    printf '%s' "$1" | tr '[:upper:]' '[:lower:]' | tr -c 'a-z0-9_-' '-' | sed 's/-*$//'
}

project_name() {
    printf 'ksg-%s' "$(sanitize "$1")"
}

port_in_use() {
    ss -ltnH 2>/dev/null | awk '{print $4}' | sed 's/.*://' | grep -qx "$1"
}

find_free_port() {
    local port
    for port in $(seq "$PORT_RANGE_START" "$PORT_RANGE_END"); do
        if ! port_in_use "$port"; then
            printf '%s' "$port"
            return 0
        fi
    done
    die "no free host port in ${PORT_RANGE_START}-${PORT_RANGE_END}"
}

# Locate the private network Compose created for a project (robust against name sanitisation).
worktree_network() {
    docker network ls \
        --filter "label=com.docker.compose.project=$1" \
        --format '{{.Name}}' | head -n1
}

cmd_create() {
    local branch="${1:-}"
    local base="${2:-origin/master}"
    [ -n "$branch" ] || die "usage: worktree.sh create <issue>-<slug> [base-ref]"

    local project wt_path net port
    project="$(project_name "$branch")"
    wt_path="$WORKTREES_DIR/$branch"

    # Guard: the main stack must be running so the shared network + containers exist.
    docker network inspect "$SHARED_NET" >/dev/null 2>&1 \
        || die "main stack network '$SHARED_NET' not found — start the main stack first (docker compose up -d)"
    [ -f "$REPO_ROOT/.env" ] || die "no .env in main repo ($REPO_ROOT) to copy"

    # 1. Create the worktree (reuse existing branch if present, else branch off base).
    if [ -d "$wt_path" ]; then
        echo "==> worktree dir already exists: $wt_path"
    else
        mkdir -p "$WORKTREES_DIR"
        git -C "$REPO_ROOT" fetch origin --quiet 2>/dev/null || true
        if git -C "$REPO_ROOT" show-ref --verify --quiet "refs/heads/$branch"; then
            echo "==> adding worktree for existing branch '$branch'"
            git -C "$REPO_ROOT" worktree add "$wt_path" "$branch"
        else
            echo "==> adding worktree '$branch' off '$base'"
            git -C "$REPO_ROOT" worktree add "$wt_path" -b "$branch" "$base"
        fi
    fi

    # 2. Copy the main .env verbatim (shell copy — contents are never read).
    cp "$REPO_ROOT/.env" "$wt_path/.env"

    # 2b. Bootstrap: if the base branch predates this file, seed it from the main repo so the
    #     worktree can start. Only when missing — a worktree that legitimately edits it is untouched.
    if [ ! -f "$wt_path/docker-compose.worktree.yml" ]; then
        echo "==> base branch lacks docker-compose.worktree.yml — copying from main repo"
        cp "$REPO_ROOT/docker-compose.worktree.yml" "$wt_path/docker-compose.worktree.yml"
    fi

    # 2c. Seed git-ignored build artifacts from the main checkout when missing. vendor/ is required
    #     to boot artisan; the public/* assets let the browser render (blade mix() needs the
    #     manifest). Each is an independent copy the worktree can rebuild if its deps/assets change.
    echo "==> seeding build artifacts from main repo (vendor + compiled assets)"
    [ -f "$REPO_ROOT/vendor/autoload.php" ] || die "main repo has no vendor/ — run composer install there first"
    local rel
    for rel in vendor version \
        public/mix-manifest.json public/css public/js public/main.js public/resources \
        public/vendor public/webfonts public/images \
        public/VERSION public/COMMITHASH public/LASTCOMMITDATETIME; do
        if [ -e "$REPO_ROOT/$rel" ] && [ ! -e "$wt_path/$rel" ]; then
            cp -a "$REPO_ROOT/$rel" "$wt_path/$rel"
            echo "  + $rel"
        fi
    done

    # 3. Pick a free host port for this worktree's nginx.
    port="$(find_free_port)"

    # 4. Point URL vars at the worktree port (sed-replace: phpdotenv keeps the FIRST occurrence,
    #    so appending would not override), then append the Compose wiring. Only the worktree's
    #    own copy is touched.
    sed -i -E \
        -e "s#^APP_URL=.*#APP_URL=http://localhost:${port}#" \
        -e "s#^URL_HOST=.*#URL_HOST=http://localhost:${port}#" \
        "$wt_path/.env"
    cat >> "$wt_path/.env" <<EOF

# --- added by sh/worktree.sh (worktree: ${branch}) ---
COMPOSE_PROJECT_NAME=${project}
COMPOSE_FILE=docker-compose.worktree.yml
WORKTREE_HTTP_PORT=${port}
EOF

    # 5. Bring up the stack (reads COMPOSE_* + WORKTREE_HTTP_PORT from the worktree .env).
    echo "==> starting stack '$project' (nginx on :$port)"
    ( cd "$wt_path" && docker compose up -d )

    # 6. Attach the shared service containers into this worktree's private network with aliases.
    net="$(worktree_network "$project")"
    [ -n "$net" ] || die "could not find worktree network for project '$project'"
    echo "==> attaching shared services to $net"
    local pair alias container
    for pair in $SHARED_SERVICES; do
        alias="${pair%%:*}"
        container="${pair#*:}"
        if ! docker inspect "$container" >/dev/null 2>&1; then
            echo "  ! $container not found — skipping (alias $alias)"
            continue
        fi
        if docker network connect --alias "$alias" "$net" "$container" 2>/dev/null; then
            echo "  + $container (alias: $alias)"
        else
            echo "  = $container already attached (alias: $alias)"
        fi
    done

    # nginx resolves ALL upstreams (app, app-swoole, reverb) at startup and refuses to boot if any
    # is missing, so it must be (re)started only after the shared services above are attached.
    ( cd "$wt_path" && docker compose restart nginx >/dev/null 2>&1 ) || true

    # 7. First-boot init inside the worktree app (shared DB is already migrated/seeded — no seed).
    echo "==> initialising worktree app"
    ( cd "$wt_path" && docker compose exec -T app sh -lc '
        mkdir -p storage/app/public/imagecache storage/app/public/expansions storage/debugbar \
                 storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
        # php-fpm serves as www-data, but storage/ and bootstrap/cache come from the checkout owned by
        # the host user, so the web process cannot write logs/compiled-views/cache. Make them writable
        # (mirrors docker_init.sh) — without this, web requests hang while failing to log warnings.
        chown -R www-data:www-data storage bootstrap/cache
        php artisan storage:link >/dev/null 2>&1 || true
        php artisan config:clear >/dev/null 2>&1 || true
    ' ) || echo "  ! init step reported an issue — check the app container"

    cat <<EOF

==> Worktree ready.
    Branch:   $branch
    Path:     $wt_path
    Project:  $project
    URL:      http://localhost:$port

    Work in it:
      cd $wt_path
      docker compose exec -T app php artisan <cmd>
      docker compose exec -T app php artisan test --compact --filter=<name>

    Tear down when done:
      $SCRIPT_DIR/worktree.sh remove $branch
EOF
}

cmd_down() {
    local branch="${1:-}"
    [ -n "$branch" ] || die "usage: worktree.sh down <issue>-<slug>"
    local project net wt_path pair container
    project="$(project_name "$branch")"
    wt_path="$WORKTREES_DIR/$branch"
    net="$(worktree_network "$project")"

    # Disconnect the attached shared containers first — otherwise Compose cannot remove the network
    # (it's still "in use") and it lingers.
    if [ -n "$net" ]; then
        for pair in $SHARED_SERVICES; do
            container="${pair#*:}"
            docker network disconnect -f "$net" "$container" 2>/dev/null || true
        done
    fi

    if [ -d "$wt_path" ]; then
        ( cd "$wt_path" && docker compose down )
    else
        # No checkout left; tear down by project name (dummy port satisfies interpolation).
        WORKTREE_HTTP_PORT=0 docker compose -p "$project" \
            -f "$REPO_ROOT/docker-compose.worktree.yml" down 2>/dev/null || true
    fi
    # Remove the network if Compose left it behind.
    [ -n "$net" ] && docker network rm "$net" >/dev/null 2>&1 || true
    echo "==> stack '$project' stopped"
}

cmd_remove() {
    local branch="${1:-}"
    [ -n "$branch" ] || die "usage: worktree.sh remove <issue>-<slug>"
    local wt_path="$WORKTREES_DIR/$branch"

    if [ ! -d "$wt_path" ]; then
        cmd_down "$branch"
        echo "==> no worktree checkout at $wt_path"
        return 0
    fi
    # Seeded build artifacts (vendor/, public/*, version) are untracked and expected, so they must
    # not block removal — but genuine uncommitted changes to TRACKED files should.
    if [ -n "$(git -C "$wt_path" status --porcelain --untracked-files=no)" ]; then
        die "worktree '$branch' has uncommitted changes to tracked files — commit/stash first, or force:
       git -C $REPO_ROOT worktree remove --force $wt_path"
    fi
    cmd_down "$branch"

    # Containers ran as root/www-data and wrote files (storage, bootstrap/cache, .phpunit.cache, ...)
    # the host user can't delete. Restore host ownership of the whole worktree via a root container
    # (reusing the always-present keystone.guru image) so git worktree remove can delete it.
    docker run --rm -v "$WORKTREES_DIR:/wt" keystone.guru \
        chown -R "$(id -u):$(id -g)" "/wt/$branch" >/dev/null 2>&1 || true

    git -C "$REPO_ROOT" worktree remove --force "$wt_path"
    echo "==> worktree '$branch' removed"
}

cmd_push() {
    [ -f "$DEPLOY_KEY" ] || die "deploy key not found at $DEPLOY_KEY — see the worktree-docker skill for setup"
    local branch
    branch="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || true)"
    [ -n "$branch" ] && [ "$branch" != "HEAD" ] || die "not on a branch (run from inside the worktree checkout)"
    echo "==> pushing '$branch' with the scoped deploy key"
    GIT_SSH_COMMAND="$WORKTREE_GIT_SSH" git push -u origin "$branch" "$@"
}

cmd_list() {
    echo "== git worktrees =="
    git -C "$REPO_ROOT" worktree list
    echo
    echo "== running worktree stacks =="
    docker ps --filter "name=ksg-" \
        --format 'table {{.Names}}\t{{.Ports}}\t{{.Status}}' || true
}

main() {
    local cmd="${1:-}"
    shift || true
    case "$cmd" in
        create) cmd_create "$@" ;;
        down)   cmd_down "$@" ;;
        remove) cmd_remove "$@" ;;
        push)   cmd_push "$@" ;;
        list)   cmd_list "$@" ;;
        *)
            cat >&2 <<EOF
usage: worktree.sh <command> [args]

  create <issue>-<slug> [base-ref]   create a worktree + isolated Docker stack (base: origin/master)
  down   <issue>-<slug>              stop the stack, keep the checkout
  remove <issue>-<slug>              stop the stack and remove the worktree
  push   [git-push-args...]          push the current branch via the scoped deploy key
  list                              list worktrees and running stacks
EOF
            exit 1
            ;;
    esac
}

main "$@"
