<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * getDominantAffix() used to decide whether to apply the index-1 "both Fortified and Tyrannical"
 * disambiguation by checking $this->season_id (the route's own season) against a hardcoded list of
 * season ids - a list that had to be updated every time a new "both" style season shipped, silently
 * missed Midnight Season 1, and was keyed off the wrong thing anyway (the route's season, not the
 * attached affix group's own composition). It now detects "both" directly from the affix group's own
 * data, so these tests deliberately set season_id to a season NOT in the old hardcoded list (or, for
 * the "both" case, IN it) to prove detection no longer depends on that list at all.
 */
#[Group('DungeonRoute')]
final class DungeonRouteGetDominantAffixTest extends PublicTestCase
{
    #[Test]
    public function getDominantAffix_givenAffixGroupWithBothFortifiedAndTyrannical_usesSecondIndexedAffix(): void
    {
        // Arrange - Midnight Season 1's real seeded affix groups carry both Fortified and Tyrannical in
        // the same group; this is the exact season the old hardcoded list forgot to include. Pick a group
        // with Tyrannical (not Fortified) at index 1, and deliberately set the route's own season_id to a
        // season the old list never listed (Dragonflight S4): the old code's `else` branch would have
        // checked hasAffix(FORTIFIED) first and wrongly returned FORTIFIED - only detecting "both" from the
        // affix group's own data (regardless of the route's season) gets this right.
        /** @var ?AffixGroup $affixGroup */
        $affixGroup = AffixGroup::where('season_id', Season::SEASON_MIDNIGHT_S1)
            ->get()
            ->first(static fn(AffixGroup $affixGroup) => $affixGroup->hasAffix(Affix::AFFIX_FORTIFIED)
                && $affixGroup->hasAffix(Affix::AFFIX_TYRANNICAL)
                && $affixGroup->affixes->get(1)?->key === Affix::AFFIX_TYRANNICAL);

        $this->assertNotNull($affixGroup, 'Expected a Midnight Season 1 affix group with Tyrannical at index 1');

        $route = DungeonRoute::factory()->create(['season_id' => Season::SEASON_DF_S4]);

        try {
            $route->affixes()->attach($affixGroup->id);

            // Act
            $result = $route->fresh()->getDominantAffix();

            // Assert
            $this->assertSame(Affix::AFFIX_TYRANNICAL, $result);
        } finally {
            $route->delete();
        }
    }

    #[Test]
    public function getDominantAffix_givenAffixGroupWithOnlyTyrannical_returnsTyrannical(): void
    {
        // Arrange - Dragonflight Season 4's real seeded affix groups only ever carry one of the two.
        // Deliberately set the route's own season_id to a season that WAS in the old hardcoded "both"
        // list (TWW Season 1): the old code's `if` branch would have looked at index 1 of this
        // single-affix group (which is never Fortified or Tyrannical) and wrongly found neither,
        // returning null - only detecting "only one present" from the affix group's own data gets this
        // right regardless of the route's season.
        /** @var ?AffixGroup $affixGroup */
        $affixGroup = AffixGroup::where('season_id', Season::SEASON_DF_S4)
            ->get()
            ->first(static fn(AffixGroup $affixGroup) => $affixGroup->hasAffix(Affix::AFFIX_TYRANNICAL)
                && !$affixGroup->hasAffix(Affix::AFFIX_FORTIFIED));

        $this->assertNotNull($affixGroup, 'Expected a Dragonflight Season 4 affix group with only Tyrannical');

        $route = DungeonRoute::factory()->create(['season_id' => Season::SEASON_TWW_S1]);

        try {
            $route->affixes()->attach($affixGroup->id);

            // Act
            $result = $route->fresh()->getDominantAffix();

            // Assert
            $this->assertSame(Affix::AFFIX_TYRANNICAL, $result);
        } finally {
            $route->delete();
        }
    }
}
