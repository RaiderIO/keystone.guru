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
        return NpcHealth::query()
            ->whereHas('npc', static fn(Builder $builder) => $builder->whereHas('dungeons'))
            ->firstOrFail();
    }
}
