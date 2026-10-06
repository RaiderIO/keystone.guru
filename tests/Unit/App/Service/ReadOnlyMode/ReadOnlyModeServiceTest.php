<?php

namespace Tests\Unit\App\Service\ReadOnlyMode;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\ReadOnlyMode\ReadOnlyModeService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

/**
 * isReadOnly() runs on every rendered page (AppLayoutComposer, bound to layouts.app/sitepage/map)
 * and every request behind the ReadOnlyMode middleware, so it must not have its own uncaught-Redis
 * failure mode independent of CacheService::get() being made resilient in #3914.
 */
#[Group('ReadOnlyMode')]
final class ReadOnlyModeServiceTest extends PublicTestCase
{
    #[Test]
    public function isReadOnly_givenCacheServiceNeverCallsHas_reliesOnlyOnGet(): void
    {
        // Arrange
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->expects($this->never())->method('has');
        $cacheService->method('get')->with('read_only_mode')->willReturn(true);

        $service = new ReadOnlyModeService($cacheService);

        // Act
        $result = $service->isReadOnly();

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isReadOnly_givenCacheServiceReturnsNull_returnsFalse(): void
    {
        // Arrange - a degraded read (Redis blip, #3914) and a genuinely unset key both surface as null
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->method('get')->with('read_only_mode')->willReturn(null);

        $service = new ReadOnlyModeService($cacheService);

        // Act
        $result = $service->isReadOnly();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function setReadOnly_givenAFlag_storesItUnderTheKeyIsReadOnlyReads(): void
    {
        // Arrange
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->expects($this->once())->method('set')->with('read_only_mode', true)->willReturn(true);

        $service = new ReadOnlyModeService($cacheService);

        // Act
        $result = $service->setReadOnly(true);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[DataProvider('isReadOnlyForUserProvider')]
    public function isReadOnlyForUser_givenAUser_returnsWhetherThatUserIsLockedOut(
        bool    $readOnly,
        ?string $userKind,
        bool    $expected,
    ): void {
        // Arrange
        /** @var MockObject&CacheServiceInterface $cacheService */
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->method('get')->with('read_only_mode')->willReturn($readOnly);

        $user = match ($userKind) {
            'admin' => User::findOrFail(1),
            'user'  => User::factory()->make(),
            default => null,
        };
        $this->assertSame($userKind === 'admin', $user?->hasRole(Role::ROLE_ADMIN) ?? false, 'User id=1 must be the only admin here.');

        $service = new ReadOnlyModeService($cacheService);

        // Act
        $result = $service->isReadOnlyForUser($user);

        // Assert
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{bool, string|null, bool}> */
    public static function isReadOnlyForUserProvider(): array
    {
        return [
            'read-only, a regular user'     => [true, 'user', true],
            'read-only, no user'            => [true, null, true],
            'read-only, an admin'           => [true, 'admin', false],
            'not read-only, a regular user' => [false, 'user', false],
            'not read-only, no user'        => [false, null, false],
        ];
    }
}
