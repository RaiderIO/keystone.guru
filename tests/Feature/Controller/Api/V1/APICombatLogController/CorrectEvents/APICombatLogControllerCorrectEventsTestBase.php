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
    protected function executeTest(string $fixtureName, bool $rewriteFixtures = false): void
    {
        // Fill function

        // Arrange
        $postBody = $this->getJsonData($fixtureName, '../../');

        // Act
        $response = $this->post(route('api.v1.combatlog.event.correct'), $postBody);

        // Assert
        $response->assertOk();

        $responseArr = json_decode($response->content(), true);

        if ($rewriteFixtures) {
            $this->writeJsonData(sprintf('%s_corrected', $fixtureName), $responseArr, '../../');

            $this->assertTrue(true, 'Corrected fixture written');
        } else {
            $this->assertEqualsCanonicalizing(
                $this->getJsonData(sprintf('%s_corrected', $fixtureName), '../../'),
                $responseArr,
            );
        }
    }
}
