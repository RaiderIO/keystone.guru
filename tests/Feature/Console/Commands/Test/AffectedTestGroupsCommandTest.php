<?php

namespace Tests\Feature\Console\Commands\Test;

use App\Console\Commands\Test\AffectedTestGroupsCommand;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('AffectedTestGroups')]
final class AffectedTestGroupsCommandTest extends PublicTestCase
{
    #[Test]
    public function handle_givenMappedPaths_printsGroupsVerdict(): void
    {
        // Arrange & Act & Assert
        $this->artisan('test:affected-groups', ['paths' => ['app/Logic/MDT/Conversion.php', 'app/Policies/DungeonRoutePolicy.php']])
            ->expectsOutput(sprintf('%s --group=MDT --group=Policy', AffectedTestGroupsCommand::VERDICT_GROUPS))
            ->assertSuccessful();
    }

    #[Test]
    public function handle_givenSharedPath_printsFullVerdictAndNamesThePath(): void
    {
        // Arrange & Act & Assert
        $this->artisan('test:affected-groups', ['paths' => ['app/Logic/MDT/Conversion.php', 'config/app.php']])
            ->expectsOutput('full suite: config/app.php')
            ->expectsOutput(AffectedTestGroupsCommand::VERDICT_FULL)
            ->assertSuccessful();
    }

    #[Test]
    public function handle_givenOnlyIgnoredPaths_printsNoneVerdict(): void
    {
        // Arrange & Act & Assert
        $this->artisan('test:affected-groups', ['paths' => ['resources/assets/js/app.js']])
            ->expectsOutput('no tests: resources/assets/js/app.js')
            ->expectsOutput(AffectedTestGroupsCommand::VERDICT_NONE)
            ->assertSuccessful();
    }
}
