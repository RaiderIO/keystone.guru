<?php

namespace Tests\Feature\Console\Commands\OpenWorldNpc;

use App\Console\Commands\OpenWorldNpc\ReconcilePortraits;
use App\Models\Npc\OpenWorldNpc;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\FakesEnemyPortraits;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('OpenWorldNpc')]
final class ReconcilePortraitsTest extends PublicTestCase
{
    use FakesEnemyPortraits;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(0, OpenWorldNpc::query()->count(), 'open_world_npcs must start empty; an earlier test leaked rows');
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            OpenWorldNpc::query()->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenFlagsThatDifferFromTheFiles_setsTheFlagsToTheFiles(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits();
        // File, not flagged, earlier found unrenderable: gains its portrait
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 11]);
        // Flagged, no file: loses its portrait
        OpenWorldNpc::factory()->withPortrait()->create(['id' => 12]);
        // File and flagged: unchanged
        OpenWorldNpc::factory()->withPortrait()->create(['id' => 13]);
        // No file, not flagged: unchanged
        OpenWorldNpc::factory()->create(['id' => 14]);
        $disk->put('11.png', $this->createPortraitPng());
        $disk->put('13.png', $this->createPortraitPng());

        // Act & Assert
        $this->artisan(ReconcilePortraits::class)
            ->expectsOutputToContain('2 open-world NPCs have a portrait file, 2 do not')
            ->expectsOutputToContain('Now marked as having a portrait: 11')
            ->expectsOutputToContain('No longer marked as having a portrait: 12')
            ->expectsOutputToContain('Could not render (0): none')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(
            [11 => [true, false], 12 => [false, false], 13 => [true, false], 14 => [false, false]],
            $this->getPortraitStates(),
        );
    }

    #[Test]
    public function handle_givenNpcsThatCouldNotBeRendered_listsThemWithTheirDisplayIds(): void
    {
        // Arrange
        $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 21, 'display_id' => 211, 'name' => 'Hogger']);
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 22, 'display_id' => 222, 'name' => 'Rhahk\'Zor']);
        OpenWorldNpc::factory()->create(['id' => 23, 'display_id' => 233]);

        // Act & Assert
        $this->artisan(ReconcilePortraits::class)
            ->expectsOutputToContain('Could not render (2): 21 (display id 211, Hogger), 22 (display id 222, Rhahk\'Zor)')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame([21 => [false, true], 22 => [false, true], 23 => [false, false]], $this->getPortraitStates());
    }

    #[Test]
    public function handle_givenAnEmptyTable_changesNothing(): void
    {
        // Arrange
        $this->fakeEnemyPortraits();

        // Act & Assert
        $this->artisan(ReconcilePortraits::class)
            ->expectsOutputToContain('0 open-world NPCs have a portrait file, 0 do not')
            ->expectsOutputToContain('Now marked as having a portrait: none')
            ->assertExitCode(Command::SUCCESS);
    }

    #[Test]
    public function handle_givenNoPortraitDirectory_failsAndChangesNothing(): void
    {
        // Arrange
        $this->fakeEnemyPortraits(withUnknownPortrait: false);
        OpenWorldNpc::factory()->withPortrait()->create(['id' => 31]);

        // Act & Assert
        $this->artisan(ReconcilePortraits::class)
            ->expectsOutputToContain('holds no unknown.png')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame([31 => [true, false]], $this->getPortraitStates());
    }

    /**
     * @return array<int, array{0: bool, 1: bool}> Per NPC id: has_portrait, portrait_unrenderable
     */
    private function getPortraitStates(): array
    {
        return OpenWorldNpc::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(static fn(OpenWorldNpc $npc) => [$npc->id => [$npc->has_portrait, $npc->portrait_unrenderable]])
            ->all();
    }
}
