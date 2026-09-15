<?php

namespace Tests\Unit\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

/**
 * Guards the connection isolation #4346 introduced.
 *
 * A model that hardcodes `protected $connection` ignores the default connection, so under PHPUnit it keeps reading and
 * writing DB_DATABASE while every other model uses DB_PHPUNIT_DATABASE. That splits a test's data across two schemas
 * and writes test rows into the live dev database (#4498).
 *
 * The combatlog models are the deliberate exception: their data genuinely lives on a second server, and
 * `Tests\TestCase::setUp()` redirects that connection to DB_PHPUNIT_COMBATLOG_DATABASE instead.
 *
 * These assertions hold regardless of what the two schemas are called, which is the point - `.env.ci.example` gives
 * CI a single schema for both, so CI cannot observe the symptom itself.
 */
#[Group('Models')]
final class ModelConnectionTest extends TestCase
{
    private const string COMBAT_LOG_NAMESPACE = 'App\\Models\\CombatLog\\';

    #[Test]
    public function connection_givenUserModel_isNotPinnedToASpecificConnection(): void
    {
        // Arrange
        $user = new User();

        // Act
        $connectionName = $user->getConnectionName();

        // Assert
        $this->assertNull(
            $connectionName,
            sprintf(
                'User must follow the default connection, but is pinned to "%s". A pin here sends every test\'s user ' .
                'rows to DB_DATABASE while the rest of the test data lives in DB_PHPUNIT_DATABASE (#4498).',
                $connectionName ?? 'null',
            ),
        );
    }

    #[Test]
    public function connection_givenAnyModel_isOnlyPinnedByCombatLogModels(): void
    {
        // Arrange
        $offenders = [];

        // Act
        foreach ($this->modelClasses() as $modelClass) {
            $connectionName = (new ReflectionClass($modelClass))->getDefaultProperties()['connection'] ?? null;

            if ($connectionName === null) {
                continue;
            }

            if ($connectionName === 'combatlog' && str_starts_with($modelClass, self::COMBAT_LOG_NAMESPACE)) {
                continue;
            }

            $offenders[$modelClass] = $connectionName;
        }

        // Assert
        $this->assertSame(
            [],
            $offenders,
            'Only App\Models\CombatLog models may pin $connection (to "combatlog"). Any other pin bypasses the ' .
            'phpunit connection and leaks test data into DB_DATABASE (#4498).',
        );
    }

    /**
     * @return list<class-string<Model>>
     */
    private function modelClasses(): array
    {
        $modelClasses = [];

        /** @var SplFileInfo $file */
        foreach (Finder::create()->files()->in(app_path('Models'))->name('*.php') as $file) {
            $relativePath = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $modelClass   = sprintf('App\\Models\\%s', $relativePath);

            if (!class_exists($modelClass)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($modelClass);
            if ($reflectionClass->isAbstract() || !$reflectionClass->isSubclassOf(Model::class)) {
                continue;
            }

            $modelClasses[] = $modelClass;
        }

        return $modelClasses;
    }
}
