<?php

namespace Tests\Feature\Controller\Team;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\GrantsPatreonBenefits;
use Tests\TestCases\PublicTestCase;

/**
 * A team's custom URL (its vanity key) replaces the public key in the team's URL, and is only
 * offered to whoever holds the custom URLs Patreon benefit.
 */
#[Group('Controller')]
#[Group('Team')]
final class TeamControllerVanityKeyTest extends PublicTestCase
{
    use GrantsPatreonBenefits;

    private const string VANITY_KEY = 'my-cool-team';

    private User $owner;

    private Team $team;

    private TeamUser $teamUser;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->owner->addRole(Role::firstWhere('name', Role::ROLE_USER));

        $this->team = $this->createTeam();

        $this->teamUser = TeamUser::create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'role'    => TeamUser::ROLE_ADMIN,
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->teamUser->delete();
            $this->team->delete();
            $this->owner->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function update_givenVanityKeyFromEntitledUser_storesItAndServesTheTeamThere(): void
    {
        // Arrange
        $this->grantCustomUrlsBenefit($this->owner);

        // Act
        $response = $this->actingAs($this->owner)->patch($this->updateUrl(), [
            'description' => '',
            'vanity_key'  => self::VANITY_KEY,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertSame(self::VANITY_KEY, $this->team->refresh()->vanity_key);
        $this->assertSame(self::VANITY_KEY, $this->team->getRouteKey());
        $this->actingAs($this->owner)->get(sprintf('/team/%s', self::VANITY_KEY))->assertOk();
    }

    #[Test]
    public function update_givenMixedCaseVanityKey_storesItLowercased(): void
    {
        // Arrange
        $this->grantCustomUrlsBenefit($this->owner);

        // Act
        $response = $this->actingAs($this->owner)->patch($this->updateUrl(), [
            'description' => '',
            'vanity_key'  => ' My-Cool-Team ',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertSame(self::VANITY_KEY, $this->team->refresh()->vanity_key);
    }

    #[Test]
    public function update_givenEmptyVanityKey_clearsItAgain(): void
    {
        // Arrange
        $this->grantCustomUrlsBenefit($this->owner);
        $this->team->update(['vanity_key' => self::VANITY_KEY]);

        // Act
        $response = $this->actingAs($this->owner)->patch($this->updateUrl(), [
            'description' => '',
            'vanity_key'  => '',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertNull($this->team->refresh()->vanity_key);
    }

    #[Test]
    public function update_givenVanityKeyFromUnentitledUser_returnsValidationErrorAndLeavesTheTeamUntouched(): void
    {
        // Act
        $response = $this->actingAs($this->owner)->patch($this->updateUrl(), [
            'description' => '',
            'vanity_key'  => self::VANITY_KEY,
        ]);

        // Assert
        $response->assertSessionHasErrors('vanity_key');
        $this->assertNull($this->team->refresh()->vanity_key);
    }

    #[Test]
    #[DataProvider('invalidVanityKeyProvider')]
    public function update_givenInvalidVanityKey_returnsValidationError(string $vanityKey): void
    {
        // Arrange
        $this->grantCustomUrlsBenefit($this->owner);

        // Act
        $response = $this->actingAs($this->owner)->patch($this->updateUrl(), [
            'description' => '',
            'vanity_key'  => $vanityKey,
        ]);

        // Assert
        $response->assertSessionHasErrors('vanity_key');
        $this->assertNull($this->team->refresh()->vanity_key);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidVanityKeyProvider(): array
    {
        return [
            'too short'          => ['ab'],
            'too long'           => [str_repeat('a', 49)],
            'underscore'         => ['my_cool_team'],
            'space'              => ['my cool team'],
            'leading dash'       => ['-my-cool-team'],
            'trailing dash'      => ['my-cool-team-'],
            'consecutive dashes' => ['my--cool-team'],
            'slash'              => ['my/cool/team'],
            'reserved new'       => ['new'],
            'reserved invite'    => ['invite'],
        ];
    }

    #[Test]
    public function update_givenVanityKeyTakenByAnotherTeam_returnsValidationError(): void
    {
        // Arrange
        $this->grantCustomUrlsBenefit($this->owner);
        $otherTeam = $this->createTeam(['vanity_key' => self::VANITY_KEY]);

        try {
            // Act
            $response = $this->actingAs($this->owner)->patch($this->updateUrl(), [
                'description' => '',
                'vanity_key'  => self::VANITY_KEY,
            ]);

            // Assert
            $response->assertSessionHasErrors('vanity_key');
            $this->assertNull($this->team->refresh()->vanity_key);
        } finally {
            $otherTeam->delete();
        }
    }

    #[Test]
    public function update_givenVanityKeyMatchingAnotherTeamsPublicKey_returnsValidationError(): void
    {
        // Arrange
        $this->grantCustomUrlsBenefit($this->owner);
        $otherTeam = $this->createTeam(['public_key' => 'abcdefg']);

        try {
            // Act
            $response = $this->actingAs($this->owner)->patch($this->updateUrl(), [
                'description' => '',
                'vanity_key'  => 'abcdefg',
            ]);

            // Assert
            $response->assertSessionHasErrors('vanity_key');
            $this->assertNull($this->team->refresh()->vanity_key);
        } finally {
            $otherTeam->delete();
        }
    }

    #[Test]
    public function edit_givenPublicKeyUrlWhileVanityKeyIsSet_redirectsToTheCustomUrl(): void
    {
        // Arrange
        $this->team->update(['vanity_key' => self::VANITY_KEY]);

        // Act
        $response = $this->actingAs($this->owner)->get(sprintf('/team/%s', $this->team->public_key));

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('team.edit', $this->team));
    }

    #[Test]
    public function edit_givenEntitledUser_rendersTheCustomUrlField(): void
    {
        // Arrange
        $this->grantCustomUrlsBenefit($this->owner);

        // Act
        $response = $this->actingAs($this->owner)->get(route('team.edit', $this->team));

        // Assert
        $response->assertOk();
        $response->assertSee(__('view_common.team.details.vanity_key'));
    }

    #[Test]
    public function edit_givenUnentitledUser_hidesTheCustomUrlField(): void
    {
        // Act
        $response = $this->actingAs($this->owner)->get(route('team.edit', $this->team));

        // Assert
        $response->assertOk();
        $response->assertDontSee(__('view_common.team.details.vanity_key'));
    }

    #[Test]
    public function resolveRouteBinding_givenVanityKey_resolvesTheTeam(): void
    {
        // Arrange
        $this->team->update(['vanity_key' => self::VANITY_KEY]);

        // Act
        $resolved = new Team()->resolveRouteBinding(self::VANITY_KEY);

        // Assert
        $this->assertNotNull($resolved);
        $this->assertSame($this->team->id, $resolved->id);
    }

    #[Test]
    public function resolveRouteBinding_givenPublicKeyWhileVanityKeyIsSet_resolvesTheTeam(): void
    {
        // Arrange
        $this->team->update(['vanity_key' => self::VANITY_KEY]);

        // Act
        $resolved = new Team()->resolveRouteBinding($this->team->public_key);

        // Assert
        $this->assertNotNull($resolved);
        $this->assertSame($this->team->id, $resolved->id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createTeam(array $attributes = []): Team
    {
        return Team::create($attributes + [
            'public_key'               => Team::generateRandomPublicKey(),
            'name'                     => sprintf('Vanity Key Test Team %s', Team::generateRandomPublicKey()),
            'description'              => '',
            'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
            'default_role'             => TeamUser::ROLE_MEMBER,
            'route_publishing_enabled' => false,
        ]);
    }

    private function updateUrl(): string
    {
        return route('team.update', $this->team->public_key);
    }

    private function grantCustomUrlsBenefit(User $user): void
    {
        $this->grantPatreonBenefit($user, PatreonBenefit::CUSTOM_URLS);
    }
}
