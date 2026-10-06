<?php

namespace Tests\Feature\App\Model\CombatLog;

use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\Spell\Spell;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
final class CombatLogSpellEventTest extends PublicTestCase
{
    #[Test]
    public function with_givenPersistedEvent_loadsMainDatabaseRelations(): void
    {
        // Arrange
        /** @var Spell $spell */
        $spell = Spell::query()->firstOrFail();

        $event = null;

        try {
            $event = CombatLogSpellEvent::create([
                'spell_id'        => $spell->id,
                'event_type'      => CombatLogSpellEventType::SpellCreated,
                'property'        => null,
                'combat_log_path' => null,
            ]);

            // Act
            $retrieved = CombatLogSpellEvent::with(['spell'])->findOrFail($event->id);

            // Assert
            $this->assertSame($spell->id, $retrieved->getRelation('spell')?->getKey());
        } finally {
            $event?->delete();
        }
    }
}
