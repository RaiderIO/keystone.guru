<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogObservationController;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Laratrust\Role;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('CombatLog')]
#[Group('APICombatLogObservation')]
final class APICombatLogObservationControllerTest extends PublicTestCase
{
    /** @var array<int, int> */
    private array $createdSpellObservationIds = [];

    /** @var array<int, int> */
    private array $createdNpcObservationIds = [];

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogSpellPropertyObservation::query()->whereIn('id', $this->createdSpellObservationIds)->delete();
            CombatLogNpcCharacteristicObservation::query()->whereIn('id', $this->createdNpcObservationIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function density_givenAdmin_returnsAggregatesForKnownTuples(): void
    {
        // Arrange
        $this->actingAsAdmin();

        // A spell without any pre-existing property so the seeded observation migration can't have touched it
        $spell = $this->findSpellWithoutProperties();
        $this->createSpellObservation($spell->id, SpellProperty::Aura, Carbon::today());
        $this->createSpellObservation($spell->id, SpellProperty::Aura, Carbon::yesterday());

        $npc            = $this->findNpcWithoutCharacteristics();
        $characteristic = Characteristic::query()->firstOrFail();
        $this->createNpcObservation($npc->id, $characteristic->id, Carbon::today());

        // Act — force the detailed tuple list so the assertions below don't depend on the default cap
        $response = $this->getJson(route('api.v1.combatlog.observations.density', ['detailed' => 1]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'spell_property_observations'     => ['row_count', 'observed_on' => ['min', 'max', 'count'], 'histogram', 'tuples', 'truncated'],
                'npc_characteristic_observations' => ['row_count', 'observed_on' => ['min', 'max', 'count'], 'histogram', 'tuples', 'truncated'],
            ],
        ]);

        /** @var array<int, array<string, mixed>> $spellTuples */
        $spellTuples = $response->json('data.spell_property_observations.tuples');
        $spellTuple  = collect($spellTuples)->first(fn(array $t) => $t['spell_id'] === $spell->id && $t['property'] === SpellProperty::Aura->value);
        $this->assertNotNull($spellTuple, 'Expected the seeded spell tuple to be present');
        $this->assertGreaterThanOrEqual(2, $spellTuple['days_observed']);

        /** @var array<int, array<string, mixed>> $npcTuples */
        $npcTuples = $response->json('data.npc_characteristic_observations.tuples');
        $npcTuple  = collect($npcTuples)->first(fn(array $t) => $t['npc_id'] === $npc->id && $t['characteristic_id'] === $characteristic->id);
        $this->assertNotNull($npcTuple, 'Expected the seeded NPC tuple to be present');
        $this->assertGreaterThanOrEqual(1, $npcTuple['days_observed']);

        $this->assertGreaterThanOrEqual(1, $response->json('data.spell_property_observations.row_count'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.npc_characteristic_observations.row_count'));
    }

    #[Test]
    public function density_givenEmptyTables_returnsSaneEmptyShape(): void
    {
        // Arrange
        $this->actingAsAdmin();

        // Both observation tables carry seed data from the environment's mapping data, so temporarily wipe them
        // inside an uncommitted transaction on the combatlog connection - never actually persisted, see the same
        // technique in DetectStaleCombatLogDataCommandTest.
        DB::connection('combatlog')->beginTransaction();

        try {
            CombatLogSpellPropertyObservation::query()->toBase()->delete();
            CombatLogNpcCharacteristicObservation::query()->toBase()->delete();

            // Act
            $response = $this->getJson(route('api.v1.combatlog.observations.density'));

            // Assert
            $response->assertOk();
            $this->assertSame(0, $response->json('data.spell_property_observations.row_count'));
            $this->assertSame(0, $response->json('data.npc_characteristic_observations.row_count'));
            $this->assertNull($response->json('data.spell_property_observations.observed_on.min'));
            $this->assertNull($response->json('data.spell_property_observations.observed_on.max'));
            $this->assertSame(0, $response->json('data.spell_property_observations.observed_on.count'));
            $this->assertSame([], $response->json('data.spell_property_observations.tuples'));
            $this->assertFalse($response->json('data.spell_property_observations.truncated'));
        } finally {
            DB::connection('combatlog')->rollBack();
        }
    }

    #[Test]
    public function density_givenAnonymous_returnsForbidden(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.combatlog.observations.density'));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
    }

    #[Test]
    public function density_givenAuthenticatedNonAdmin_returnsForbidden(): void
    {
        // Arrange
        /** @var User $nonAdmin */
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->getJson(route('api.v1.combatlog.observations.density'));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function density_givenAiAgent_returnsOk(): void
    {
        // Arrange
        /** @var User $aiAgent */
        $aiAgent = User::factory()->create();

        try {
            $aiAgent->addRole(Role::ROLE_AI_AGENT);
            $this->actingAs($aiAgent);

            // Act
            $response = $this->getJson(route('api.v1.combatlog.observations.density'));

            // Assert
            $response->assertOk();
        } finally {
            $aiAgent->delete();
        }
    }

    #[Test]
    public function spellHistory_givenAdmin_returnsPropertyDates(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $spell = $this->findSpellWithoutProperties();
        $this->createSpellObservation($spell->id, SpellProperty::Aura, Carbon::today());
        $this->createSpellObservation($spell->id, SpellProperty::Aura, Carbon::yesterday());
        $this->createSpellObservation($spell->id, SpellProperty::MissDodge, Carbon::today());

        // Act
        $response = $this->getJson(route('api.v1.combatlog.observations.spells.show', ['spell' => $spell->id]));

        // Assert
        $response->assertOk();
        $this->assertSame($spell->id, $response->json('data.spell_id'));
        $this->assertEqualsCanonicalizing(
            [Carbon::today()->toDateString(), Carbon::yesterday()->toDateString()],
            $response->json('data.properties.aura'),
        );
        $this->assertSame([Carbon::today()->toDateString()], $response->json('data.properties.miss_dodge'));
    }

    #[Test]
    public function spellHistory_givenSpellWithoutObservations_returnsEmptyShape(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $spell = $this->findSpellWithoutProperties();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.observations.spells.show', ['spell' => $spell->id]));

        // Assert
        $response->assertOk();
        $this->assertSame($spell->id, $response->json('data.spell_id'));
        $this->assertSame([], $response->json('data.properties'));
    }

    #[Test]
    public function spellHistory_givenUnknownSpell_returnsNotFound(): void
    {
        // Arrange
        $this->actingAsAdmin();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.observations.spells.show', ['spell' => PHP_INT_MAX]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function npcHistory_givenAdmin_returnsCharacteristicDates(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $npc            = $this->findNpcWithoutCharacteristics();
        $characteristic = Characteristic::query()->firstOrFail();
        $this->createNpcObservation($npc->id, $characteristic->id, Carbon::today());
        $this->createNpcObservation($npc->id, $characteristic->id, Carbon::yesterday());

        // Act
        $response = $this->getJson(route('api.v1.combatlog.observations.npcs.show', ['npc' => $npc->id]));

        // Assert
        $response->assertOk();
        $this->assertSame($npc->id, $response->json('data.npc_id'));
        $characteristicData = $response->json(sprintf('data.characteristics.%d', $characteristic->id));
        $this->assertNotNull($characteristicData);
        $this->assertSame($characteristic->key, $characteristicData['key']);
        $this->assertEqualsCanonicalizing(
            [Carbon::today()->toDateString(), Carbon::yesterday()->toDateString()],
            $characteristicData['observed_on'],
        );
    }

    #[Test]
    public function npcHistory_givenNpcWithoutObservations_returnsEmptyShape(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $npc = $this->findNpcWithoutCharacteristics();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.observations.npcs.show', ['npc' => $npc->id]));

        // Assert
        $response->assertOk();
        $this->assertSame($npc->id, $response->json('data.npc_id'));
        $this->assertSame([], $response->json('data.characteristics'));
    }

    #[Test]
    public function npcHistory_givenUnknownNpc_returnsNotFound(): void
    {
        // Arrange
        $this->actingAsAdmin();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.observations.npcs.show', ['npc' => PHP_INT_MAX]));

        // Assert
        $response->assertNotFound();
    }

    private function actingAsAdmin(): void
    {
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must have the admin role for this test (seed the database).');
        $this->actingAs($admin);
    }

    /**
     * Only `aura` and `miss_types_mask` are checked - `debuff` is seeded true on effectively every spell in this
     * environment, so a test property that isn't `debuff` is used wherever collision with the seeded observation
     * migration (`2026_05_22_000006_seed_combat_log_observations`, dated "today") matters.
     */
    private function findSpellWithoutProperties(): Spell
    {
        /** @var Spell $spell */
        $spell = Spell::query()
            ->where('aura', false)
            ->where('miss_types_mask', 0)
            ->firstOrFail();

        return $spell;
    }

    private function findNpcWithoutCharacteristics(): Npc
    {
        /** @var Npc $npc */
        $npc = Npc::query()
            ->whereDoesntHave('characteristics')
            ->firstOrFail();

        return $npc;
    }

    private function createSpellObservation(int $spellId, SpellProperty $property, Carbon $observedOn): void
    {
        $observation = CombatLogSpellPropertyObservation::factory()->create([
            'spell_id'    => $spellId,
            'property'    => $property->value,
            'observed_on' => $observedOn,
        ]);

        $this->createdSpellObservationIds[] = $observation->id;
    }

    private function createNpcObservation(int $npcId, int $characteristicId, Carbon $observedOn): void
    {
        $observation = CombatLogNpcCharacteristicObservation::factory()->create([
            'npc_id'            => $npcId,
            'characteristic_id' => $characteristicId,
            'observed_on'       => $observedOn,
        ]);

        $this->createdNpcObservationIds[] = $observation->id;
    }
}
