<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\BannedIpAddress;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsBannedIpAddressControllerTest extends PublicTestCase
{
    private const int ADMIN_USER_ID     = 1;
    private const int NON_ADMIN_USER_ID = 3;

    /** @var array<int, int> */
    private array $createdIds = [];

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
    public function index_givenAdmin_returnsOk(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $bannedIpAddress = BannedIpAddress::factory()->create([
            'ip_address' => '203.0.113.32',
            'reason'     => 'Rendered by test',
        ]);
        $this->createdIds[] = $bannedIpAddress->id;

        // Act
        $response = $this->get(route('admin.tools.bannedipaddresses.view'));

        // Assert - proves the table actually renders the ban, not just a bare 200
        $response->assertOk();
        $response->assertSee('203.0.113.32');
        $response->assertSee('Rendered by test');
        $response->assertSee(route('admin.tools.bannedipaddresses.store'), false);
    }

    #[Test]
    public function index_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.bannedipaddresses.view'));

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function store_givenValidIpAddress_createsBanAndRedirects(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        try {
            // Act
            $response = $this->post(route('admin.tools.bannedipaddresses.store'), [
                'ip_address' => '203.0.113.30',
                'reason'     => 'Abuse',
            ]);

            // Assert
            $response->assertRedirect(route('admin.tools.bannedipaddresses.view'));
            $response->assertSessionHas('status', __('controller.admintools.flash.banned_ip_address_added'));
            $this->assertDatabaseHas('banned_ip_addresses', [
                'ip_address' => '203.0.113.30',
                'reason'     => 'Abuse',
                'created_by' => self::ADMIN_USER_ID,
                'expires_at' => null,
            ]);
        } finally {
            BannedIpAddress::query()->where('ip_address', '203.0.113.30')->delete();
        }
    }

    #[Test]
    public function store_givenFutureExpiry_storesTheExpiry(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $expiresAt = Carbon::now()->addDays(3)->startOfSecond();

        try {
            // Act
            $response = $this->post(route('admin.tools.bannedipaddresses.store'), [
                'ip_address' => '203.0.113.33',
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);

            // Assert
            $response->assertRedirect(route('admin.tools.bannedipaddresses.view'));
            $bannedIpAddress = BannedIpAddress::query()->where('ip_address', '203.0.113.33')->firstOrFail();
            $this->assertNull($bannedIpAddress->reason);
            $this->assertTrue($expiresAt->equalTo($bannedIpAddress->expires_at));
        } finally {
            BannedIpAddress::query()->where('ip_address', '203.0.113.33')->delete();
        }
    }

    #[Test]
    public function store_givenPastExpiry_returnsValidationError(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        try {
            // Act
            $response = $this->post(route('admin.tools.bannedipaddresses.store'), [
                'ip_address' => '203.0.113.34',
                'expires_at' => Carbon::now()->subDay()->toDateTimeString(),
            ]);

            // Assert
            $response->assertSessionHasErrors('expires_at');
            $this->assertDatabaseMissing('banned_ip_addresses', ['ip_address' => '203.0.113.34']);
        } finally {
            BannedIpAddress::query()->where('ip_address', '203.0.113.34')->delete();
        }
    }

    #[Test]
    public function store_givenNonAdmin_returnsForbiddenWithoutCreatingBan(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        try {
            // Act
            $response = $this->post(route('admin.tools.bannedipaddresses.store'), [
                'ip_address' => '203.0.113.35',
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseMissing('banned_ip_addresses', ['ip_address' => '203.0.113.35']);
        } finally {
            BannedIpAddress::query()->where('ip_address', '203.0.113.35')->delete();
        }
    }

    #[Test]
    public function store_givenOverlyBroadRange_returnsValidationError(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->post(route('admin.tools.bannedipaddresses.store'), [
            'ip_address' => '10.0.0.0/8',
        ]);

        // Assert
        $response->assertSessionHasErrors('ip_address');
        $this->assertDatabaseMissing('banned_ip_addresses', ['ip_address' => '10.0.0.0/8']);
    }

    #[Test]
    public function store_givenRequestersOwnIp_returnsValidationError(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->post(route('admin.tools.bannedipaddresses.store'), [
                'ip_address' => '198.51.100.77',
            ]);

        // Assert
        $response->assertSessionHasErrors('ip_address');
        $this->assertDatabaseMissing('banned_ip_addresses', ['ip_address' => '198.51.100.77']);
    }

    #[Test]
    public function destroy_givenExistingBan_removesItAndRedirects(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $bannedIpAddress    = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.31']);
        $this->createdIds[] = $bannedIpAddress->id;

        // Act
        $response = $this->delete(route('admin.tools.bannedipaddresses.destroy', ['bannedIpAddress' => $bannedIpAddress->id]));

        // Assert
        $response->assertRedirect(route('admin.tools.bannedipaddresses.view'));
        $response->assertSessionHas('status', __('controller.admintools.flash.banned_ip_address_removed'));
        $this->assertDatabaseMissing('banned_ip_addresses', ['id' => $bannedIpAddress->id]);
    }

    #[Test]
    public function destroy_givenUnknownBan_returnsNotFound(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $unknownId = (int)BannedIpAddress::query()->max('id') + 1000;

        // Act
        $response = $this->delete(route('admin.tools.bannedipaddresses.destroy', ['bannedIpAddress' => $unknownId]));

        // Assert
        $response->assertNotFound();
    }
}
