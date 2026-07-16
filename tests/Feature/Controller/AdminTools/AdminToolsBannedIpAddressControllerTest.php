<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\BannedIpAddress;
use App\Models\User;
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

        // Act
        $response = $this->post(route('admin.tools.bannedipaddresses.store'), [
            'ip_address' => '203.0.113.30',
            'reason'     => 'Abuse',
        ]);

        // Assert
        $response->assertRedirect(route('admin.tools.bannedipaddresses.view'));
        $this->assertDatabaseHas('banned_ip_addresses', [
            'ip_address' => '203.0.113.30',
            'reason'     => 'Abuse',
            'created_by' => self::ADMIN_USER_ID,
        ]);

        $created            = BannedIpAddress::query()->where('ip_address', '203.0.113.30')->firstOrFail();
        $this->createdIds[] = $created->id;
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
        $bannedIpAddress = BannedIpAddress::factory()->create(['ip_address' => '203.0.113.31']);

        // Act
        $response = $this->delete(route('admin.tools.bannedipaddresses.destroy', ['bannedIpAddress' => $bannedIpAddress->id]));

        // Assert
        $response->assertRedirect(route('admin.tools.bannedipaddresses.view'));
        $this->assertDatabaseMissing('banned_ip_addresses', ['id' => $bannedIpAddress->id]);
    }
}
