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

        try {
            // Act
            $response = $this->get(route('admin.affixes'));

            // Assert
            $response->assertForbidden();
        } finally {
            $user->delete();
        }
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
    public function create_givenAllDeclaredAffixIdsAlreadyUsed_showsNoAvailableIdsWarning(): void
    {
        // Arrange - every Affix::ALL id already has a seeded row in the fixture DB, so this is the
        // only reachable state for the create page today.
        $this->assertEmpty(Affix::getAvailableIds(), 'Fixture DB assumption changed - add a covering test for the id-select branch.');

        // Act
        $response = $this->get(route('admin.affix.new'));

        // Assert
        $response->assertOk();
        $response->assertSee(__('view_admin.affix.edit.no_available_ids'));
    }

    #[Test]
    public function savenew_givenNoId_returnsValidationError(): void
    {
        // Arrange
        $key = 'TestAffixNoId';

        // Act
        $response = $this->post(route('admin.affix.savenew'), [
            'key'         => $key,
            'affix_id'    => 999,
            'name'        => 'Test Affix',
            'description' => 'Test description',
        ]);

        // Assert
        $response->assertSessionHasErrors('id');
        $this->assertFalse(Affix::query()->where('key', $key)->exists());
    }

    #[Test]
    public function savenew_givenIdNotDeclaredAsAffixConstant_returnsValidationError(): void
    {
        // Arrange
        $key = 'TestAffixUndeclaredId';

        // Act
        $response = $this->post(route('admin.affix.savenew'), [
            'id'          => 90000,
            'key'         => $key,
            'affix_id'    => 999,
            'name'        => 'Test Affix',
            'description' => 'Test description',
        ]);

        // Assert
        $response->assertSessionHasErrors('id');
        $this->assertFalse(Affix::query()->where('key', $key)->exists());
    }

    #[Test]
    public function savenew_givenIdAlreadyTaken_returnsValidationError(): void
    {
        // Arrange
        $key           = 'TestAffixTakenId';
        $alreadyUsedId = Affix::query()->value('id');

        // Act
        $response = $this->post(route('admin.affix.savenew'), [
            'id'          => $alreadyUsedId,
            'key'         => $key,
            'affix_id'    => 999,
            'name'        => 'Test Affix',
            'description' => 'Test description',
        ]);

        // Assert
        $response->assertSessionHasErrors('id');
        $this->assertFalse(Affix::query()->where('key', $key)->exists());
    }

    #[Test]
    public function update_givenValidData_updatesAffix(): void
    {
        // Arrange
        $key   = 'TestUpdateAffixKey';
        $affix = Affix::create([
            'key'         => $key,
            'affix_id'    => 998,
            'name'        => 'Original name',
            'description' => 'Original description',
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
        } finally {
            $affix->delete();
        }
    }
}
