## Test execution

This project must never run PHP, Artisan, PHPUnit, or Pest directly on the host machine.

Always run Laravel and test commands inside Docker.

Use these commands only:

- `./bin/artisan-test --filter=...`
- `docker compose exec -T app php artisan ...`

Never use these commands on the host machine, always prefix with `docker compose exec` as stated above:
- `php artisan test`
- `vendor/bin/phpunit`
- `vendor/bin/pest`
- `php ...`
