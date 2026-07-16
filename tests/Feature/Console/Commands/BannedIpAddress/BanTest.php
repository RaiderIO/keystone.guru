<?php

namespace Tests\Feature\Console\Commands\BannedIpAddress;

use App\Console\Commands\BannedIpAddress\Ban;
use App\Models\BannedIpAddress;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('BannedIpAddress')]
final class BanTest extends PublicTestCase
{
    private const int ADMIN_USER_ID = 1;

    #[\Override]
    protected function tearDown(): void
    {
        try {
            BannedIpAddress::query()->whereIn('ip_address', ['203.0.113.40', '203.0.113.43'])->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenIpAddressAndAdminId_createsBanAttributedToThatAdmin(): void
    {
        // Act
        $this->artisan(Ban::class, [
            'ipAddress' => '203.0.113.40',
            'adminId'   => self::ADMIN_USER_ID,
            '--reason'  => 'CLI incident response',
        ])->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('banned_ip_addresses', [
            'ip_address' => '203.0.113.40',
            'reason'     => 'CLI incident response',
            'created_by' => self::ADMIN_USER_ID,
        ]);
    }

    #[Test]
    public function handle_givenNonAdminUserId_failsWithoutCreatingBan(): void
    {
        // Arrange
        $nonAdmin = User::factory()->create();

        try {
            // Act
            $this->artisan(Ban::class, [
                'ipAddress' => '203.0.113.43',
                'adminId'   => $nonAdmin->id,
            ])->assertFailed();

            // Assert
            $this->assertDatabaseMissing('banned_ip_addresses', ['ip_address' => '203.0.113.43']);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function handle_givenNonExistentAdminId_failsWithoutCreatingBan(): void
    {
        // Act
        $this->artisan(Ban::class, [
            'ipAddress' => '203.0.113.43',
            'adminId'   => 999999999,
        ])->assertFailed();

        // Assert
        $this->assertDatabaseMissing('banned_ip_addresses', ['ip_address' => '203.0.113.43']);
    }
}
