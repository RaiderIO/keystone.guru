<?php

namespace Tests\Feature\Controller\Compendium;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\Fixtures\Traits\CreatesSpell;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
#[Group('EncounterMechanics')]
final class NpcCompendiumControllerEncounterMechanicsTest extends PublicTestCase
{
    use CreatesNpc;
    use CreatesSpell;

    private const int ENCOUNTER_ID = 999_999_101;

    #[\Override]
    protected function tearDown(): void
    {
        try {
            NpcEncounterMechanic::query()->whereIn('encounter_id', [self::ENCOUNTER_ID, self::ENCOUNTER_ID + 1])->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function show_givenABossWithMechanics_listsEveryMechanicOfItsEncounter(): void
    {
        // Arrange
        $boss          = $this->createBoss('Test Sentinel');
        $otherBoss     = $this->createBoss('Test Kezkitt');
        $add           = $this->createNpcInDatabase(['name' => 'Test Shivercore']);
        $friendly      = $this->createNpcInDatabase(['name' => 'Test Zuljarra']);
        $immunitySpell = $this->createSpell(['name' => 'Test Ssscavenging']);
        $movementSpell = $this->createSpell(['name' => 'Test Nether Dash']);
        $this->createMechanic(NpcEncounterMechanicType::Boss, ['npc_id' => $boss->id]);
        $this->createMechanic(NpcEncounterMechanicType::Boss, ['npc_id' => $otherBoss->id]);
        $this->createMechanic(NpcEncounterMechanicType::Adds, ['npc_id' => $add->id, 'first_ms' => 29_500, 'cooldown_ms' => 63_000, 'count' => 2, 'run_count' => 12, 'total_run_count' => 14]);
        $this->createMechanic(NpcEncounterMechanicType::Adds, ['npc_id' => $add->id, 'first_ms' => null, 'first_health_pct' => 60.0, 'cooldown_ms' => null, 'count' => 3]);
        $this->createMechanic(NpcEncounterMechanicType::Invulnerable, ['npc_id' => $boss->id, 'spell_id' => $immunitySpell->id, 'first_ms' => 26_100, 'cooldown_ms' => 55_000, 'duration_ms' => 7_900]);
        $this->createMechanic(NpcEncounterMechanicType::SharedHealth, ['npc_id' => $boss->id, 'count' => 2, 'data' => ['npc_ids' => [$boss->id, $otherBoss->id]]]);
        $this->createMechanic(NpcEncounterMechanicType::Movement, ['npc_id' => null, 'spell_id' => $movementSpell->id, 'first_ms' => 15_000, 'cooldown_ms' => 35_000]);
        $this->createMechanic(NpcEncounterMechanicType::Friendly, ['npc_id' => $friendly->id]);
        $this->createMechanic(NpcEncounterMechanicType::AffixExempt, ['npc_id' => $add->id]);
        // Another encounter's mechanic is not this boss's
        $this->createMechanic(NpcEncounterMechanicType::Movement, ['encounter_id' => self::ENCOUNTER_ID + 1, 'npc_id' => null, 'spell_id' => $movementSpell->id, 'first_ms' => 99_000]);

        // Act
        $response = $this->get(route('npc.compendium.show', $boss));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__('view_compendium.npc.sections.encounter_mechanics.title'));
        $response->assertSeeTextInOrder([
            'Summons 2x Test Shivercore at 0:30, then every 1:03',
            '(seen in 12 of 14 runs)',
            'Summons 3x Test Shivercore at 60% health',
            'Becomes immune during Test Ssscavenging at 0:26, then every 0:55, for about 8 s',
            'Shares health with Test Kezkitt',
            'Forces the party to move with Test Nether Dash at 0:15, then every 0:35',
            'Test Zuljarra fights alongside the party',
            'Test Shivercore is not affected by Fortified or Tyrannical',
        ]);
        $response->assertDontSeeText('Test Sentinel and');
        $response->assertDontSeeText('at 1:39');
        $response->assertSee(sprintf('href="%s"', route('npc.compendium.show', $add)), false);
        $response->assertSee(sprintf('href="%s"', route('npc.compendium.show', $otherBoss)), false);
        $response->assertSee(sprintf('href="%s"', route('spell.compendium.show', $immunitySpell)), false);
    }

    #[Test]
    public function show_givenABossSharingHealthWithCopiesOfItself_saysHowManyShareOnePool(): void
    {
        // Arrange
        $boss = $this->createBoss('Test Sentinel');
        $this->createMechanic(NpcEncounterMechanicType::Boss, ['npc_id' => $boss->id]);
        $this->createMechanic(NpcEncounterMechanicType::SharedHealth, ['npc_id' => $boss->id, 'count' => 3, 'data' => ['npc_ids' => [$boss->id]]]);

        // Act
        $response = $this->get(route('npc.compendium.show', $boss));

        // Assert
        $response->assertOk();
        $response->assertSeeText('3x Test Sentinel share one health pool');
    }

    #[Test]
    public function show_givenAnAddOfAnEncounter_saysWhichBossItBelongsTo(): void
    {
        // Arrange
        $boss = $this->createBoss('Test Sentinel');
        $add  = $this->createNpcInDatabase(['name' => 'Test Shivercore']);
        $this->createMechanic(NpcEncounterMechanicType::Boss, ['npc_id' => $boss->id]);
        $this->createMechanic(NpcEncounterMechanicType::Adds, ['npc_id' => $add->id, 'run_count' => 4, 'total_run_count' => 5]);
        $this->createMechanic(NpcEncounterMechanicType::AffixExempt, ['npc_id' => $add->id, 'run_count' => 3, 'total_run_count' => 5]);

        // Act
        $response = $this->get(route('npc.compendium.show', $add));

        // Assert
        $response->assertOk();
        $response->assertSeeTextInOrder([
            'Summoned during the Test Sentinel encounter',
            '(seen in 4 of 5 runs)',
            'Not affected by Fortified or Tyrannical',
            '(seen in 3 of 5 runs)',
        ]);
        $response->assertSee(sprintf('href="%s"', route('npc.compendium.show', $boss)), false);
        $response->assertDontSeeText('Summons');
    }

    #[Test]
    public function show_givenAnNpcWithoutMechanics_rendersNoBossMechanicsSection(): void
    {
        // Arrange
        $boss  = $this->createBoss('Test Sentinel');
        $add   = $this->createNpcInDatabase(['name' => 'Test Shivercore']);
        $other = $this->createNpcInDatabase(['name' => 'Test Bystander']);
        $this->createMechanic(NpcEncounterMechanicType::Boss, ['npc_id' => $boss->id]);
        $this->createMechanic(NpcEncounterMechanicType::Adds, ['npc_id' => $add->id]);

        // Act
        $otherResponse = $this->get(route('npc.compendium.show', $other));
        $bossResponse  = $this->get(route('npc.compendium.show', $boss));

        // Assert
        $otherResponse->assertOk();
        $otherResponse->assertDontSeeText(__('view_compendium.npc.sections.encounter_mechanics.title'));
        $bossResponse->assertSeeText(__('view_compendium.npc.sections.encounter_mechanics.title'));
    }

    private function createBoss(string $name): Npc
    {
        return $this->createNpcInDatabase([
            'name'              => $name,
            'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createMechanic(NpcEncounterMechanicType $type, array $attributes): void
    {
        NpcEncounterMechanic::factory()->create(array_merge([
            'dungeon_id'      => 999_999,
            'encounter_id'    => self::ENCOUNTER_ID,
            'type'            => $type,
            'run_count'       => 5,
            'total_run_count' => 5,
        ], $attributes));
    }
}
