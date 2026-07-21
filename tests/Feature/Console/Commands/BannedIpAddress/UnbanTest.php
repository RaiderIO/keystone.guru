<?php

namespace Tests\Feature\Console\Commands\BannedIpAddress;

use App\Console\Commands\BannedIpAddress\Unban;
use App\Models\BannedIpAddress;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('BannedIpAddress')]
final class UnbanTest extends PublicTestCase
{
    #[Test]
    public function handle_givenExistingBan_removesIt(): void
    {
        // Arrange
        $bannedIpAddress = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.41']);

        // Act
        $this->artisan(Unban::class, ['ipAddress' => '203.0.113.41'])->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('banned_ip_addresses', ['id' => $bannedIpAddress->id]);
    }

    #[Test]
    public function handle_givenIpAddressNotBanned_returnsFailureWithoutError(): void
    {
        // Act + Assert
        $this->artisan(Unban::class, ['ipAddress' => '203.0.113.42'])->assertFailed();
    }
}
