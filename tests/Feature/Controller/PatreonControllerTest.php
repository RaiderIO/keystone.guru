<?php

namespace Tests\Feature\Controller;

use App\Models\User;
use App\Service\Patreon\Dtos\LinkToUserIdResult;
use App\Service\Patreon\PatreonServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Patreon')]
final class PatreonControllerTest extends PublicTestCase
{
    #[Test]
    public function link_givenNoCodeParameter_redirectsWithCancelledFlashAndDoesNotThrow(): void
    {
        // Arrange - Patreon omits `code` when the user denies the authorization prompt
        $user  = User::findOrFail(1);
        $state = 'test-csrf-token';

        // Act
        $response = $this->actingAs($user)
            ->withSession(['_token' => $state])
            ->get('/patreon-link?state=' . $state);

        // Assert
        $response->assertRedirect(route('profile.edit', ['#patreon']));
        $response->assertSessionHas('warning', __('controller.patreon.flash.link_cancelled'));
    }

    #[Test]
    public function link_givenBlankCodeParameter_redirectsWithCancelledFlash(): void
    {
        // Arrange
        $user  = User::findOrFail(1);
        $state = 'test-csrf-token';

        // Act
        $response = $this->actingAs($user)
            ->withSession(['_token' => $state])
            ->get('/patreon-link?state=' . $state . '&code=');

        // Assert
        $response->assertRedirect(route('profile.edit', ['#patreon']));
        $response->assertSessionHas('warning', __('controller.patreon.flash.link_cancelled'));
    }

    #[Test]
    public function link_givenStateNotMatchingSessionToken_redirectsWithSessionExpiredFlashWithoutLinking(): void
    {
        // Arrange
        $user = User::findOrFail(1);

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->expects($this->never())->method('linkToUserAccount');
        app()->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf-token'])
            ->get('/patreon-link?state=some-other-token&code=some-code');

        // Assert
        $response->assertRedirect(route('profile.edit', ['#patreon']));
        $response->assertSessionHas('warning', __('controller.patreon.flash.session_expired'));
    }

    #[Test]
    #[DataProvider('link_givenCodeAndMatchingState_linksUserAndFlashesTheResult_dataProvider')]
    public function link_givenCodeAndMatchingState_linksUserAndFlashesTheResult(
        LinkToUserIdResult $linkResult,
        string             $expectedFlashKey,
        string             $expectedTranslationKey,
    ): void {
        // Arrange
        $user  = User::findOrFail(1);
        $state = 'test-csrf-token';

        $patreonService = $this->createMockPublic(PatreonServiceInterface::class);
        $patreonService->expects($this->once())
            ->method('linkToUserAccount')
            ->with(
                $this->callback(static fn(User $linkedUser): bool => $linkedUser->id === $user->id),
                'some-code',
                route('patreon.link'),
            )
            ->willReturn($linkResult);
        app()->instance(PatreonServiceInterface::class, $patreonService);

        // Act
        $response = $this->actingAs($user)
            ->withSession(['_token' => $state])
            ->get('/patreon-link?state=' . $state . '&code=some-code');

        // Assert
        $response->assertRedirect(route('profile.edit', ['#patreon']));
        $response->assertSessionHas($expectedFlashKey, __($expectedTranslationKey));
    }

    /**
     * @return array<string, array{LinkToUserIdResult, string, string}>
     */
    public static function link_givenCodeAndMatchingState_linksUserAndFlashesTheResult_dataProvider(): array
    {
        return [
            'link successful'         => [LinkToUserIdResult::LinkSuccessful, 'status', 'controller.patreon.flash.link_successful'],
            'patreon error'           => [LinkToUserIdResult::PatreonErrorOccurred, 'warning', 'controller.patreon.flash.patreon_error_occurred'],
            'internal error'          => [LinkToUserIdResult::InternalErrorOccurred, 'warning', 'controller.patreon.flash.internal_error_occurred'],
            'patreon session expired' => [LinkToUserIdResult::PatreonSessionExpired, 'warning', 'controller.patreon.flash.patreon_session_expired'],
        ];
    }
}
