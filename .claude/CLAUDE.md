# Working in the repository

## Git

Branch formats are as follows:
- `<issue number>-<slug-description-of>`
- `1234-create-the-feature`
- `2345-fix-the-issue`

- The project is under Git version control.
- Any newly created files should be staged.
- In the main checkout, commits should not be done unless explicitly asked (see the worktree
  exception below).

### Git worktrees
- By default, do every task from an isolated git worktree with its own Docker `app` stack, created
  with `sh/worktree.sh create <issue>-<slug>`. Run all commands (artisan, tests, `composer run
  fix`/`analyse`) through that worktree's `app` container from inside the worktree dir, and tear it
  down with `sh/worktree.sh remove <issue>-<slug>` when done. Only skip this when the user says to
  work directly in the main checkout.
- **The worktree and its branch are yours: you may commit, push, and open a MR without asking.**
  Commit as you go, push the branch with `sh/worktree.sh push` (uses a scoped write deploy key so no
  password is prompted), and open the MR to `development` with `gh`. This autonomy applies only to a
  worktree you created — in the main checkout, still ask before committing.
- The worktree shares the main stack's database/redis, so keep migrations non-destructive and never
  run `migrate:fresh`/`migrate:refresh` in a worktree. See the `worktree-docker` skill for details.

## Github

You can use `gh issue view <issue number> --repo RaiderIO/keystone.guru --json number,title,body,labels,comments`
to request info from Github. Any call to `gh issue view` MUST be accompanied by `--json` to prevent deprecation warnings
and the command failing.

## Command execution
- Never run PHP, Artisan, PHPUnit, or Pest directly on the host machine.
- Always run Laravel, test commands, and any other file system commands inside Docker.

For example:
- `docker compose exec -T app php ...`
- `docker compose exec -T app php artisan ...`
- `docker compose exec -T app vendor/bin/phpunit ...`

## Host Machine
- The host machine runs Windows.
- The project is set up to run in Docker, so all commands should be executed within the Docker environment.
- The project uses WSL2, so you can also run basic Linux commands (such as `mkdir` or `ls`) in the WSL2 terminal if needed.
- Do not run any commands directly on the host machine, such as Powershell commands.
- All newly created files should have LF line endings.
- Do not create new files or folders using `docker compose exec`. You will not be able to edit or remove them properly from the host machine otherwise.
- Do not use `php artisan make:` commands to create new files. Instead, create new files directly in the codebase to ensure they are created with the correct permissions and structure. This overrides the Boost guideline that recommends using `php artisan make:` and running Artisan directly on the command line.

## Finishing up your work
- After completing your work, ensure you run `composer run fix` to run PhpCsFixer and `composer run analyse` to run PhpStan to verify your work.
- `composer run fix` reformats any files with pre-existing style drift, not just the ones you changed. After running it, stage only the files you actually intended to touch (`git checkout -- <other files>` to discard the unrelated reformats) so your diff/PR stays focused.

# Project-specific conventions

## General
- `sprintf` should always be used over direct concatenation for dynamic strings.

## PHP

### Comments
- If there's existing comments in the code, prefer to keep them around if they aren't completely redundant.

### Class definition order
- traits
- constants
- static properties
- private properties
- protected properties
- public properties
- constructor
- public methods
- protected methods
- private methods
- static methods
- magic methods (like `__call` or `__get`)

## Backend (Laravel)

### Database & Eloquent
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- Every new model must also have a repository. Create the interface at `app/Repositories/Interfaces/{Domain}/{ModelName}RepositoryInterface.php`, the implementation at `app/Repositories/Database/{Domain}/{ModelName}Repository.php`, and register the binding in `app/Providers/RepositoryServiceProvider.php`. See the `repository-pattern` skill for the full convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.
- Any IDs in the post body of a request should be validated to ensure they exist in the database and are of the correct type. For example: `['user_id' => ['required', 'integer', 'exists:users,id']]`. Do not put this validation in a controller; it should be in a Form Request.
- Any IDs that are validated through an `exists` rule should also have a cached getter so that the Controller can directly get a modal instance. For example:
```php
    public function dungeon(): Dungeon
    {
        return once(fn() => Dungeon::query()
            ->where('challenge_mode_id', $this->validated('challenge_mode_id'))
            ->firstOrFail());
    }
```

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Database (migrations)
- Do not use foreign keys for migrations. This application does not use them, and they can cause issues with seeding and testing.

## Localization
- Use the `__()` helper function for localization and translation of strings. Use translation keys. For example: `__('view_common.my.folder.structure.welcome_to_the_website')`.
- The language folder exists in the root of the project. Translation files are located in `lang/{locale}/` and should be organized by relevant class name (such as `Spell` -> `spells.php`) or folder structure for views (such as `view_common` or `view_dungeon`). For example: `lang/en_US/auth.php`, `lang/en_US/dashboard.php`, etc.
- Only ever edit localization files in the `lang/en_US` directory. All other languages are handled externally.
- For blade.php files, the translation keys matches exactly the file structure and name. For example, `resources/views/common/footer.blade.php` would have translation keys like `view_common.footer.copyright`.

## Testing
- Structure every test using the Arrange-Act-Assert pattern. Arrange all necessary preconditions and inputs, Act on the object or method under test, and Assert that the expected results have occurred.
- Every test name should follow the pattern of `[functionname]_given[Condition]_returns[ExpectedResult]`. For example: `myFunction_givenValidDate_returnsTrue` or `myFunction_givenInvalidDate_throwsInvalidArgumentException`.
- Any created database records must be cleaned up using try...finally.
- Test groups should be applied with the `#[Group('...')]` attribute at the class level, not the method level (doc-comment `@group` metadata is deprecated and triggers a PHPUnit warning). For example: a `#[Group('CombatLog')]` for all tests in the `CombatLog` folder, a `#[Group('EncounterStart')]` for all tests in the `EncounterStart` file. See the `writing-tests` skill for the full testing conventions.
- A DataProvider should be placed right below the last test using it, not at the top or the bottom of the class.
