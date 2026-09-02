<?php

namespace Tests\Feature\Controller\Auth;

use App\Http\Controllers\Auth\RegisterController;
use App\Models\GameServerRegion;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

#[Group('Auth')]
final class RegisterControllerTest extends PublicTestCase
{
    #[Test]
    public function register_givenInvalidData_redirectsToRegisterWithErrors(): void
    {
        // Arrange
        $postData = [
            'name'  => sprintf('user%s', random_int(100000, 999999)),
            'email' => 'not-an-email',
        ];

        // Act
        $response = $this->post(route('register'), $postData);

        // Assert
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email', 'password', 'legal_agreed']);
    }

    #[Test]
    public function register_givenInvalidDataExpectingJson_returnsUnprocessableWithFieldErrors(): void
    {
        // Arrange
        $postData = [
            'name'     => sprintf('user%s', random_int(100000, 999999)),
            'email'    => 'not-an-email',
            'password' => 'short',
        ];

        // Act
        $response = $this->postJson(route('register'), $postData);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email', 'password', 'legal_agreed']);
    }

    #[Test]
    public function register_givenNonExistingRegion_returnsRegionError(): void
    {
        // Arrange - the placeholder used to submit -1, which was written to the database verbatim
        $postData = $this->validRegistrationData(['region' => '-1']);

        // Act
        $response = $this->postJson(route('register'), $postData);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['region']);
    }

    #[Test]
    public function register_givenNoRegion_createsUserWithDefaultRegion(): void
    {
        // Arrange
        $postData = $this->validRegistrationData(['region' => '']);
        $user     = null;

        try {
            // Act
            $response = $this->post(route('register'), $postData);

            // Assert
            $response->assertRedirect();
            $user = User::firstWhere('email', $postData['email']);
            $this->assertNotNull($user, 'Registration should have created the user');
            $this->assertEquals(
                GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION],
                $user->game_server_region_id,
            );
        } finally {
            $this->deleteRegisteredUser($user);
        }
    }

    #[Test]
    public function register_givenRegion_createsUserWithThatRegion(): void
    {
        // Arrange
        $europe   = GameServerRegion::ALL[GameServerRegion::EUROPE];
        $postData = $this->validRegistrationData(['region' => (string)$europe]);
        $user     = null;

        try {
            // Act
            $response = $this->post(route('register'), $postData);

            // Assert
            $response->assertRedirect();
            $user = User::firstWhere('email', $postData['email']);
            $this->assertNotNull($user, 'Registration should have created the user');
            $this->assertEquals($europe, $user->game_server_region_id);
        } finally {
            $this->deleteRegisteredUser($user);
        }
    }

    #[Test]
    public function register_givenValidDataExpectingJson_returnsCreatedSoTheFlashSurvivesTheReload(): void
    {
        // Arrange - a redirect response would be transparently followed by jquery and the hidden
        // GET would consume the flashed status message before the modal's page reload
        $postData = $this->validRegistrationData();
        $user     = null;

        try {
            // Act
            $response = $this->postJson(route('register'), $postData);

            // Assert
            $response->assertCreated();
            $user = User::firstWhere('email', $postData['email']);
            $this->assertNotNull($user, 'Registration should have created the user');
        } finally {
            auth()->logout();
            $this->deleteRegisteredUser($user);
        }
    }

    /**
     * A plain XHR (no explicit JSON Accept header) satisfies expectsJson() but not wantsJson(), so
     * the success and failure paths must both gate on expectsJson() - otherwise the same request
     * gets a JSON 422 on failure and a silently followed redirect on success, which is the
     * flash-eating extra hop #4003 removes.
     */
    #[Test]
    public function register_givenAjaxRequestWithoutJsonAcceptHeader_returnsJsonOnBothFailureAndSuccess(): void
    {
        // Arrange
        $ajaxHeaders = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => '*/*'];
        $invalidData = $this->validRegistrationData(['email' => 'not-an-email']);
        $validData   = $this->validRegistrationData();
        $user        = null;

        try {
            // Act
            $failureResponse = $this->post(route('register'), $invalidData, $ajaxHeaders);
            $successResponse = $this->post(route('register'), $validData, $ajaxHeaders);

            // Assert
            $failureResponse->assertUnprocessable();
            $failureResponse->assertJsonValidationErrors(['email']);

            $successResponse->assertCreated();
            $user = User::firstWhere('email', $validData['email']);
            $this->assertNotNull($user, 'Registration should have created the user');
        } finally {
            auth()->logout();
            $this->deleteRegisteredUser($user);
        }
    }

    #[Test]
    public function create_givenEmailAlreadyExists_throwsUniqueConstraintViolationException(): void
    {
        // Arrange - proves the DB's `users_email_unique` index actually throws
        // UniqueConstraintViolationException, which is what register()'s catch block relies on
        $method = new ReflectionMethod(RegisterController::class, 'create');
        $data   = $this->validRegistrationData();
        $user   = null;

        try {
            $user = $method->invoke(new RegisterController(), $data);

            // Act & Assert
            $this->expectException(UniqueConstraintViolationException::class);
            $method->invoke(new RegisterController(), $this->validRegistrationData(['email' => $data['email']]));
        } finally {
            $this->deleteRegisteredUser($user);
        }
    }

    /**
     * Reproduces #4440: a double-click submits the registration form twice with the same email.
     * Both requests pass validation before either has created a row, so the second `User::create()`
     * hits the database's unique index directly instead of failing the `unique:users` validation
     * rule. Mocks `create()` since the race can't be reproduced deterministically over real HTTP
     * requests - the first request's `unique:users` check would simply catch a second, sequential one.
     */
    #[Test]
    public function register_givenCreateRacesIntoUniqueConstraintViolationExpectingJson_returnsUnprocessableWithEmailError(): void
    {
        // Arrange
        $postData   = $this->validRegistrationData();
        $controller = $this->createPartialMockPublic(RegisterController::class, ['create']);
        $controller->method('create')->willThrowException(new UniqueConstraintViolationException(
            'mysql',
            'insert into `users` ...',
            [],
            new PDOException('Duplicate entry for key users_email_unique'),
        ));
        $this->app->instance(RegisterController::class, $controller);

        // Act
        $response = $this->postJson(route('register'), $postData);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function register_givenCreateRacesIntoUniqueConstraintViolationNotExpectingJson_redirectsToRegisterWithEmailError(): void
    {
        // Arrange
        $postData   = $this->validRegistrationData();
        $controller = $this->createPartialMockPublic(RegisterController::class, ['create']);
        $controller->method('create')->willThrowException(new UniqueConstraintViolationException(
            'mysql',
            'insert into `users` ...',
            [],
            new PDOException('Duplicate entry for key users_email_unique'),
        ));
        $this->app->instance(RegisterController::class, $controller);

        // Act
        $response = $this->post(route('register'), $postData);

        // Assert
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * The catch re-validates against the row that actually caused the collision rather than
     * hardcoding an "email taken" message - this proves the reported error comes from that real
     * validation failure (a genuine duplicate email, different name) and not just the fallback.
     */
    #[Test]
    public function register_givenCreateRacesIntoARealEmailCollision_reportsTheRealValidationErrorForEmail(): void
    {
        // Arrange - a real, already-registered email collides; the name in this request does not
        $existingUser = (new ReflectionMethod(RegisterController::class, 'create'))
            ->invoke(new RegisterController(), $this->validRegistrationData());

        try {
            $controller = $this->createPartialMockPublic(RegisterController::class, ['create']);
            $controller->method('create')->willThrowException(new UniqueConstraintViolationException(
                'mysql',
                'insert into `users` ...',
                [],
                new PDOException('Duplicate entry for key users_email_unique'),
            ));
            $this->app->instance(RegisterController::class, $controller);

            $postData = $this->validRegistrationData(['email' => $existingUser->email]);

            // Act
            $response = $this->postJson(route('register'), $postData);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['email']);
            $response->assertJsonMissingValidationErrors(['name']);
        } finally {
            $this->deleteRegisteredUser($existingUser);
        }
    }

    #[Test]
    public function register_givenNoLegalAgreedMs_createsUserAnyway(): void
    {
        // Arrange - the write-only legal_agreed_ms tracking was removed, so neither the form nor
        // the AJAX modal path posts the key anymore. Registration must not depend on it.
        $postData = $this->validRegistrationData();
        $this->assertArrayNotHasKey('legal_agreed_ms', $postData);
        $user = null;

        try {
            // Act
            $response = $this->post(route('register'), $postData);

            // Assert
            $response->assertRedirect();
            $user = User::firstWhere('email', $postData['email']);
            $this->assertNotNull($user, 'Registration should have created the user');
        } finally {
            $this->deleteRegisteredUser($user);
        }
    }

    #[Test]
    public function showRegistrationForm_givenGuest_rendersNoLegalAgreedMsHiddenField(): void
    {
        // Act
        $response = $this->get(route('register'));

        // Assert
        $response->assertOk();
        $response->assertDontSee('legal_agreed_ms', false);
    }

    #[Test]
    public function showRegistrationForm_givenGuest_rendersRegionSelectWithRegionIdsAsValues(): void
    {
        // Arrange
        $europe = GameServerRegion::ALL[GameServerRegion::EUROPE];

        // Act
        $response = $this->get(route('register'));

        // Assert - the placeholder must submit an empty value, and the options must keep region
        // ids as their values (array_merge would renumber them)
        $response->assertOk();
        // laravel-html renders an empty value attribute as a bare `value`, which still submits ""
        $response->assertSee('<option value>', false);
        $response->assertSee(sprintf('<option value="%d">', $europe), false);
    }

    /**
     * @param  array<string, string> $overrides
     * @return array<string, string>
     */
    private function validRegistrationData(array $overrides = []): array
    {
        $name = sprintf('user%s', random_int(100000, 999999));

        return array_merge([
            'name'                  => $name,
            'email'                 => sprintf('%s@example.com', $name),
            'region'                => '',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'legal_agreed'          => '1',
        ], $overrides);
    }

    private function deleteRegisteredUser(?User $user): void
    {
        if ($user === null) {
            return;
        }

        $user->roles()->sync([]);
        $user->delete();
    }
}
