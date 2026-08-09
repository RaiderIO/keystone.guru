<?php

namespace Tests\Feature\App\Service\MDT;

use App\Service\MDT\MDTImportStringServiceInterface;
use Illuminate\Support\Facades\Log;
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
            $dungeonRoute  = $this->getMDTCompatibleNonFacadeDungeonRoute();
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

    /**
     * Guards #3906: garbage input that MDTStringFormat::detect() falls back to the Legacy codec for
     * (i.e. anything not `!~MDT2~`-prefixed) must not be logged at 'error' level - LegacyMDTCodec
     * shells out to cli_weakauras_parser, whose stderr on malformed input ("Failed to decompress
     * data: Invalid prefix" etc.) was reaching Sentry as noise for what the controller already
     * handles as a clean 400 response.
     */
    #[Test]
    #[Group('MDTImportStringServiceDecode')]
    public function getDecoded_givenGarbageStringRoutedToLegacyCodec_logsAtWarningNotError(): void
    {
        // A spy (rather than shouldReceive(), which replaces Log entirely with a strict mock) only
        // observes calls without failing the test over any OTHER log call made along the way
        $logSpy = Log::spy();

        // Act
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString('this_is_not_a_valid_mdt_string')
            ->getDecoded();

        // Assert
        $this->assertNull($decoded);
        $logSpy->shouldHaveReceived('warning');
        $logSpy->shouldNotHaveReceived('error');
    }
}
