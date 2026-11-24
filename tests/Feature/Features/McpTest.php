<?php

namespace Tests\Feature\Features;

use App\Features\Mcp;
use App\Models\Feature\Feature;
use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Admins first: the MCP server resolves only for admins, and only once an admin has switched it on.
 */
#[Group('Mcp')]
#[Group('Features')]
final class McpTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('resolveProvider')]
    public function resolve_givenSwitchAndRole_returnsExpected(bool $switchedOn, string $role, bool $expected): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', $role));

        $scope         = sprintf('%s|%d', User::class, Feature::ADMIN_USER_ID);
        $existingValue = Feature::query()->where('name', Mcp::class)->where('scope', $scope)->value('value');

        try {
            Feature::query()->updateOrInsert(
                ['name' => Mcp::class, 'scope' => $scope],
                ['value' => $switchedOn ? 'true' : 'false', 'created_at' => now(), 'updated_at' => now()],
            );

            // Act
            $result = new Mcp()->resolve($user);

            // Assert
            $this->assertSame($expected, $result);
        } finally {
            if ($existingValue === null) {
                Feature::query()->where('name', Mcp::class)->where('scope', $scope)->delete();
            } else {
                Feature::query()->where('name', Mcp::class)->where('scope', $scope)->update(['value' => $existingValue]);
            }

            $user->syncRoles([]);
            $user->delete();
        }
    }

    /**
     * @return array<string, array{bool, string, bool}>
     */
    public static function resolveProvider(): array
    {
        return [
            'switched on, admin'      => [true, Role::ROLE_ADMIN, true],
            'switched on, plain user' => [true, Role::ROLE_USER, false],
            'switched off, admin'     => [false, Role::ROLE_ADMIN, false],
        ];
    }
}
