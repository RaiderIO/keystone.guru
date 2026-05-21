<?php

namespace Tests\Feature\App\Service\MDT;

use App\Service\MDT\MDTImportStringServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTImportStringService')]
class MDTImportStringServiceDecodeTest extends MDTImportStringServiceTestBase
{
    #[Test]
    #[Group('MDTImportStringServiceDecode')]
    public function getDecoded_givenValidEncodedString_returnsArray(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute  = $this->getMDTCompatibleDungeonRoute();
            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $decoded = app()->make(MDTImportStringServiceInterface::class)
                ->setEncodedString($encodedString)
                ->getDecoded();

            // Assert
            $this->assertIsArray($decoded);
            $this->assertArrayHasKey('value', $decoded);
            $this->assertArrayHasKey('objects', $decoded);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[Group('MDTImportStringServiceDecode')]
    public function getDecoded_givenInvalidString_returnsNull(): void
    {
        // Act
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString('this_is_not_a_valid_mdt_string')
            ->getDecoded();

        // Assert
        $this->assertNull($decoded);
    }
}
