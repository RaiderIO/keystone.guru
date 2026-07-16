<?php

namespace Tests\Feature\Http;

use App\Models\BannedIpAddress;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('BlockBannedIpAddresses')]
final class BlockBannedIpAddressesTest extends PublicTestCase
{
    #[Test]
    public function get_GivenRequestFromBannedIp_ReturnsForbiddenWithoutRenderingView(): void
    {
        // Arrange
        $bannedIpAddress = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.20']);

        try {
            // Act
            $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])->get(route('home'));

            // Assert - short-circuited by the middleware before routing, so no view was rendered
            $response->assertStatus(403);
            self::assertSame('Forbidden', $response->getContent());
        } finally {
            $bannedIpAddress->delete();
        }
    }

    #[Test]
    public function get_GivenRequestFromNonBannedIp_PassesThrough(): void
    {
        // Arrange
        $bannedIpAddress = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.21']);

        try {
            // Act
            $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.30'])->get(route('home'));

            // Assert
            $response->assertOk();
        } finally {
            $bannedIpAddress->delete();
        }
    }

    #[Test]
    public function get_GivenRequestFromIpWithinBannedCidrRange_ReturnsForbidden(): void
    {
        // Arrange
        $bannedIpAddress = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.0/24']);

        try {
            // Act
            $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.250'])->get(route('home'));

            // Assert
            $response->assertStatus(403);
        } finally {
            $bannedIpAddress->delete();
        }
    }

    #[Test]
    public function get_GivenRequestFromIpWithExpiredBan_PassesThrough(): void
    {
        // Arrange
        $bannedIpAddress = BannedIpAddress::factory()->expired()->create(['ip_address' => '203.0.113.22']);

        try {
            // Act
            $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.22'])->get(route('home'));

            // Assert
            $response->assertOk();
        } finally {
            $bannedIpAddress->delete();
        }
    }
}
