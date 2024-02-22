<?php

namespace Tests\Unit\App\Service\AffixGroup;

use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\ServiceFixtures;

class AffixGroupEaseTierServiceTest extends PublicTestCase
{

    /**
     * @test
     *
     * @return void
     *
     * @group AffixGroupEaseTierService
     */
    public function getTiersHash_GivenNormalResponse_ShouldGenerateExpectedHash()
    {
        // Arrange
        $response                  = $this->getResponse();
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock($this);

        // Act
        $hash = $affixGroupEaseTierService->getTiersHash($response, []);

        // Assert
        $this->assertEquals('60104c77c32d029558c21f5f32a533d6', $hash);
    }

    /**
     * @test
     *
     * @return void
     *
     * @group AffixGroupEaseTierService
     */
    public function getTiersHash_GivenUnsortedDungeonNamesResponse_ShouldGenerateExpectedHash()
    {
        // Arrange
        $response                  = $this->getResponse('response_unsorted_dungeon_names');
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock($this);

        // Act
        $hash = $affixGroupEaseTierService->getTiersHash($response, []);

        // Assert
        $this->assertEquals('60104c77c32d029558c21f5f32a533d6', $hash);
    }

    /**
     * @test
     *
     * @return void
     *
     * @group AffixGroupEaseTierService
     */
    public function getTiersHash_GivenNormalResponseWithDungeonNameMapping_ShouldGenerateDifferentHash()
    {
        // Arrange
        $response                  = $this->getResponse();
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock($this);

        // Act
        $hash = $affixGroupEaseTierService->getTiersHash($response, ['Waycrest Manor' => 'WM']);

        // Assert
        $this->assertEquals('89032af835ef1b1553b17c86eb668078', $hash);
    }

    /**
     * @param string $fileName
     * @return array
     */
    private function getResponse(string $fileName = 'response'): array
    {
        return json_decode(file_get_contents(sprintf('%s/Fixtures/%s.json', __DIR__, $fileName)), true);
    }
}
