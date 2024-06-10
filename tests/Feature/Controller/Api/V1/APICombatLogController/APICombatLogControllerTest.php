<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\APIPublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
class APICombatLogControllerTest extends APIPublicTestCase
{
    use LoadsJsonFiles;

    #[Test]
    public function store_givenNewValidBrushline_shouldReturnBrushline(): void
    {
        // Arrange
        $postBody = $this->getJsonData('s4_algethar_academy_bunten_16');

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert

        $responseArr = json_decode($response->content(), true);

        dump($responseArr);

        $response->assertCreated();
    }
}
