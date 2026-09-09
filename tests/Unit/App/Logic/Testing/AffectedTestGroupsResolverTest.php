<?php

namespace Tests\Unit\App\Logic\Testing;

use App\Logic\Testing\AffectedTestGroupsResolver;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('AffectedTestGroups')]
final class AffectedTestGroupsResolverTest extends TestCase
{
    private const string OWN_PATH = 'tests/Unit/App/Logic/Testing/AffectedTestGroupsResolverTest.php';

    /**
     * @param string[] $expectedGroups
     */
    #[Test]
    #[DataProvider('mappedPathsProvider')]
    public function resolve_givenMappedPath_returnsItsGroups(string $path, array $expectedGroups): void
    {
        // Arrange
        $resolver = new AffectedTestGroupsResolver(base_path());

        // Act
        $result = $resolver->resolve([$path]);

        // Assert
        $this->assertFalse($result->requiresFullSuite());
        $this->assertSame($expectedGroups, $result->groups);
    }

    /**
     * @return array<string, array{string, string[]}>
     */
    public static function mappedPathsProvider(): array
    {
        return [
            'combat log service' => ['app/Service/CombatLog/CombatLogService.php', ['APICombatLog', 'CombatLog', 'CombatLogRoute', 'CorrectEvents']],
            'controller'         => ['app/Http/Controllers/Api/V1/APIDungeonRouteController.php', ['API', 'Controller']],
            'mapping model'      => ['app/Models/Mapping/MappingVersion.php', ['Mapping', 'MappingVersion']],
            'mdt logic'          => ['app/Logic/MDT/Conversion.php', ['MDT']],
            'seeder json'        => ['database/seeders/dungeondata/wow/kingsrest/enemies.json', ['AffixSeeder', 'DatabaseSeeder', 'DungeonDataSeeder', 'MapIconTypesSeeder', 'SeasonsSeeder', 'SeederHelpers']],
            'own test file'      => [self::OWN_PATH, ['AffectedTestGroups']],
            'resolver itself'    => ['app/Logic/Testing/AffectedTestGroupsResolver.php', ['AffectedTestGroups']],
        ];
    }

    #[Test]
    #[DataProvider('sharedPathsProvider')]
    public function resolve_givenSharedPath_requiresFullSuite(string $path): void
    {
        // Arrange
        $resolver = new AffectedTestGroupsResolver(base_path());

        // Act
        $result = $resolver->resolve(['app/Logic/MDT/Conversion.php', $path]);

        // Assert
        $this->assertTrue($result->requiresFullSuite());
        $this->assertSame([$path], $result->fullSuitePaths);
        $this->assertSame('', $result->toPhpUnitArguments());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sharedPathsProvider(): array
    {
        return [
            'base test case'     => ['tests/TestCase.php'],
            'test case subclass' => ['tests/TestCases/PublicTestCase.php'],
            'migration'          => ['database/migrations/2026_01_01_000000_create_things_table.php'],
            'config'             => ['config/app.php'],
            'composer.lock'      => ['composer.lock'],
            'phpunit.xml'        => ['phpunit.xml'],
            'bootstrap'          => ['bootstrap/app.php'],
            'ci setup action'    => ['.github/actions/php-ci-setup/action.yml'],
            'unmapped'           => ['app/Models/DungeonRoute.php'],
        ];
    }

    #[Test]
    public function resolve_givenMultiplePaths_returnsSortedUnionWithoutDuplicates(): void
    {
        // Arrange
        $resolver = new AffectedTestGroupsResolver(base_path());

        // Act
        $result = $resolver->resolve([
            'app/Logic/MDT/Conversion.php',
            'app/Service/MDT/MDTImportStringService.php',
            'app/Policies/DungeonRoutePolicy.php',
            'resources/assets/js/app.js',
            '',
        ]);

        // Assert
        $this->assertSame(['MDT', 'Policy'], $result->groups);
        $this->assertSame(['resources/assets/js/app.js'], $result->ignoredPaths);
        $this->assertSame('--group=MDT --group=Policy', $result->toPhpUnitArguments());
    }

    #[Test]
    public function resolve_givenOnlyIgnoredPaths_hasNothingToRun(): void
    {
        // Arrange
        $resolver = new AffectedTestGroupsResolver(base_path());

        // Act
        $result = $resolver->resolve(['resources/assets/js/app.js', 'README.md', 'sh/worktree.sh', '.github/workflows/php-tests.yml']);

        // Assert
        $this->assertTrue($result->hasNothingToRun());
        $this->assertFalse($result->requiresFullSuite());
    }

    #[Test]
    public function resolve_givenDeletedTestFile_ignoresIt(): void
    {
        // Arrange
        $resolver = new AffectedTestGroupsResolver(base_path());

        // Act
        $result = $resolver->resolve(['tests/Unit/App/Logic/Testing/DoesNotExistTest.php']);

        // Assert
        $this->assertTrue($result->hasNothingToRun());
    }

    #[Test]
    public function resolve_givenTestFileWithoutGroup_requiresFullSuite(): void
    {
        // Arrange
        $basePath = sprintf('%s/affected-test-groups-%s', sys_get_temp_dir(), uniqid());
        $path     = 'tests/Feature/UngroupedTest.php';
        File::ensureDirectoryExists(sprintf('%s/tests/Feature', $basePath));
        File::put(sprintf('%s/%s', $basePath, $path), '<?php final class UngroupedTest extends TestCase {}');

        try {
            // Act
            $result = (new AffectedTestGroupsResolver($basePath))->resolve([$path]);

            // Assert
            $this->assertSame([$path], $result->fullSuitePaths);
        } finally {
            File::deleteDirectory($basePath);
        }
    }

    #[Test]
    public function getPathGroups_givenTable_everyGroupIsCarriedBySomeTestClass(): void
    {
        // Arrange
        $existingGroups = [];
        foreach (File::allFiles(base_path('tests')) as $file) {
            preg_match_all('/^#\[Group\(\'([^\']+)\'\)\]/m', $file->getContents(), $matches);
            array_push($existingGroups, ...$matches[1]);
        }
        $existingGroups = array_unique($existingGroups);

        // Act
        $tableGroups = array_unique(array_merge(...array_values(array_filter(AffectedTestGroupsResolver::getPathGroups()))));

        // Assert
        $this->assertSame([], array_values(array_diff($tableGroups, $existingGroups)));
    }
}
