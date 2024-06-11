<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\Traits\ValidatesUrls;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('AlgetharAcademy')]
class APICombatLogControllerAlgetharAcademyTest extends APICombatLogControllerBaseTest
{
    use LoadsJsonFiles, ValidatesUrls;

    #[Test]
    public function create_givenAlgetharAcademyBunten16Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('s4_algethar_academy_bunten_16');

        // Act
        $response = $this->post(route('api.v1.combatlog.route.create'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);
        $this->validateResponseStaticData($responseArr);

        // Main data
        $this->assertEquals(67, $responseArr['data']['dungeon_id']); // Algethar Academy
        $this->assertEquals('Algeth\'ar Academy', $responseArr['data']['title']);
        $this->assertEquals(13, $responseArr['data']['pulls']);
        $this->assertEquals(450, $responseArr['data']['enemy_forces']);
        $this->assertEquals(450, $responseArr['data']['enemy_forces_required']);

        // Affixes
        $this->assertEquals(10, $responseArr['data']['affix_groups'][0]['affixes'][0]['id']);
        $this->assertEquals(124, $responseArr['data']['affix_groups'][0]['affixes'][1]['id']);
        $this->assertEquals(11, $responseArr['data']['affix_groups'][0]['affixes'][2]['id']);
    }
}
