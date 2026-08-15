<?php

namespace Tests\Feature\Mapping;

use App\Models\Spell\Spell;
use App\SeederHelpers\RelationImport\Mapping\SpellRelationMapping;
use App\Service\Mapping\MappingExportServiceInterface;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Mapping')]
class MappingExportServiceTest extends TestCase
{
    /**
     * Spell has computed accessors in $appends (icon_url, wowhead_url, wowhead_tooltip_data) that are not
     * columns. They must never reach spells.json - the seeder cannot load them back, and every one of them
     * that leaks rewrites all ~8000 spell entries on the next `mapping:save`.
     */
    #[Test]
    public function serializeSpells_givenSeededSpells_returnsNoAppendedAccessors(): void
    {
        // Arrange
        /** @var MappingExportServiceInterface $mappingExportService */
        $mappingExportService = app(MappingExportServiceInterface::class);
        $appends              = new Spell()->getAppends();

        Assert::assertNotEmpty($appends, 'Spell has no appended accessors - this test no longer guards anything');

        // Act
        $serializedSpells = $mappingExportService->serializeSpells();
        $leaked           = $this->findLeakedProperties($serializedSpells, $appends);

        // Assert
        Assert::assertEmpty($leaked, sprintf('Appended accessors leaked into exported spells: %s', $this->describeLeaks($leaked)));
    }

    /**
     * Combat-log-derived behavior is re-applied per environment from the combatlog pipeline and must not
     * round-trip through the git seeders.
     */
    #[Test]
    public function serializeSpells_givenSeededSpells_returnsNoCombatLogDerivedProperties(): void
    {
        // Arrange
        /** @var MappingExportServiceInterface $mappingExportService */
        $mappingExportService = app(MappingExportServiceInterface::class);
        $combatLogDerived     = ['aura', 'debuff', 'miss_types_mask', 'counters_mask', 'bypasses_immunities_mask'];

        // Act
        $serializedSpells = $mappingExportService->serializeSpells();
        $leaked           = $this->findLeakedProperties($serializedSpells, $combatLogDerived);

        // Assert
        Assert::assertEmpty($leaked, sprintf('Combat-log-derived properties leaked into exported spells: %s', $this->describeLeaks($leaked)));
    }

    /**
     * The other half of the contract the test above guards. A column kept out of spells.json has no value
     * to load back, so unless SpellRelationMapping preserves it, DungeonDataSeeder's temp-table swap
     * replaces it with the column default - and every deploy runs `db:seed`. That is how `counters_mask`
     * and `bypasses_immunities_mask` were wiped on every staging deploy for ten days (#4033): they were
     * added to the export's hidden list and missed from the preserved list.
     *
     * Asserted against the live schema rather than a hand-written list, so a sixth combat-log-derived
     * column fails here the moment it is added rather than on the next deploy.
     */
    #[Test]
    public function getPreservedColumns_givenColumnsMissingFromTheExport_preservesEveryOneOfThem(): void
    {
        // Arrange
        /** @var MappingExportServiceInterface $mappingExportService */
        $mappingExportService = app(MappingExportServiceInterface::class);
        $columns              = Schema::getColumnListing(new Spell()->getTable());

        Assert::assertNotEmpty($columns, 'The spells table reports no columns - this test no longer guards anything');

        // Act
        $serializedSpells = $mappingExportService->serializeSpells();

        Assert::assertNotEmpty($serializedSpells, 'No spells were exported - this test no longer guards anything');

        $exported  = array_keys($serializedSpells[array_key_first($serializedSpells)]);
        $omitted   = array_values(array_diff($columns, $exported));
        $preserved = new SpellRelationMapping()->getPreservedColumns();

        // Assert
        Assert::assertEmpty(
            array_diff($omitted, $preserved),
            sprintf(
                'Columns are omitted from spells.json but not preserved across a re-seed, so `db:seed` will reset them to their default: %s',
                implode(', ', array_diff($omitted, $preserved)),
            ),
        );
    }

    /**
     * A preserved column that does not exist fails the seeder's `UPDATE ... SET t.<col> = orig.<col>`
     * with a SQL error mid-seed, which is a deploy outage rather than a silent data loss - but it is
     * just as avoidable, and a typo is the likely cause.
     */
    #[Test]
    public function getPreservedColumns_givenTheSpellsSchema_namesOnlyRealColumns(): void
    {
        // Arrange
        $columns = Schema::getColumnListing(new Spell()->getTable());

        // Act
        $preserved = new SpellRelationMapping()->getPreservedColumns();

        // Assert
        Assert::assertEmpty(
            array_diff($preserved, $columns),
            sprintf('Preserved columns do not exist on the spells table: %s', implode(', ', array_diff($preserved, $columns))),
        );
    }

    /**
     * Every spell carries the same shape, so the first handful of offenders identifies the problem; the
     * total is reported so a truncated list is never mistaken for the full extent of the leak.
     *
     * @param array<int, string> $leaked
     */
    private function describeLeaks(array $leaked): string
    {
        return sprintf('%d occurrence(s), first: %s', count($leaked), implode(', ', array_slice($leaked, 0, 10)));
    }

    /**
     * @param  array<int, array<string, mixed>> $serializedSpells
     * @param  array<int, string>               $forbiddenProperties
     * @return array<int, string>               `<property> (spell <id>)` for each forbidden property found
     */
    private function findLeakedProperties(array $serializedSpells, array $forbiddenProperties): array
    {
        Assert::assertNotEmpty($serializedSpells, 'No spells were exported - the test database is not seeded');

        $leaked = [];
        foreach ($serializedSpells as $serializedSpell) {
            foreach ($forbiddenProperties as $forbiddenProperty) {
                if (array_key_exists($forbiddenProperty, $serializedSpell)) {
                    $leaked[] = sprintf('%s (spell %d)', $forbiddenProperty, $serializedSpell['id']);
                }
            }
        }

        return $leaked;
    }
}
