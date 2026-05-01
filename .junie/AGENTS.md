## Command execution

This project must never run PHP, Artisan, PHPUnit, or Pest directly on the host machine.

Always run Laravel, test commands, and any other file system commands inside Docker.

Use these commands only:

- `docker compose exec -T app php artisan ...`

Never use these commands on the host machine, always prefix with `docker compose exec` as stated above:
- `php artisan test`
- `vendor/bin/phpunit`
- `vendor/bin/pest`
- `php ...`

Do not run Powershell Commands on the host machine. Use Linux commands in Docker only.


## Project details
The project is run in Docker. All files should have LF line endings.

The language folder exists in the root of the project.

## Localization
Only ever edit localization files in the `resources/lang/en_US` directory. All other languages are handled externally.

## Project preferences
- `sprintf` should always be used over direct concatenation for dynamic strings.

## Git
The project is under Git version control.

- Any newly created files should be added to the repository
