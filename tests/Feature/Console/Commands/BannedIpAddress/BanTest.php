<?php

namespace Tests\Feature\Console\Commands\BannedIpAddress;

use App\Console\Commands\BannedIpAddress\Ban;
use App\Models\BannedIpAddress;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('BannedIpAddress')]
final class BanTest extends PublicTestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        try {
            BannedIpAddress::query()->where('ip_address', '203.0.113.40')->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenIpAddress_createsBan(): void
    {
        // Act
        $this->artisan(Ban::class, [
            'ipAddress' => '203.0.113.40',
            '--reason'  => 'CLI incident response',
        ])->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('banned_ip_addresses', [
            'ip_address' => '203.0.113.40',
            'reason'     => 'CLI incident response',
            'created_by' => null,
        ]);
    }
}
