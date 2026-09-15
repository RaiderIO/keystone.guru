<?php

namespace Tests\Feature\Environment;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards that retired integrations do not creep back into the example environments (#4484).
 *
 * InfluxDB was replaced by the telemetry_metrics table in #4075, the Reddit integration was never wired up in
 * config/ or app/, and GITHUB_DOCKER_ACCESS_TOKEN is a registry credential that the running application never
 * reads. Each one was a variable a developer had to keep populated for no benefit.
 */
#[Group('Environment')]
final class RetiredIntegrationConfigTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function exampleEnvironmentFileProvider(): array
    {
        return [
            '.env.example'        => ['.env.example'],
            '.env.docker.example' => ['.env.docker.example'],
            '.env.ci.example'     => ['.env.ci.example'],
        ];
    }

    #[Test]
    #[DataProvider('exampleEnvironmentFileProvider')]
    public function exampleEnvironmentFile_givenRetiredIntegrations_declaresNoneOfTheirVariables(string $fileName): void
    {
        // Arrange
        $retiredPrefixes = ['INFLUXDB_', 'REDDIT_', 'GITHUB_DOCKER_ACCESS_TOKEN'];
        $contents        = file_get_contents(base_path($fileName));

        // Act
        $declared = [];
        foreach (preg_split('/\R/', $contents) as $line) {
            $name = ltrim($line, '#');
            foreach ($retiredPrefixes as $retiredPrefix) {
                if (str_starts_with($name, $retiredPrefix)) {
                    $declared[] = trim($line);
                }
            }
        }

        // Assert
        $this->assertSame([], $declared, sprintf('%s declares retired integration variables', $fileName));
    }

    #[Test]
    public function influxDbConfiguration_givenTheTelemetryMetricsReplacement_isAbsent(): void
    {
        // Arrange

        // Act
        $configExists = file_exists(config_path('influxdb.php'));
        $keystoneGuru = config('keystoneguru');

        // Assert
        $this->assertFalse($configExists, 'config/influxdb.php was retired along with the InfluxDB sink');
        $this->assertArrayNotHasKey('influxdb', $keystoneGuru);
    }
}
