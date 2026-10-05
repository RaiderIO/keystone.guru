<?php

namespace Tests\Feature\App\Model\CombatLog;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
final class CombatLogSpellPropertyObservationTest extends PublicTestCase
{
    #[Test]
    public function with_givenPersistedObservation_loadsMainDatabaseRelations(): void
    {
        // Arrange
        $observation = CombatLogSpellPropertyObservation::factory()->create(['observed_on' => '2000-01-01']);

        try {
            // Act
            $retrieved = CombatLogSpellPropertyObservation::with(['spell'])->findOrFail($observation->id);

            // Assert
            $this->assertSame($observation->spell_id, $retrieved->getRelation('spell')?->getKey());
        } finally {
            CombatLogSpellPropertyObservation::where('id', $observation->id)->delete();
        }
    }
}
