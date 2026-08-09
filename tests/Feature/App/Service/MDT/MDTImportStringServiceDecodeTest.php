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
     * Guards #3906: input that doesn't even plausibly claim to be an MDT export string (fails
     * MDTStringFormat::isValid() - detect() still falls back to Legacy for dispatch purposes, but
     * that's just a "pick something to try" default) must not be logged at 'error' level -
     * LegacyMDTCodec shells out to cli_weakauras_parser, whose stderr on malformed input ("Failed to
     * decompress data: Invalid prefix" etc.) was reaching Sentry as noise for what the controller
     * already handles as a clean 400 response.
     */
    #[Test]
    #[Group('MDTImportStringServiceDecode')]
    public function getDecoded_givenStringThatDoesNotPlausiblyClaimToBeMdt_logsAtWarningNotError(): void
    {
        // Resolve the service (and its StructuredLogging-backed $log) before spying - StructuredLogging
        // caches app('log') at construction time, and Log::spy() replaces that binding with a Mockery
        // spy for the rest of the test; if the service is resolved for the first time AFTER the spy is
        // in place, the spy itself gets cached as a "logger" and every unstubbed method call on it
        // (LogManager::channel()) returns null, crashing the very code this test means to exercise
        $service = app()->make(MDTImportStringServiceInterface::class);

        // A spy (rather than shouldReceive(), which replaces Log entirely with a strict mock) only
        // observes calls without failing the test over any OTHER log call made along the way
        $logSpy = Log::spy();

        // Act
        $decoded = $service
            ->setEncodedString('this_is_not_a_valid_mdt_string')
            ->getDecoded();

        // Assert
        $this->assertNull($decoded);
        $logSpy->shouldHaveReceived('warning');
        $logSpy->shouldNotHaveReceived('error');
    }

    /**
     * Guards the flip side of #3906: a string that DOES plausibly claim to be a legacy MDT export
     * (matches LegacyMDTCodec::appliesTo()'s character-class check) but fails to actually decode -
     * e.g. truncated/corrupted in transit - must still be logged at 'error' level, since that's much
     * more likely a real problem worth investigating than a user pasting arbitrary garbage.
     */
    #[Test]
    #[Group('MDTImportStringServiceDecode')]
    public function getDecoded_givenLegacyShapedStringThatFailsToDecode_logsAtError(): void
    {
        // Resolve before spying - see the comment on the sibling test above for why
        $service = app()->make(MDTImportStringServiceInterface::class);

        $logSpy = Log::spy();

        // Act - `!`-prefixed and otherwise matches LegacyMDTCodec::appliesTo()'s character class,
        // but isn't valid compressed data underneath
        $decoded = $service
            ->setEncodedString('!ThisIsNotReallyValidButLooksLegacyXXX123')
            ->getDecoded();

        // Assert
        $this->assertNull($decoded);
        $logSpy->shouldHaveReceived('error');
        $logSpy->shouldNotHaveReceived('warning');
    }
}
