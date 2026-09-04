<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CorrectEvents;

use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Controller\Api\V1\APICombatLogController\APICombatLogControllerTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CorrectEvents')]
abstract class APICombatLogControllerCorrectEventsTestBase extends APICombatLogControllerTestBase
{
    /**
     * Asserts the corrected events for $fixtureName against its committed `_corrected` fixture.
     *
     * A `_corrected` fixture that does not exist yet is written instead of asserted, so a new dungeon's
     * expectations can be captured on the first run, and the test reports incomplete rather than passing on an
     * expectation it just made up. Regenerating an existing fixture is the same gesture: delete it and run again.
     *
     * Rewriting a fixture in place is deliberately not offered. An assertion that overwrites what it asserts
     * against cannot fail, so a mapping change that moves these coordinates would land with nothing to catch it.
     */
    protected function executeTest(string $fixtureName): void
    {
        // Arrange
        $correctedFixtureName = sprintf('%s_corrected', $fixtureName);
        $postBody             = $this->getJsonData($fixtureName, '../../');

        // Act
        $response = $this->post(route('api.v1.combatlog.event.correct'), $postBody);

        // Assert
        $response->assertOk();

        $responseArr = json_decode($response->content(), true);

        if (!$this->hasJsonData($correctedFixtureName, '../../')) {
            $this->writeJsonData($correctedFixtureName, $responseArr, '../../');

            $this->markTestIncomplete(sprintf(
                'Wrote a new fixture for %s - review it, then re-run to assert against it.',
                $correctedFixtureName,
            ));
        }

        $this->assertEqualsCanonicalizing(
            $this->getJsonData($correctedFixtureName, '../../'),
            $responseArr,
        );
    }
}
