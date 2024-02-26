<?php

namespace Tests\Unit\App\Service\AffixGroup;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\ServiceFixtures;

final class AffixGroupEaseTierServiceTest extends PublicTestCase
{

    /**
     *
     * @return void
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getTiersHash_GivenNormalResponse_ShouldGenerateExpectedHash(): void
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
     *
     * @return void
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getTiersHash_GivenUnsortedDungeonNamesResponse_ShouldGenerateSameHash(): void
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
     *
     * @return void
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getTiersHash_GivenNormalResponseWithDungeonNameMapping_ShouldGenerateDifferentHash(): void
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
     *
     * @return void
     */
    #[Test]
    #[Group('AffixGroupEaseTierService')]
    public function getTiersHash_GivenDifferentTiersResponse_ShouldGenerateDifferentHash(): void
    {
        // Arrange
        $response                  = $this->getResponse('response_different_tiers');
        $affixGroupEaseTierService = ServiceFixtures::getAffixGroupEaseTierServiceMock($this);

        // Act
        $hash = $affixGroupEaseTierService->getTiersHash($response, []);

        // Assert
        $this->assertEquals('761ba5dc6a6bc38ca8158a814c9ed6c1', $hash);
    }

    /**
     * @return array
     */
    private function getResponse(string $fileName = 'response'): array
    {
        return json_decode(file_get_contents(sprintf('%s/Fixtures/%s.json', __DIR__, $fileName)), true);
    }
}
