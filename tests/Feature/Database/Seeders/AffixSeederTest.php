<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupCoupling;
use App\Models\Season;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the invariants of the affix data that AffixSeeder imports from database/seeders/seasondata/.
 *
 * That data is now authored in the admin panel and exported with `php artisan mapping:save`, which makes
 * these invariants easier to break than when the seeder held PHP literals: the admin panel can create an
 * Affix row, but nothing updates the Affix::ALL / Affix::AFFIX_* constants that the rest of the
 * application resolves affixes by.
 */
#[Group('AffixSeeder')]
final class AffixSeederTest extends PublicTestCase
{
    /**
     * Legion Timewalking declares a 12 week rotation but only ever had 2 affix groups seeded. The
     * runtime tolerates it (SeasonAffixGroupService guards on $season->affixGroups->count()), and the
     * season starts on the 2050 sentinel date so it is never actually rendered. Asserted explicitly so
     * the known mismatch cannot silently widen, and so no-one "fixes" it by editing user-visible data.
     */
    private const int LEGION_TIMEWALKING_SEEDED_AFFIX_GROUP_COUNT = 2;

    #[Test]
    public function run_givenSeededDatabase_mirrorsTheAffixAllConstant(): void
    {
        // Arrange
        $expected = Affix::ALL;

        // Act
        $actual = Affix::query()
            ->orderBy('id')
            ->pluck('id', 'key')
            ->all();

        // Assert - both directions, so neither a new seeded affix nor a stale constant goes unnoticed.
        $this->assertSame(
            $expected,
            $actual,
            'The seeded affixes no longer match Affix::ALL. Adding an affix requires adding its AFFIX_* constant and ALL entry too.',
        );
    }

    #[Test]
    public function run_givenSeededDatabase_couplesOnlyExistingAffixes(): void
    {
        // Arrange
        $affixIds = Affix::query()->pluck('id')->all();

        // Act
        $orphanedAffixIds = AffixGroupCoupling::query()
            ->whereNotIn('affix_id', $affixIds)
            ->pluck('affix_id')
            ->unique()
            ->all();

        // Assert
        $this->assertSame([], $orphanedAffixIds, 'Affix group couplings reference affixes that do not exist.');
    }

    #[Test]
    public function run_givenSeededDatabase_ordersAffixesWithinAGroupByCouplingInsertionOrder(): void
    {
        // Arrange - affix_groups.id 1 is BFA S1's first week, which is fixed, long-past history and will
        // never be re-authored. Its rotation order is documented in affix_groups.json and never changes.
        $affixGroup = AffixGroup::query()->findOrFail(1);

        // Act - AffixGroupBase::affixes() orders by affix_group_couplings.id, which mirrors the coupling
        // array's position in affix_groups.json (see saveAffixGroups()'s and seedAffixGroups()'s PHPDoc
        // for why that array order is the contract, not just its set of members).
        $actual = $affixGroup->affixes->pluck('key')->all();

        // Assert - expected order taken from affix_groups.json's id:1 entry, resolving each coupling's
        // affix_id against affixes.json's own `id` column (not its `affix_id` column - the coupling's
        // affix_id is a foreign key to affixes.id, confusingly named the same as the business affix_id).
        $this->assertSame(
            ['Fortified', 'Sanguine', 'Necrotic', 'Infested'],
            $actual,
            'Affix group 1 no longer resolves its affixes in the expected rotation order.',
        );
    }

    #[Test]
    public function run_givenSeededDatabase_givesEveryCouplingAPositiveKeyLevel(): void
    {
        // Arrange & Act - a null or zero key level used to be possible when the key levels were a
        // separate per-season list indexed positionally against the affix list.
        $invalidCouplingCount = AffixGroupCoupling::query()
            ->whereNull('key_level')
            ->orWhere('key_level', '<=', 0)
            ->count();

        // Assert
        $this->assertSame(0, $invalidCouplingCount, 'Every affix group coupling must have a positive key level.');
    }

    #[Test]
    public function run_givenSeededDatabase_producesContiguousAffixGroupIdsInSeasonOrder(): void
    {
        // Arrange - affix_groups.id is referenced by dungeon_route_affix_groups (no foreign key) and is
        // used arithmetically by App\Logic\MDT\Conversion, so a gap or a reorder corrupts saved routes.
        $affixGroups = AffixGroup::query()
            ->orderBy('id')
            ->get(['id', 'season_id']);

        // Act
        $expectedId       = 1;
        $previousSeasonId = 0;

        // Assert
        foreach ($affixGroups as $affixGroup) {
            $this->assertSame($expectedId, $affixGroup->id, 'Affix group ids must be contiguous starting at 1.');
            $this->assertGreaterThanOrEqual(
                $previousSeasonId,
                $affixGroup->season_id,
                sprintf('Affix group %d breaks the ascending season order.', $affixGroup->id),
            );

            $expectedId++;
            $previousSeasonId = $affixGroup->season_id;
        }

        $this->assertGreaterThan(0, $affixGroups->count());
    }

    #[Test]
    public function run_givenSeededDatabase_matchesTheAffixGroupCountOfEverySeason(): void
    {
        // Arrange
        $seededCountsBySeasonId = AffixGroup::query()
            ->selectRaw('season_id, COUNT(*) as affix_group_count')
            ->groupBy('season_id')
            ->pluck('affix_group_count', 'season_id')
            ->all();

        // Act & Assert
        foreach (Season::all() as $season) {
            $seededCount = $seededCountsBySeasonId[$season->id] ?? 0;

            $expectedCount = $season->id === Season::SEASON_LEGION_TW_S1
                ? self::LEGION_TIMEWALKING_SEEDED_AFFIX_GROUP_COUNT
                : $season->affix_group_count;

            $this->assertSame(
                $expectedCount,
                $seededCount,
                sprintf(
                    'Season %d seeds %d affix groups but declares affix_group_count %d - the affixes overview will show the wrong week.',
                    $season->id,
                    $seededCount,
                    $season->affix_group_count,
                ),
            );
        }
    }
}
