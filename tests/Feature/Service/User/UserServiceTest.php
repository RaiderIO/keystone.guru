<?php

namespace Tests\Feature\Service\User;

use App\Models\User;
use App\Service\User\Dtos\BasicAuthenticationResult;
use App\Service\User\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Service')]
#[Group('User')]
final class UserServiceTest extends PublicTestCase
{
    #[Test]
    public function loginAsUserFromAuthenticationHeader_givenNoAuthorizationHeader_returnsMissingHeader(): void
    {
        // Arrange
        $userService = app()->make(UserServiceInterface::class);

        // Act
        $result = $userService->loginAsUserFromAuthenticationHeader(new Request());

        // Assert
        $this->assertSame(BasicAuthenticationResult::MissingHeader, $result);
    }

    #[Test]
    public function loginAsUserFromAuthenticationHeader_givenNonBasicScheme_returnsUnsupportedScheme(): void
    {
        // Arrange
        $userService = app()->make(UserServiceInterface::class);

        // Act
        $result = $userService->loginAsUserFromAuthenticationHeader($this->createRequestWithAuthorization('Bearer some-token'));

        // Assert
        $this->assertSame(BasicAuthenticationResult::UnsupportedScheme, $result);
    }

    /**
     * Guzzle sends exactly this when it is handed null credentials, which is what an unconfigured
     * COMBAT_LOG_ROUTE_REGENERATION_USER / _PASSWORD ends up as.
     */
    #[Test]
    public function loginAsUserFromAuthenticationHeader_givenEmptyCredentials_returnsMalformedCredentials(): void
    {
        // Arrange
        $userService = app()->make(UserServiceInterface::class);

        // Act
        $result = $userService->loginAsUserFromAuthenticationHeader($this->createRequestWithCredentials('', ''));

        // Assert
        $this->assertSame(BasicAuthenticationResult::MalformedCredentials, $result);
    }

    #[Test]
    public function loginAsUserFromAuthenticationHeader_givenPasswordContainingColon_returnsSuccess(): void
    {
        // Arrange
        $userService = app()->make(UserServiceInterface::class);
        $password    = 'some:password:with:colons';
        $user        = User::factory()->create(['password' => Hash::make($password)]);

        try {
            // Act
            $result = $userService->loginAsUserFromAuthenticationHeader($this->createRequestWithCredentials($user->email, $password));

            // Assert
            $this->assertSame(BasicAuthenticationResult::Success, $result);
        } finally {
            User::query()->where('id', $user->id)->delete();
        }
    }

    #[Test]
    public function loginAsUserFromAuthenticationHeader_givenWrongPassword_returnsCredentialsRejected(): void
    {
        // Arrange
        $userService = app()->make(UserServiceInterface::class);
        $user        = User::factory()->create(['password' => Hash::make('the-right-password')]);

        try {
            // Act
            $result = $userService->loginAsUserFromAuthenticationHeader($this->createRequestWithCredentials($user->email, 'the-wrong-password'));

            // Assert
            $this->assertSame(BasicAuthenticationResult::CredentialsRejected, $result);
        } finally {
            User::query()->where('id', $user->id)->delete();
        }
    }

    #[Test]
    public function loginAsUserFromAuthenticationHeader_givenUnknownUser_returnsCredentialsRejected(): void
    {
        // Arrange
        $userService = app()->make(UserServiceInterface::class);

        // Act
        $result = $userService->loginAsUserFromAuthenticationHeader(
            $this->createRequestWithCredentials('this-user-does-not-exist@example.com', 'password'),
        );

        // Assert
        $this->assertSame(BasicAuthenticationResult::CredentialsRejected, $result);
    }

    private function createRequestWithCredentials(string $username, string $password): Request
    {
        return $this->createRequestWithAuthorization(sprintf('Basic %s', base64_encode(sprintf('%s:%s', $username, $password))));
    }

    private function createRequestWithAuthorization(string $authorization): Request
    {
        $request = new Request();
        $request->headers->set('Authorization', $authorization);

        return $request;
    }
}
