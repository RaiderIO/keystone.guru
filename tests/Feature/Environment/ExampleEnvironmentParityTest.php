<?php

namespace Tests\Feature\Environment;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Guards that the three example environment files declare the same variables (#4491).
 *
 * Since #4485 these files are the complete starting point for their environment - nothing tops the environment
 * up at runtime any more. A variable missing from one is therefore silently absent for whoever starts from that
 * file, and only surfaces much later as a config default quietly taking effect.
 *
 * A commented-out declaration counts as declared: several variables must not be set to an empty string, because
 * Laravel's env() returns '' rather than the declared default for those, so documenting the name while leaving
 * it unset is the correct way to carry them.
 */
#[Group('Environment')]
final class ExampleEnvironmentParityTest extends TestCase
{
    private const REFERENCE_FILE = '.env.example';

    /**
     * Variables docker-compose.yml interpolates for itself. They are never read by the application, so they
     * belong only in the file a Docker developer copies to .env.
     *
     * @var array<int, string>
     */
    private const DOCKER_COMPOSE_ONLY = [
        'DB_DATA_VARIANT',
        'PGID',
        'PUID',
    ];

    /**
     * @return array<string, array{string, array<int, string>}>
     */
    public static function comparedEnvironmentFileProvider(): array
    {
        return [
            '.env.ci.example'     => ['.env.ci.example', []],
            '.env.docker.example' => ['.env.docker.example', self::DOCKER_COMPOSE_ONLY],
        ];
    }

    /**
     * @param array<int, string> $allowedExtraVariables
     */
    #[Test]
    #[DataProvider('comparedEnvironmentFileProvider')]
    public function exampleEnvironmentFile_givenTheReferenceFile_declaresTheSameVariables(
        string $fileName,
        array  $allowedExtraVariables,
    ): void {
        // Arrange
        $reference = $this->declaredVariables(self::REFERENCE_FILE);
        $compared  = $this->declaredVariables($fileName);

        // Act
        $missing = array_values(array_diff($reference, $compared));
        $extra   = array_values(array_diff($compared, $reference, $allowedExtraVariables));

        // Assert
        $this->assertSame([], $missing, sprintf(
            '%s does not declare variables that %s does; add them, or remove them from the reference file',
            $fileName,
            self::REFERENCE_FILE,
        ));
        $this->assertSame([], $extra, sprintf(
            '%s declares variables %s does not; add them there, or list them as a per-environment exception',
            $fileName,
            self::REFERENCE_FILE,
        ));
    }

    #[Test]
    public function dockerComposeOnlyVariables_givenTheExceptionList_areNeverReadByTheApplication(): void
    {
        // Arrange - the exception list only holds because docker-compose.yml interpolates these itself; the
        // moment one gains a consumer it has to be declared in every file like anything else
        $sourceContents = '';
        foreach (glob(config_path('*.php')) as $configFile) {
            $sourceContents .= file_get_contents($configFile);
        }
        foreach ($this->phpFilesIn(app_path()) as $applicationFile) {
            $sourceContents .= file_get_contents($applicationFile);
        }

        // Act
        $consumed = array_values(array_filter(
            self::DOCKER_COMPOSE_ONLY,
            static fn(string $variable): bool => str_contains($sourceContents, $variable),
        ));

        // Assert
        $this->assertSame([], $consumed, 'a docker-compose-only variable gained a config/ or app/ consumer');
    }

    /**
     * @return array<int, string>
     */
    private function phpFilesIn(string $directory): array
    {
        $files    = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function declaredVariables(string $fileName): array
    {
        $variables = [];
        foreach (preg_split('/\R/', file_get_contents(base_path($fileName))) as $line) {
            if (preg_match('/^#?\s*([A-Z][A-Z0-9_]*)=/', trim($line), $matches) === 1) {
                $variables[] = $matches[1];
            }
        }

        sort($variables);

        return array_values(array_unique($variables));
    }
}
