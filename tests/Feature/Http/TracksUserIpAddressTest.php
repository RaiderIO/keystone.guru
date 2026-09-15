<?php

namespace Tests\Feature\Http;

use App\Http\Middleware\TracksUserIpAddress;
use App\Models\User;
use App\Models\UserIpAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('TracksUserIpAddress')]
final class TracksUserIpAddressTest extends PublicTestCase
{
    private const string IP_ADDRESS = '198.51.100.40';

    private const string OTHER_IP_ADDRESS = '198.51.100.41';

    #[Test]
    public function handle_givenRepeatedRequestsFromSameIp_writesTheIpOnce(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            Auth::login($user);

            // Act
            $this->handleRequest(self::IP_ADDRESS);
            $this->handleRequest(self::IP_ADDRESS);
            $this->handleRequest(self::IP_ADDRESS);

            // Assert
            $userIpAddresses = UserIpAddress::query()->where('user_id', $user->id)->get();
            $this->assertCount(1, $userIpAddresses);
            $this->assertSame(self::IP_ADDRESS, $userIpAddresses->first()->ip_address);
            $this->assertSame(1, $userIpAddresses->first()->count);
        } finally {
            $this->deleteUser($user);
        }
    }

    #[Test]
    public function handle_givenRequestFromNewIp_writesThatIpToo(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            Auth::login($user);
            $this->handleRequest(self::IP_ADDRESS);

            // Act
            $this->handleRequest(self::OTHER_IP_ADDRESS);

            // Assert
            $ipAddresses = UserIpAddress::query()->where('user_id', $user->id)->orderBy('ip_address')->pluck('ip_address');
            $this->assertSame([self::IP_ADDRESS, self::OTHER_IP_ADDRESS], $ipAddresses->all());
        } finally {
            $this->deleteUser($user);
        }
    }

    #[Test]
    public function handle_givenRequestAfterTrackIntervalPassed_incrementsCount(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            Auth::login($user);
            $this->handleRequest(self::IP_ADDRESS);
            $this->travel(61)->minutes();

            // Act
            $this->handleRequest(self::IP_ADDRESS);

            // Assert
            $userIpAddress = UserIpAddress::query()->where('user_id', $user->id)->sole();
            $this->assertSame(2, $userIpAddress->count);
        } finally {
            $this->travelBack();
            $this->deleteUser($user);
        }
    }

    #[Test]
    public function handle_givenAjaxRequest_writesNothing(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            Auth::login($user);

            // Act
            $this->handleRequest(self::IP_ADDRESS, true);

            // Assert
            $this->assertFalse(UserIpAddress::query()->where('user_id', $user->id)->exists());
        } finally {
            $this->deleteUser($user);
        }
    }

    #[Test]
    public function handle_givenGuest_writesNothing(): void
    {
        // Arrange
        $countBefore = UserIpAddress::query()->where('ip_address', self::OTHER_IP_ADDRESS)->count();

        // Act
        $response = $this->handleRequest(self::OTHER_IP_ADDRESS);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($countBefore, UserIpAddress::query()->where('ip_address', self::OTHER_IP_ADDRESS)->count());
    }

    private function handleRequest(string $ipAddress, bool $ajax = false): Response
    {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $ipAddress]);
        if ($ajax) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return new TracksUserIpAddress()->handle($request, static fn(): Response => new Response());
    }

    private function deleteUser(User $user): void
    {
        Auth::logout();
        UserIpAddress::query()->where('user_id', $user->id)->delete();
        $user->delete();
    }
}
