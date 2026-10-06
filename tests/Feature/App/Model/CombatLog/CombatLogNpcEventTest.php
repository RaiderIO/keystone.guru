<?php

namespace Tests\Feature\App\Model\CombatLog;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\Npc\Npc;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
final class CombatLogNpcEventTest extends PublicTestCase
{
    #[Test]
    public function with_givenPersistedEvent_loadsMainDatabaseRelations(): void
    {
        // Arrange
        /** @var Npc $npc */
        $npc = Npc::query()->where('id', '>', 100000)->firstOrFail();
        /** @var Characteristic $characteristic */
        $characteristic = Characteristic::query()->firstOrFail();

        $event = null;

        try {
            $event = CombatLogNpcEvent::create([
                'npc_id'          => $npc->id,
                'event_type'      => CombatLogNpcEventType::CharacteristicAdded,
                'model_class'     => Characteristic::class,
                'model_id'        => $characteristic->id,
                'combat_log_path' => null,
            ]);

            // Act
            $retrieved = CombatLogNpcEvent::with(['npc'])->findOrFail($event->id);
            // The generic model relation cannot be eager loaded: its related class is read from the row
            $model = $retrieved->model()->first();

            // Assert
            $this->assertSame($npc->id, $retrieved->getRelation('npc')?->getKey());
            $this->assertSame($characteristic->id, $model?->getKey());
        } finally {
            $event?->delete();
        }
    }
}
