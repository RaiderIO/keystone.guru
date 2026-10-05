<?php

namespace Tests\Feature\Controller;

use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Npc')]
final class NpcHealthControllerTest extends PublicTestCase
{
    use CreatesNpc;

    private const int CLASSIFICATIONLESS_NPC_ID = 999999601;

    /** npcs.json seeds a few NPCs with this classification_id (e.g. Freehold's emissaries 155432-155434); no npc_classifications row has it */
    private const int CLASSIFICATION_ID_WITHOUT_ROW = 0;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));

        // Eloquent only flags models hydrated as part of a multi-row result for lazy-loading
        // prevention, so a route-bound model never trips it on its own - flag the bound models
        // explicitly so a relation the controller stops eager-loading fails these tests.
        Route::bind('npc', static function (string $value): Npc {
            $npc                      = Npc::query()->findOrFail((int)explode('-', $value, 2)[0]);
            $npc->preventsLazyLoading = true;

            return $npc;
        });
        Route::bind('npcHealth', static function (string $value): NpcHealth {
            $npcHealth                      = NpcHealth::query()->findOrFail((int)$value);
            $npcHealth->preventsLazyLoading = true;

            return $npcHealth;
        });
    }

    /**
     * The edit page reads $npc->dungeons, $npc->npcHealths and $npcHealth->gameVersion
     * (PHP-LARAVEL-W8, #4438).
     */
    #[Test]
    public function edit_givenNpcHealthOfNpcWithDungeons_returnsOk(): void
    {
        // Arrange
        $npcHealth = $this->getSeededNpcHealthWithDungeons();

        // Act
        $response = $this->get(route('admin.npc.npchealth.edit', ['npc' => $npcHealth->npc_id, 'npcHealth' => $npcHealth->id]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function edit_givenDungeonWithAClassificationlessNpc_returnsOkAndListsThatNpc(): void
    {
        // Arrange
        $npcHealth          = $this->getSeededNpcHealthWithDungeons();
        $seededNpc          = Npc::query()->with('dungeons')->findOrFail($npcHealth->npc_id);
        $neighbourNpc       = null;
        $neighbourNpcHealth = null;

        try {
            $neighbourNpc = Npc::query()->create([
                'id'                => self::CLASSIFICATIONLESS_NPC_ID,
                'game_version_id'   => $npcHealth->game_version_id,
                'classification_id' => self::CLASSIFICATION_ID_WITHOUT_ROW,
                'npc_type_id'       => $seededNpc->npc_type_id,
                'npc_class_id'      => $seededNpc->npc_class_id,
                'name'              => 'Test Npc - classificationless neighbour',
                'aggressiveness'    => $seededNpc->aggressiveness,
                'dangerous'         => false,
                'truesight'         => false,
                'runs_away_in_fear' => false,
            ]);
            $neighbourNpc->dungeons()->attach($seededNpc->dungeons->firstOrFail()->id);
            $neighbourNpcHealth = NpcHealth::query()->create([
                'npc_id'          => $neighbourNpc->id,
                'game_version_id' => $npcHealth->game_version_id,
                'health'          => 123456,
            ]);

            // Act
            $response = $this->get(route('admin.npc.npchealth.edit', ['npc' => $npcHealth->npc_id, 'npcHealth' => $npcHealth->id]));

            // Assert
            $response->assertOk();
            $response->assertSee('Test Npc - classificationless neighbour');
        } finally {
            $neighbourNpcHealth?->delete();
            $neighbourNpc?->delete();
        }
    }

    #[Test]
    public function create_givenNpcWithDungeons_returnsOk(): void
    {
        // Arrange
        $npcHealth = $this->getSeededNpcHealthWithDungeons();

        // Act
        $response = $this->get(route('admin.npc.npchealth.new', ['npc' => $npcHealth->npc_id]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function savenew_givenValidHealth_createsNpcHealthAndRedirectsToItsEditPage(): void
    {
        // Arrange
        $npc = $this->createNpcInDatabase();

        try {
            // Act
            $response = $this->post(route('admin.npc.npchealth.savenew', ['npc' => $npc->id]), [
                'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
                'health'          => '1,234,567',
                'percentage'      => 100,
            ]);

            // Assert
            $npcHealth = NpcHealth::query()->where('npc_id', $npc->id)->firstOrFail();
            $response->assertRedirect(route('admin.npc.npchealth.edit', ['npc' => $npc, 'npcHealth' => $npcHealth]));
            $response->assertSessionHas('status', __('view_admin.npchealth.flash.npc_health_created'));
            $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL], $npcHealth->game_version_id);
            $this->assertSame(1234567, $npcHealth->health);
            $this->assertNull($npcHealth->percentage);
        } finally {
            NpcHealth::query()->where('npc_id', $npc->id)->delete();
        }
    }

    #[Test]
    public function savenew_givenHealthGroupedWithSpaces_storesTheWholeNumber(): void
    {
        // Arrange
        $npc = $this->createNpcInDatabase();

        try {
            // Act
            $response = $this->post(route('admin.npc.npchealth.savenew', ['npc' => $npc->id]), [
                'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
                'health'          => '1 234 567',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();
            $this->assertSame(1234567, NpcHealth::query()->where('npc_id', $npc->id)->firstOrFail()->health);
        } finally {
            NpcHealth::query()->where('npc_id', $npc->id)->delete();
        }
    }

    #[Test]
    public function savenew_givenHealthBeyondASignedInt_storesItWhole(): void
    {
        // Arrange - a raid boss, or health scaled up from a low observed percentage, easily passes 2,147,483,647
        $npc = $this->createNpcInDatabase();

        try {
            // Act
            $response = $this->post(route('admin.npc.npchealth.savenew', ['npc' => $npc->id]), [
                'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
                'health'          => '3,000,000,000',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();
            $this->assertSame(3000000000, NpcHealth::query()->where('npc_id', $npc->id)->firstOrFail()->health);
        } finally {
            NpcHealth::query()->where('npc_id', $npc->id)->delete();
        }
    }

    #[Test]
    public function savenew_givenNonNumericHealth_returnsValidationErrorAndCreatesNothing(): void
    {
        // Arrange
        $npc = $this->createNpcInDatabase();

        try {
            // Act
            $response = $this->post(route('admin.npc.npchealth.savenew', ['npc' => $npc->id]), [
                'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
                'health'          => 'lots',
            ]);

            // Assert
            $response->assertSessionHasErrors('health');
            $this->assertFalse(NpcHealth::query()->where('npc_id', $npc->id)->exists());
        } finally {
            NpcHealth::query()->where('npc_id', $npc->id)->delete();
        }
    }

    #[Test]
    public function update_givenValidHealth_updatesNpcHealthAndRedirectsToItsEditPage(): void
    {
        // Arrange
        $npc       = $this->createNpcInDatabase();
        $npcHealth = NpcHealth::query()->create([
            'npc_id'          => $npc->id,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'health'          => 1000,
        ]);

        try {
            // Act
            $response = $this->patch(route('admin.npc.npchealth.update', ['npc' => $npc->id, 'npcHealth' => $npcHealth->id]), [
                'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
                'health'          => '2.500',
                'percentage'      => 50,
            ]);

            // Assert
            $response->assertRedirect(route('admin.npc.npchealth.edit', ['npc' => $npc, 'npcHealth' => $npcHealth]));
            $response->assertSessionHas('status', __('view_admin.npchealth.flash.npc_health_updated'));
            $npcHealth->refresh();
            $this->assertSame(2500, $npcHealth->health);
            $this->assertSame(50, $npcHealth->percentage);
        } finally {
            NpcHealth::query()->where('npc_id', $npc->id)->delete();
        }
    }

    #[Test]
    public function delete_givenNpcHealth_deletesItAndRedirectsToTheNpc(): void
    {
        // Arrange
        $npc       = $this->createNpcInDatabase();
        $npcHealth = NpcHealth::query()->create([
            'npc_id'          => $npc->id,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'health'          => 1000,
        ]);

        try {
            // Act
            $response = $this->delete(route('admin.npc.npchealth.delete', ['npc' => $npc->id, 'npcHealth' => $npcHealth->id]));

            // Assert
            $response->assertRedirect(route('admin.npc.edit', ['npc' => $npc]));
            $response->assertSessionHas('status', __('view_admin.npchealth.flash.npc_health_deleted'));
            $this->assertFalse(NpcHealth::query()->whereKey($npcHealth->id)->exists());
        } finally {
            NpcHealth::query()->where('npc_id', $npc->id)->delete();
        }
    }

    #[Test]
    public function update_givenMissingGameVersion_returnsValidationErrorAndKeepsTheHealth(): void
    {
        // Arrange
        $npc       = $this->createNpcInDatabase();
        $npcHealth = NpcHealth::query()->create([
            'npc_id'          => $npc->id,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'health'          => 1000,
        ]);

        try {
            // Act
            $response = $this->patch(route('admin.npc.npchealth.update', ['npc' => $npc->id, 'npcHealth' => $npcHealth->id]), [
                'health' => '2500',
            ]);

            // Assert
            $response->assertSessionHasErrors('game_version_id');
            $this->assertSame(1000, $npcHealth->refresh()->health);
        } finally {
            NpcHealth::query()->where('npc_id', $npc->id)->delete();
        }
    }

    #[Test]
    public function edit_givenNpcHealthOfAnotherNpc_returnsNotFound(): void
    {
        // Arrange
        $npc       = $this->createNpcInDatabase();
        $otherNpc  = $this->createNpcInDatabase();
        $npcHealth = NpcHealth::query()->create([
            'npc_id'          => $otherNpc->id,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'health'          => 1000,
        ]);

        try {
            // Act
            $response = $this->get(route('admin.npc.npchealth.edit', ['npc' => $npc->id, 'npcHealth' => $npcHealth->id]));

            // Assert
            $response->assertNotFound();
        } finally {
            NpcHealth::query()->where('npc_id', $otherNpc->id)->delete();
        }
    }

    #[Test]
    public function update_givenNpcHealthOfAnotherNpc_returnsNotFoundAndKeepsTheHealth(): void
    {
        // Arrange
        $npc       = $this->createNpcInDatabase();
        $otherNpc  = $this->createNpcInDatabase();
        $npcHealth = NpcHealth::query()->create([
            'npc_id'          => $otherNpc->id,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'health'          => 1000,
        ]);

        try {
            // Act
            $response = $this->patch(route('admin.npc.npchealth.update', ['npc' => $npc->id, 'npcHealth' => $npcHealth->id]), [
                'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
                'health'          => '2500',
            ]);

            // Assert
            $response->assertNotFound();
            $this->assertSame(1000, $npcHealth->refresh()->health);
        } finally {
            NpcHealth::query()->where('npc_id', $otherNpc->id)->delete();
        }
    }

    #[Test]
    public function delete_givenNpcHealthOfAnotherNpc_returnsNotFoundAndKeepsTheHealth(): void
    {
        // Arrange
        $npc       = $this->createNpcInDatabase();
        $otherNpc  = $this->createNpcInDatabase();
        $npcHealth = NpcHealth::query()->create([
            'npc_id'          => $otherNpc->id,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'health'          => 1000,
        ]);

        try {
            // Act
            $response = $this->delete(route('admin.npc.npchealth.delete', ['npc' => $npc->id, 'npcHealth' => $npcHealth->id]));

            // Assert
            $response->assertNotFound();
            $this->assertTrue(NpcHealth::query()->whereKey($npcHealth->id)->exists());
        } finally {
            NpcHealth::query()->where('npc_id', $otherNpc->id)->delete();
        }
    }

    #[Test]
    public function edit_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $npcHealth = $this->getSeededNpcHealthWithDungeons();
        $user      = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        try {
            // Act
            $response = $this->actingAs($user)
                ->get(route('admin.npc.npchealth.edit', ['npc' => $npcHealth->npc_id, 'npcHealth' => $npcHealth->id]));

            // Assert
            $response->assertForbidden();
        } finally {
            $user->delete();
        }
    }

    private function getSeededNpcHealthWithDungeons(): NpcHealth
    {
        // Unordered, MySQL flips between plans on its sampled statistics and returns a different row per run
        return NpcHealth::query()
            ->whereHas('npc', static fn(Builder $builder) => $builder->whereHas('dungeons'))
            ->orderBy('id')
            ->firstOrFail();
    }
}
