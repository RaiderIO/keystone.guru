# Keystone.guru overrides — read these first

Everything below the Boost guidelines is generated. **`.claude/CLAUDE.md` (imported on the last line
of this file) is the authority and wins over anything in the generated block.** These are the rules
the generated guidance most often gets wrong for this project:

- **Never run PHP, Artisan, PHPUnit or Pest directly on the host machine.** They go through Docker:
  `docker compose exec -T app php artisan ...`, `docker compose exec -T app vendor/bin/phpunit ...`,
  `docker compose exec -T app php artisan test --compact`.
- **Never use `php artisan make:`** to create files — under WSL it writes root-owned files the host
  cannot edit or delete (#3414). Create files directly in the codebase instead.
- **This project does not use Vite, and `npm run build` does not exist** — ignore the "Frontend
  Bundling" note below. Assets are built by `scripts/build/build.mjs`; the `package.json` scripts
  are `dev`, `development`, `watch`, `prod`, `production`.
- Useful read-only Artisan tools (host prefix still required):
  `docker compose exec -T app php artisan route:list --except-vendor --path=api`,
  `docker compose exec -T app php artisan config:show database.default`.
- New models get a factory, a seeder and a **repository** (see the `repository-pattern` skill).
  For APIs use Eloquent API Resources under `app/Http/Controllers/Api/V1/` (`api-endpoint` skill).
  Link to pages with named routes via `route()`.
- Tests are PHPUnit (never Pest), built from factories, and run in Docker with
  `docker compose exec -T app php artisan test --compact --filter=<name>`. The full conventions —
  base test case, the persistent seeded test DB, `#[Group]`/`#[Test]` attributes, naming and
  cleanup — are in the `writing-tests` skill.
