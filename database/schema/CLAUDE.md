# Never hand-edit the `.dump` files in this folder

`migrate-schema.dump`, `phpunit-schema.dump`, and `combatlog-schema.dump` are frozen snapshots of
the schema as it stood at the last squash (`php artisan schema:dump`), including the migrations
bookkeeping table up to whatever migration ID they were squashed at. Laravel loads a dump like this
verbatim on a fresh database, then replays every migration created *after* that squash on top of it.

That means a column (or table) you're removing must be dropped by a **migration**, never by editing
it out of the dump directly. Editing a dump to "pre-drop" a column looks correct on the diff but
breaks CI: the new drop-column migration then has nothing left to drop on a fresh DB built from that
dump, while every environment migrating forward from an older schema still has the column until the
migration runs. Real incident: #4014/#4055 (2026-08-16) — an agent stripped `legal_agreed_ms` out of
both dump files instead of just adding the migration, and cold review caught it before merge.

If a dump is ever regenerated (rare, deliberate, always its own PR), that's a fresh
`php artisan schema:dump` run reviewed on its own — not a hand-patch inside an unrelated change.
