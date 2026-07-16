<?php

namespace Tests\Feature\App\Service\BannedIpAddress;

use App\Models\BannedIpAddress;
use App\Service\BannedIpAddress\BannedIpAddressServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Service')]
#[Group('BannedIpAddressService')]
final class BannedIpAddressServiceTest extends PublicTestCase
{
    private BannedIpAddressServiceInterface $service;

    /** @var array<int, int> */
    private array $createdIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BannedIpAddressServiceInterface::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            BannedIpAddress::query()->whereIn('id', $this->createdIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function ban_GivenIpAddress_PersistsIt(): void
    {
        // Act
        $bannedIpAddress    = $this->service->ban('203.0.113.10', 'testing', null, 1);
        $this->createdIds[] = $bannedIpAddress->id;

        // Assert
        $this->assertDatabaseHas('banned_ip_addresses', [
            'id'         => $bannedIpAddress->id,
            'ip_address' => '203.0.113.10',
            'reason'     => 'testing',
        ]);
    }

    #[Test]
    public function isBanned_GivenBannedSingleIp_ReturnsTrue(): void
    {
        // Arrange
        $bannedIpAddress    = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.11']);
        $this->createdIds[] = $bannedIpAddress->id;

        // Act + Assert
        $this->assertTrue($this->service->isBanned('203.0.113.11'));
    }

    #[Test]
    public function isBanned_GivenNonBannedIp_ReturnsFalse(): void
    {
        // Arrange
        $bannedIpAddress    = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.12']);
        $this->createdIds[] = $bannedIpAddress->id;

        // Act + Assert
        $this->assertFalse($this->service->isBanned('203.0.113.99'));
    }

    #[Test]
    public function isBanned_GivenIpWithinBannedCidrRange_ReturnsTrue(): void
    {
        // Arrange
        $bannedIpAddress    = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.0/24']);
        $this->createdIds[] = $bannedIpAddress->id;

        // Act + Assert
        $this->assertTrue($this->service->isBanned('203.0.113.200'));
        $this->assertFalse($this->service->isBanned('203.0.114.1'));
    }

    #[Test]
    public function isBanned_GivenExpiredBan_ReturnsFalse(): void
    {
        // Arrange
        $bannedIpAddress    = BannedIpAddress::factory()->expired()->create(['ip_address' => '203.0.113.13']);
        $this->createdIds[] = $bannedIpAddress->id;

        // Act + Assert
        $this->assertFalse($this->service->isBanned('203.0.113.13'));
    }

    #[Test]
    public function unban_GivenExistingBan_RemovesIt(): void
    {
        // Arrange
        $bannedIpAddress = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.14']);
        self::assertTrue($this->service->isBanned('203.0.113.14'));

        // Act
        $this->service->unban($bannedIpAddress);

        // Assert
        $this->assertFalse($this->service->isBanned('203.0.113.14'));
        $this->assertDatabaseMissing('banned_ip_addresses', ['id' => $bannedIpAddress->id]);
    }
}
