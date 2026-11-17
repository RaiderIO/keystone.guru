<?php

namespace Tests\Feature\Controller\Team;

use App\Models\File;
use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Team')]
final class TeamControllerMenuSwitcherTest extends PublicTestCase
{
    #[Test]
    public function edit_givenMemberOfTeamWithLogoAndMarkupInName_returnsNameEscapedInsideTheOptionMarkup(): void
    {
        // Arrange
        $user       = null;
        $logoTeam   = null;
        $plainTeam  = null;
        $file       = null;
        $markupName = sprintf('<b>%s</b>', substr(uniqid(), -8));
        $plainName  = sprintf('Plain %s', substr(uniqid(), -8));

        try {
            $user = User::factory()->create();
            $user->addRole(Role::ROLE_USER);

            $logoTeam  = $this->createTeam($markupName);
            $plainTeam = $this->createTeam($plainName);

            $file = File::create([
                'model_id'    => $logoTeam->id,
                'model_class' => Team::class,
                'disk'        => 'local',
                'path'        => sprintf('uploads/%s&x.png', uniqid()),
            ]);
            $logoTeam->update(['icon_file_id' => $file->id]);

            TeamUser::create(['team_id' => $logoTeam->id, 'user_id' => $user->id, 'role' => TeamUser::ROLE_MEMBER]);
            TeamUser::create(['team_id' => $plainTeam->id, 'user_id' => $user->id, 'role' => TeamUser::ROLE_MEMBER]);

            // Act
            $response = $this->actingAs($user)->get(route('team.edit', $logoTeam));

            // Assert
            $response->assertOk();
            $html          = $response->getContent();
            $doubleEscaped = e(e($markupName));
            $singleEscaped = e($markupName);

            $this->assertStringContainsString(sprintf("style='max-height: 16px;'/> %s\"", $doubleEscaped), $html);
            $this->assertStringNotContainsString(sprintf("style='max-height: 16px;'/> %s\"", $singleEscaped), $html);
            $this->assertStringContainsString(sprintf("data-content=\"<img src='%s'", e(e($file->getURL()))), $html);
            $this->assertStringContainsString(sprintf('>%s</option>', e($plainName)), $html);
        } finally {
            $file?->delete();
            TeamUser::query()->where('user_id', $user?->id)->delete();
            $logoTeam?->delete();
            $plainTeam?->delete();
            $user?->delete();
        }
    }

    private function createTeam(string $name): Team
    {
        return Team::create([
            'public_key'   => Team::generateRandomPublicKey(),
            'name'         => $name,
            'description'  => '',
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
    }
}
