<?php

namespace Tests\Feature\Environment;

use Laravel\Reverb\Application;
use Laravel\Reverb\ConfigApplicationProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The map's cursor and viewport whispers identify their sender by the user_id Reverb stamps onto a client event. Reverb
 * only stamps it when the app accepts client events from channel members; in its default 'all' mode the event reaches
 * the browsers without a sender and they drop it.
 */
#[Group('Environment')]
final class ReverbConfigTest extends PublicTestCase
{
    #[Test]
    public function acceptClientEventsFrom_givenTheConfiguredApps_returnsMembersForEveryApp(): void
    {
        // Arrange
        $provider = new ConfigApplicationProvider(collect(config('reverb.apps.apps')));

        // Act
        $acceptClientEventsFrom = $provider->all()
            ->map(static fn(Application $application) => $application->acceptClientEventsFrom())
            ->all();

        // Assert
        self::assertNotEmpty($acceptClientEventsFrom);
        foreach ($acceptClientEventsFrom as $value) {
            self::assertSame('members', $value);
        }
    }
}
