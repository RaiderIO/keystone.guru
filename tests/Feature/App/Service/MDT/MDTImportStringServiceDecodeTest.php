<?php

namespace Tests\Feature\App\Service\MDT;

use App\Service\MDT\Logging\MDTImportStringServiceLoggingInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use Mockery;
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
     * Guards #3906: input that doesn't even plausibly claim to be an MDT export string (fails
     * MDTStringFormat::isValid() - detect() still falls back to Legacy for dispatch purposes, but
     * that's just a "pick something to try" default) must not be logged at 'error' level -
     * LegacyMDTCodec shells out to cli_weakauras_parser, whose stderr on malformed input ("Failed to
     * decompress data: Invalid prefix" etc.) was reaching Sentry as noise for what the controller
     * already handles as a clean 400 response.
     */
    #[Test]
    #[Group('MDTImportStringServiceDecode')]
    public function getDecoded_givenStringThatDoesNotPlausiblyClaimToBeMdt_logsDecodeInvalidStringFailed(): void
    {
        // Arrange - a spy (rather than a strict mock) on the structured logging seam only observes
        // calls, without failing the test over any OTHER log event raised along the way. It must be
        // bound before the service is resolved, since the service receives it through its constructor
        $logSpy = Mockery::spy(MDTImportStringServiceLoggingInterface::class);
        $this->app->instance(MDTImportStringServiceLoggingInterface::class, $logSpy);

        // Act
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString('this_is_not_a_valid_mdt_string')
            ->getDecoded();

        // Assert - the levels these two events map onto are asserted by MDTBaseServiceLoggingTest
        $this->assertNull($decoded);
        $logSpy->shouldHaveReceived('decodeInvalidStringFailed');
        $logSpy->shouldNotHaveReceived('decodeFailed');
    }

    /**
     * Guards the flip side of #3906: a string that DOES plausibly claim to be a legacy MDT export
     * (matches LegacyMDTCodec::appliesTo()'s character-class check) but fails to actually decode -
     * e.g. truncated/corrupted in transit - must still be logged at 'error' level, since that's much
     * more likely a real problem worth investigating than a user pasting arbitrary garbage.
     */
    #[Test]
    #[Group('MDTImportStringServiceDecode')]
    public function getDecoded_givenLegacyShapedStringThatFailsToDecode_logsDecodeFailed(): void
    {
        // Arrange - see the comment on the sibling test above for why the spy is bound up front
        $logSpy = Mockery::spy(MDTImportStringServiceLoggingInterface::class);
        $this->app->instance(MDTImportStringServiceLoggingInterface::class, $logSpy);

        // Act - `!`-prefixed and otherwise matches LegacyMDTCodec::appliesTo()'s character class,
        // but isn't valid compressed data underneath
        $decoded = app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString('!ThisIsNotReallyValidButLooksLegacyXXX123')
            ->getDecoded();

        // Assert - the levels these two events map onto are asserted by MDTBaseServiceLoggingTest
        $this->assertNull($decoded);
        $logSpy->shouldHaveReceived('decodeFailed');
        $logSpy->shouldNotHaveReceived('decodeInvalidStringFailed');
    }
}
