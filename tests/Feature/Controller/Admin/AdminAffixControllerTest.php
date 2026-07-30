<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Affix;
use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class AdminAffixControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function get_asAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.affixes'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_asNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->be($user);

        // Act
        $response = $this->get(route('admin.affixes'));

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function edit_givenExistingAffix_returnsOk(): void
    {
        // Arrange
        $affix = Affix::query()->firstOrFail();

        // Act
        $response = $this->get(route('admin.affix.edit', $affix));

        // Assert
        $response->assertOk();
        $response->assertSee($affix->key);
    }

    #[Test]
    public function create_givenNoAffix_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.affix.new'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function savenew_givenValidData_createsAffixWithPlaceholderIconFileId(): void
    {
        // Arrange
        $key = 'TestAffixKey';

        try {
            // Act
            $response = $this->post(route('admin.affix.savenew'), [
                'key'         => $key,
                'affix_id'    => 999,
                'name'        => 'Test Affix',
                'description' => 'Test description',
            ]);

            // Assert
            $affix = Affix::query()->where('key', $key)->first();
            $this->assertNotNull($affix);
            $response->assertRedirect(route('admin.affix.edit', $affix));
            $this->assertSame(-1, $affix->icon_file_id);
        } finally {
            Affix::query()->where('key', $key)->delete();
        }
    }

    #[Test]
    public function update_givenValidData_updatesAffixWithoutTouchingIconFileId(): void
    {
        // Arrange
        $key   = 'TestUpdateAffixKey';
        $affix = Affix::create([
            'key'          => $key,
            'icon_file_id' => -1,
            'affix_id'     => 998,
            'name'         => 'Original name',
            'description'  => 'Original description',
        ]);

        try {
            // Act
            $response = $this->patch(route('admin.affix.update', $affix), [
                'key'         => $key,
                'affix_id'    => 998,
                'name'        => 'Updated name',
                'description' => 'Updated description',
            ]);

            // Assert
            $response->assertOk();

            $updated = Affix::query()->findOrFail($affix->id);
            $this->assertSame('Updated name', $updated->name);
            $this->assertSame('Updated description', $updated->description);
            $this->assertSame(-1, $updated->icon_file_id);
        } finally {
            $affix->delete();
        }
    }
}
