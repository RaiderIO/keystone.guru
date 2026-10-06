<?php

namespace Tests\Feature\App\Model\CombatLog;

use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
final class CombatLogNpcCharacteristicObservationTest extends PublicTestCase
{
    #[Test]
    public function with_givenPersistedObservation_loadsMainDatabaseRelations(): void
    {
        // Arrange
        $observation = CombatLogNpcCharacteristicObservation::factory()->create(['observed_on' => '2000-01-01']);

        try {
            // Act
            $retrieved = CombatLogNpcCharacteristicObservation::with(['npc', 'characteristic'])
                ->findOrFail($observation->id);

            // Assert
            $this->assertSame($observation->npc_id, $retrieved->getRelation('npc')?->getKey());
            $this->assertSame($observation->characteristic_id, $retrieved->getRelation('characteristic')?->getKey());
        } finally {
            CombatLogNpcCharacteristicObservation::where('id', $observation->id)->delete();
        }
    }
}
