<?php

namespace App\Logic\Testing;

/**
 * Maps the paths changed by a branch onto the PHPUnit groups that can observe those changes, so a draft PR push runs
 * only those groups instead of the whole suite. Anything the table does not know falls back to the full suite: a miss
 * costs one extra CI round at undraft (where the full matrix always runs), never a red master.
 */
class AffectedTestGroupsResolver
{
    private const array COMBAT_LOG_GROUPS = ['CombatLog', 'CombatLogRoute', 'CorrectEvents', 'APICombatLog'];

    private const array CONTROLLER_GROUPS = ['Controller', 'API'];

    private const array VIEW_GROUPS = ['View', 'ViewComposers', 'Controller'];

    /**
     * Ordered mapping table; the first pattern that matches a path wins. Patterns are fnmatch() globs where `*` also
     * matches directory separators. A value of null means the path requires the full suite; an empty array means no
     * PHP test can observe the path.
     *
     * @var array<string, string[]|null>
     */
    private const array PATH_GROUPS = [
        // Shared test infrastructure, schema, configuration and CI: every test depends on these.
        'tests/TestCase*.php'    => null,
        'tests/TestCases/*'      => null,
        'tests/Traits/*'         => null,
        'tests/Attributes/*'     => null,
        'tests/Fixtures/*'       => null,
        'tests/Bootstrap.php'    => null,
        'tests/Shutdown.php'     => null,
        'database/migrations*/*' => null,
        'database/factories/*'   => null,
        'config/*'               => null,
        'bootstrap/*'            => null,
        'routes/*'               => null,
        'composer.json'          => null,
        'composer.lock'          => null,
        'phpunit.xml'            => null,
        '.env*'                  => null,
        '.github/actions/*'      => null,
        'docker-compose*'        => null,
        'app/Providers/*'        => null,
        'app/Helpers/*'          => null,
        'app/Traits/*'           => null,
        'app/Overrides/*'        => null,
        'app/Vendor/*'           => null,

        // Combat log fixtures are read by the combat log tests only.
        'tests/CombatLogs/*' => self::COMBAT_LOG_GROUPS,

        'app/Service/CombatLog/*'           => self::COMBAT_LOG_GROUPS,
        'app/Service/CombatLogEvent/*'      => self::COMBAT_LOG_GROUPS,
        'app/Logic/CombatLog/*'             => self::COMBAT_LOG_GROUPS,
        'app/Models/CombatLog/*'            => self::COMBAT_LOG_GROUPS,
        'app/Console/Commands/CombatLog*/*' => [...self::COMBAT_LOG_GROUPS, 'Console'],
        'app/Console/Commands/MDT/*'        => ['MDT', 'Console'],
        'app/Console/Commands/*'            => ['Console'],
        'app/Http/Controllers/*'            => self::CONTROLLER_GROUPS,
        'app/Http/Requests/*'               => self::CONTROLLER_GROUPS,
        'app/Http/Resources/*'              => self::CONTROLLER_GROUPS,
        'app/Http/Middleware/*'             => ['Middleware', ...self::CONTROLLER_GROUPS],
        'app/Http/View/*'                   => self::VIEW_GROUPS,
        'app/Models/Mapping/*'              => ['MappingVersion', 'Mapping'],
        'app/Service/Mapping/*'             => ['MappingVersion', 'Mapping'],
        'app/Models/Patreon/*'              => ['Patreon'],
        'app/Service/Patreon/*'             => ['Patreon'],
        'app/Logic/MDT/*'                   => ['MDT'],
        'app/Service/MDT/*'                 => ['MDT'],
        'app/Logic/Testing/*'               => ['AffectedTestGroups'],
        'app/Logic/SimulationCraft/*'       => ['SimulationCraft'],
        'app/Service/SimulationCraft/*'     => ['SimulationCraft'],
        'app/Policies/*'                    => ['Policy'],
        'app/Jobs/*'                        => ['Jobs'],
        'database/seeders/*'                => ['DungeonDataSeeder', 'DatabaseSeeder', 'SeasonsSeeder', 'AffixSeeder', 'MapIconTypesSeeder', 'SeederHelpers'],
        'resources/views/*'                 => self::VIEW_GROUPS,
        'lang/*'                            => ['Localization', 'View'],

        // Read by EnemyDisplayTypeTest, which cross-checks the JS constants against the PHP enum.
        'resources/assets/js/custom/constants.js' => ['Models'],

        // Nothing here is reachable from a PHP test. A workflow change is exercised by the run of that workflow itself;
        // only the shared setup action under .github/actions changes what every test sees.
        '.github/*'          => [],
        'resources/assets/*' => [],
        'public/*'           => [],
        'scripts/*'          => [],
        'sh/*'               => [],
        '.claude/*'          => [],
        '*.md'               => [],
        'package.json'       => [],
        'package-lock.json'  => [],
        'webpack.mix.js'     => [],
        'vitest.config.*'    => [],
    ];

    /**
     * @param string $basePath Repository root the changed paths are relative to; test files are read from here.
     */
    public function __construct(private readonly string $basePath)
    {
    }

    /**
     * @return array<string, string[]|null>
     */
    public static function getPathGroups(): array
    {
        return self::PATH_GROUPS;
    }

    /**
     * @param string[] $changedPaths Repository-relative paths, as printed by `git diff --name-only`.
     */
    public function resolve(array $changedPaths): AffectedTestGroups
    {
        $groups         = [];
        $fullSuitePaths = [];
        $ignoredPaths   = [];

        foreach (array_unique(array_filter(array_map('trim', $changedPaths))) as $path) {
            $pathGroups = $this->resolvePath($path);

            if ($pathGroups === null) {
                $fullSuitePaths[] = $path;
            } elseif ($pathGroups === []) {
                $ignoredPaths[] = $path;
            } else {
                array_push($groups, ...$pathGroups);
            }
        }

        $groups = array_values(array_unique($groups));
        sort($groups);

        return new AffectedTestGroups($groups, $fullSuitePaths, $ignoredPaths);
    }

    /**
     * @return string[]|null
     */
    private function resolvePath(string $path): ?array
    {
        foreach (self::PATH_GROUPS as $pattern => $pathGroups) {
            if (fnmatch($pattern, $path)) {
                return $pathGroups;
            }
        }

        if ($this->isTestFile($path)) {
            return $this->resolveTestFileGroups($path);
        }

        return null;
    }

    private function isTestFile(string $path): bool
    {
        return fnmatch('tests/Feature/*Test.php', $path) || fnmatch('tests/Unit/*Test.php', $path);
    }

    /**
     * A test file maps onto its own class-level #[Group] attributes. A deleted test file has nothing left to run; a
     * test file without any group is unknown to the mapping and requires the full suite.
     *
     * @return string[]|null
     */
    private function resolveTestFileGroups(string $path): ?array
    {
        $absolutePath = sprintf('%s/%s', $this->basePath, $path);
        if (!is_file($absolutePath)) {
            return [];
        }

        $contents = (string)file_get_contents($absolutePath);

        preg_match_all('/^#\[Group\(\'([^\']+)\'\)\]/m', $contents, $matches);

        return $matches[1] === [] ? null : $matches[1];
    }
}
