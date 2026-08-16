<?php

namespace Tests\Feature\App\Repository;

use App\Models\Characteristic;
use App\Models\Spell\Spell;
use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use App\Repositories\Swoole\SpellRepositorySwoole;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellRepositorySwoole')]
final class SpellRepositorySwooleTest extends PublicTestCase
{
    private const int SPELL_ID = 999701;

    private SpellRepositorySwoole $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new SpellRepositorySwoole();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            Spell::where('id', self::SPELL_ID)->delete();
            Carbon::setTestNow();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTestSpell(array $overrides = []): Spell
    {
        return Spell::create(array_merge([
            'id'              => self::SPELL_ID,
            'game_version_id' => 1,
            'category'        => null,
            'cooldown_group'  => null,
            'dispel_type'     => '',
            'mechanic'        => '',
            'icon_name'       => '',
            'name'            => 'TestSpell',
            'schools_mask'    => 1,
            'miss_types_mask' => 0,
            'aura'            => false,
            'debuff'          => false,
            'cast_time'       => 0,
            'duration'        => 0,
            'selectable'      => false,
            'hidden_on_map'   => false,
            'fetched_data_at' => Carbon::now(),
        ], $overrides));
    }

    #[Test]
    public function container_givenTwoResolutions_returnsTheSameProcessPersistentInstance(): void
    {
        // Arrange / Act - the whole optimization rests on OctaneServiceProvider's app()->instance() binding
        // surviving provider registration; a later bind() would silently revert to a repository per job
        $first  = $this->app->make(SpellRepositorySwooleInterface::class);
        $second = $this->app->make(SpellRepositorySwooleInterface::class);

        // Assert - identity, not equality: one shared repository per process
        $this->assertSame($first, $second);
        $this->assertInstanceOf(SpellRepositorySwoole::class, $first);
    }

    #[Test]
    public function getAllKeyedWithSpellDungeons_givenThisProcessesOwnSpellCreation_returnsSameCatalogWithoutRebuild(): void
    {
        // Arrange - build the catalog, then mirror what SpellCreationCollector does on a spell create: insert
        // the row AND put() the model into the shared catalog
        $catalog = $this->repository->getAllKeyedWithSpellDungeons();
        $spell   = $this->createTestSpell();
        $spell->setRelation('spellDungeons', collect());
        $catalog->put($spell->id, $spell);

        // Act
        $secondCatalog = $this->repository->getAllKeyedWithSpellDungeons();

        // Assert - the database matches the live catalog, so a self-write must not force a full rebuild
        $this->assertSame($catalog, $secondCatalog);
    }

    #[Test]
    public function getAllKeyedWithSpellDungeons_givenNoTableChanges_returnsMemoizedCatalog(): void
    {
        // Arrange
        $firstCatalog = $this->repository->getAllKeyedWithSpellDungeons();

        // Act
        $secondCatalog = $this->repository->getAllKeyedWithSpellDungeons();

        // Assert - the exact same collection instance, not a rebuilt copy
        $this->assertSame($firstCatalog, $secondCatalog);
        $this->assertTrue($firstCatalog->first()->relationLoaded('spellDungeons'));
    }

    #[Test]
    public function getAllKeyedWithSpellDungeons_givenASpellInsertedElsewhere_returnsRebuiltCatalog(): void
    {
        // Arrange - build the catalog, then insert a spell behind its back like another worker would
        $staleCatalog = $this->repository->getAllKeyedWithSpellDungeons();
        $this->createTestSpell();

        // Act
        $freshCatalog = $this->repository->getAllKeyedWithSpellDungeons();

        // Assert - the row count stamp detected the insert and rebuilt
        $this->assertNotSame($staleCatalog, $freshCatalog);
        $this->assertTrue($freshCatalog->has(self::SPELL_ID));
        $this->assertFalse($staleCatalog->has(self::SPELL_ID));
    }

    #[Test]
    public function getAllKeyedWithSpellDungeons_givenAnExpiredTtl_returnsRebuiltCatalog(): void
    {
        // Arrange - no inserts at all, only time passing beyond the TTL
        $staleCatalog = $this->repository->getAllKeyedWithSpellDungeons();
        Carbon::setTestNow(Carbon::now()->addSeconds(301));

        // Act
        $freshCatalog = $this->repository->getAllKeyedWithSpellDungeons();

        // Assert - in-place updates leave the stamps untouched, so the TTL is what picks them up
        $this->assertNotSame($staleCatalog, $freshCatalog);
    }

    #[Test]
    public function getAllWithCharacteristic_givenASpellInsertedElsewhere_returnsRebuiltSubset(): void
    {
        // Arrange - both memos share the freshness stamps, so a spell insert refreshes the subset too
        $staleSubset = $this->repository->getAllWithCharacteristic();
        $this->createTestSpell(['characteristic_id' => Characteristic::ALL[Characteristic::CHARACTERISTIC_POLYMORPH]]);

        // Act
        $freshSubset = $this->repository->getAllWithCharacteristic();

        // Assert
        $this->assertNotSame($staleSubset, $freshSubset);
        $this->assertTrue($freshSubset->has(self::SPELL_ID));
    }
}
