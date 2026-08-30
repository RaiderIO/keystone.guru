<?php

namespace Tests\Unit\App\Logic\Utils;

use App\Logic\Utils\EmailMasker;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[Group('Patreon')]
#[Group('EmailMasker')]
final class EmailMaskerTest extends TestCase
{
    #[Test]
    #[TestWith(['someone@example.com', 's*****e@e*****e.com'])]
    #[TestWith(['a@b.com', '*@*.com'])]
    #[TestWith(['no-at-sign', 'n*****n'])]
    public function mask_givenAnAddress_keepsOnlyTheOutlineAndTheTld(string $email, string $expected): void
    {
        // Act
        $masked = EmailMasker::mask($email);

        // Assert
        $this->assertSame($expected, $masked);
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith([''])]
    public function mask_givenNothingToMask_returnsItUnchanged(?string $email): void
    {
        // Act & Assert
        $this->assertSame($email, EmailMasker::mask($email));
    }

    #[Test]
    public function mask_givenTwoDifferentAddresses_producesDifferentMasks(): void
    {
        // Arrange - the masks have to stay comparable, since spotting a changed Patreon email is the
        // entire reason the diagnostics show them at all (#4373)
        $first  = EmailMasker::mask('patron@oldprovider.com');
        $second = EmailMasker::mask('patron@newprovider.net');

        // Assert
        $this->assertNotSame($first, $second);
    }
}
