#!/usr/bin/env bash
# Reports how far behind en_US every lang/*_ai locale is, and writes each locale's before-dump
# and work list to $OUT so a translation pass can start from them directly.
#
#   sh status.sh                 # read-only: counts empty in-scope keys + keys missing entirely
#   sh status.sh --sync          # also runs localization:sync per locale first (adds the missing
#                                # stubs to lang/<locale>/ - the first step of an actual pass)
#   sh status.sh [--sync] de_DE_ai fr_FR_ai   # restrict to some locales
#
# Run from anywhere inside the checkout whose Docker `app` stack should do the PHP work.
# Exit code: 0 when every reported locale is fully up to date, 1 otherwise.
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"
SCRIPTS=.claude/skills/ai-locale-translation/scripts
OUT=${OUT:-/tmp/translate}
mkdir -p "$OUT"

SYNC=0
LOCALES=()
for arg in "$@"; do
    case $arg in
        --sync) SYNC=1 ;;
        *) LOCALES+=("$arg") ;;
    esac
done
if [ ${#LOCALES[@]} -eq 0 ]; then
    for dir in lang/*_ai; do LOCALES+=("$(basename "$dir")"); done
fi

# The scan reads en_US only; one run serves every locale.
docker compose exec -T app php artisan translate:scan \
    --exclude-files=datatables,dungeons,npcs,spells,view_admin,validation >/dev/null

STALE=0
printf '%-10s %9s %7s %11s\n' locale in_scope absent outstanding
for LOCALE in "${LOCALES[@]}"; do
    if [ $SYNC -eq 1 ]; then
        # Dump before and after the sync and prove the sync touched no existing value - it once
        # re-quoted "\'" escapes into double quotes (#4165), which the translation gate cannot see.
        docker compose exec -T app php "$SCRIPTS/dump_locale.php" "$LOCALE" > "$OUT/$LOCALE.presync.json"
        docker compose exec -T app php artisan localization:sync en_US "$LOCALE" >/dev/null
        docker compose exec -T app php -r 'opcache_reset();'
        docker compose exec -T app php "$SCRIPTS/dump_locale.php" "$LOCALE" > "$OUT/$LOCALE.before.json"
        python3 "$SCRIPTS/check_sync.py" "$OUT/$LOCALE.presync.json" "$OUT/$LOCALE.before.json" >/dev/null
    else
        docker compose exec -T app php "$SCRIPTS/dump_locale.php" "$LOCALE" > "$OUT/$LOCALE.before.json"
    fi
    summary=$(python3 "$SCRIPTS/build_worklist.py" "$LOCALE" "$OUT/$LOCALE.before.json" "$OUT/$LOCALE.worklist.json" \
        | grep '^SUMMARY ')
    in_scope=$(sed -E 's/.*in_scope=([0-9]+).*/\1/' <<<"$summary")
    absent=$(sed -E 's/.*absent=([0-9]+).*/\1/' <<<"$summary")
    outstanding=$(sed -E 's/.*outstanding=([0-9]+).*/\1/' <<<"$summary")
    printf '%-10s %9s %7s %11s\n' "$LOCALE" "$in_scope" "$absent" "$outstanding"
    [ "$outstanding" -eq 0 ] || STALE=1
done

echo "dumps and work lists: $OUT/<locale>.before.json, $OUT/<locale>.worklist.json"
exit $STALE
