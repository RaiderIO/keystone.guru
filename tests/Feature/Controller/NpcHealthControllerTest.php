<?php

namespace Tests\Feature\Controller;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Npc')]
final class NpcHealthControllerTest extends PublicTestCase
{
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

    private function getSeededNpcHealthWithDungeons(): NpcHealth
    {
        // Unordered, MySQL flips between plans on its sampled statistics and returns a different row per run
        return NpcHealth::query()
            ->whereHas('npc', static fn(Builder $builder) => $builder->whereHas('dungeons'))
            ->orderBy('id')
            ->firstOrFail();
    }
}
