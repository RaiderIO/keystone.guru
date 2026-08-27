#!/bin/sh
# Provision dedicated phpunit schemas for the MAIN checkout's test suite (#4346).
#
# Without this, the main checkout's phpunit connection points wherever DB_PHPUNIT_DATABASE points
# — historically the live dev schema, shared with the running stack (swoole, Horizon, cron). That
# let tests write-lock live tables, mingle test data with dev data, and hang the suite forever
# when a test's LOCK TABLES waited behind a dev-stack transaction (#4345). Worktrees already get
# private schemas from sh/worktree.sh; this script gives the main checkout the same treatment.
#
# What it does (idempotent, safe to re-run after a mid-seed failure):
#   1. creates the schema on both DB servers (main + combatlog) with the same grant logic as
#      sh/worktree.sh provision-db
#   2. loads database/schema/{migrate,combatlog}-schema.dump (skipped when already loaded)
#   3. runs pending migrations and the full seed against the new schemas via docker exec env
#      overrides (container env wins over .env, so the checkout's .env is never touched)
#
# What it does NOT do: edit .env. It prints the two lines to set when done.
#
# Usage: sh/provision-phpunit-db.sh [schema-name]     (default: keystone.guru.phpunit)

set -eu

SCHEMA="${1:-keystone.guru.phpunit}"
case "$SCHEMA" in
    *[!A-Za-z0-9._-]*) echo "ERROR: invalid schema name '$SCHEMA'" >&2; exit 1 ;;
esac

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"

DB_MAIN_CONTAINER="keystone.guru-db-prod"
DB_CL_CONTAINER="keystone.guru-db-prod-combatlog"

# Root SQL against a DB server container; the password never touches the host (read from the
# container's own MYSQL_ROOT_PASSWORD), the SQL arrives via stdin. Same shape as sh/worktree.sh.
db_root_sql() { # <container> <sql>
    printf '%s\n' "$2" | docker exec -i "$1" sh -c \
        'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot -N'
}

# Load a Laravel schema dump, but only when the schema has no `migrations` table yet — artisan's
# own schema load shells out to the app image's MariaDB client, which refuses the MySQL 8 server's
# self-signed certificate (see sh/worktree.sh), so the dump is piped into the server directly.
load_schema_dump() { # <container> <dump file>
    container="$1"; dump="$2"
    [ -f "$dump" ] || { echo "ERROR: schema dump not found: $dump" >&2; return 1; }
    if [ -n "$(db_root_sql "$container" \
        "SELECT 1 FROM information_schema.tables WHERE table_schema = '$SCHEMA' AND table_name = 'migrations';")" ]; then
        echo "==> [$container] $SCHEMA already has a migrations table — skipping schema dump"
        return 0
    fi
    echo "==> [$container] loading $(basename "$dump") into $SCHEMA"
    docker exec -i "$container" sh -c \
        'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot "$1"' -- "$SCHEMA" < "$dump"
}

for container in "$DB_MAIN_CONTAINER" "$DB_CL_CONTAINER"; do
    docker inspect "$container" >/dev/null 2>&1 \
        || { echo "ERROR: $container not running — start the main stack first (docker compose up -d)" >&2; exit 1; }
    echo "==> [$container] creating schema $SCHEMA + grants"
    db_root_sql "$container" \
        "CREATE DATABASE IF NOT EXISTS \`$SCHEMA\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    # Grant every user that already has grants on the server's main schema (covers the app,
    # migration and phpunit users whatever they are named), plus MYSQL_USER as a baseline.
    main_schema="$(docker exec "$container" printenv MYSQL_DATABASE)"
    users="$(db_root_sql "$container" \
        "SELECT DISTINCT User FROM mysql.db WHERE Db = '${main_schema}';")"
    users="$users
$(docker exec "$container" printenv MYSQL_USER)"
    for u in $(printf '%s\n' "$users" | sort -u); do
        db_root_sql "$container" \
            "GRANT ALL PRIVILEGES ON \`$SCHEMA\`.* TO '$u'@'%'; FLUSH PRIVILEGES;"
    done
done

load_schema_dump "$DB_MAIN_CONTAINER" "$REPO_ROOT/database/schema/migrate-schema.dump"
load_schema_dump "$DB_CL_CONTAINER" "$REPO_ROOT/database/schema/combatlog-schema.dump"

# The migrate/combatlog_migrate connections read DB_DATABASE/DB_COMBATLOG_DATABASE — overridden
# per-exec here, which beats .env (phpdotenv never overrides real environment variables).
run_app() { # <artisan args...>
    ( cd "$REPO_ROOT" && docker compose exec -T \
        -e DB_DATABASE="$SCHEMA" -e DB_COMBATLOG_DATABASE="$SCHEMA" \
        -e DB_PHPUNIT_DATABASE="$SCHEMA" -e DB_PHPUNIT_COMBATLOG_DATABASE="$SCHEMA" \
        app php artisan "$@" )
}

echo "==> [$(date +%T)] migrating $SCHEMA (pending migrations, both DBs)"
run_app migrate --database=migrate --force
run_app migrate --database=combatlog_migrate --path=database/migrations_combatlog --force

user_count="$(db_root_sql "$DB_MAIN_CONTAINER" \
    "SELECT COUNT(*) FROM \`$SCHEMA\`.users;" 2>/dev/null || echo 0)"
if [ "${user_count:-0}" -gt 0 ]; then
    echo "==> schema already seeded ($user_count users) — skipping seed"
else
    echo "==> [$(date +%T)] seeding $SCHEMA (DungeonDataSeeder dominates — takes several minutes)"
    run_app db:seed --database=migrate --force
    echo "==> [$(date +%T)] seeding users (LaratrustSeeder: 1=admin, 2=internal_team, 3=user)"
    run_app db:seed --class=LaratrustSeeder --database=migrate
fi

echo ""
echo "==> done. Point the main checkout's test suite at the new schemas by setting in .env:"
echo ""
echo "    DB_PHPUNIT_DATABASE=$SCHEMA"
echo "    DB_PHPUNIT_COMBATLOG_DATABASE=$SCHEMA"
echo ""
echo "    (the second one redirects the combatlog connection during tests — see #4346)"
