<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\CharacterRace;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsCombatLogCriteriaControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function criteria_givenAuthenticatedAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.combatlog.criteria.view'));

        // Assert
        $response->assertOk();
    }

    /**
     * The page is generic over CombatLogParsingCriterion::VALID_CRITERIA, so a newly added criterion
     * model has to render with a name rather than fall back to the "#<id>" placeholder (#4357).
     */
    #[Test]
    public function criteria_givenARaceCriterion_rendersItUnderItsOwnHeadingWithTheRaceName(): void
    {
        // Arrange
        $nightElf  = CharacterRace::query()->where('key', CharacterRace::CHARACTER_RACE_NIGHT_ELF)->firstOrFail();
        $criterion = CombatLogParsingCriterion::factory()->forRace($nightElf->id)->forBand(90, 94)->create();

        try {
            // Act
            $response = $this->get(route('admin.tools.combatlog.criteria.view'));

            // Assert
            $response->assertOk();
            $content = $response->getContent();

            $this->assertStringContainsString('id="criteria-band-' . $criterion->combat_log_version . '-characterrace-90"', $content);
            $this->assertStringContainsString($nightElf->getName(), $content);
            $this->assertStringNotContainsString(sprintf('#%d', $nightElf->id), $content);
        } finally {
            $criterion->delete();
        }
    }

    /**
     * Every criterion is repeated once per band, so without collapsing the page runs to thousands
     * of pixels. The header must carry enough to judge a band without expanding it.
     */
    #[Test]
    public function criteria_givenBandsWithAndWithoutEveryCriterionFulfilled_rendersCollapsedAccordionPerBand(): void
    {
        // Arrange
        $fulfilled   = CombatLogParsingCriterion::factory()->forDungeon(999901)->forBand(90, 94)->atThreshold()->create();
        $unfulfilled = CombatLogParsingCriterion::factory()->forDungeon(999902)->forBand(95, 99)->withCount(1)->create();

        try {
            // Act
            $response = $this->get(route('admin.tools.combatlog.criteria.view'));

            // Assert
            $response->assertOk();
            $content = $response->getContent();

            // Both bands are their own accordion item, and both start collapsed
            $this->assertStringContainsString('id="criteria-band-' . $fulfilled->combat_log_version . '-dungeon-90"', $content);
            $this->assertStringContainsString('id="criteria-band-' . $unfulfilled->combat_log_version . '-dungeon-95"', $content);
            $this->assertSame(
                substr_count($content, 'class="accordion-item"'),
                substr_count($content, 'accordion-button collapsed'),
            );
            $this->assertStringNotContainsString('accordion-collapse collapse show', $content);

            // The at-a-glance status: green when the whole band is fulfilled, orange when it is not
            $this->assertStringContainsString('bg-success-subtle', $content);
            $this->assertStringContainsString('bg-warning-subtle', $content);
        } finally {
            $fulfilled->delete();
            $unfulfilled->delete();
        }
    }

    /**
     * A collapsed Bootstrap panel is hidden, not removed - which is the only reason the thresholds
     * of every band still submit. Rendering panel contents lazily would silently drop them.
     */
    #[Test]
    public function updatethresholds_givenACriterionInACollapsedBand_savesTheNewThreshold(): void
    {
        // Arrange
        $criterion = CombatLogParsingCriterion::factory()->forDungeon(999903)->forBand(12, 16)->create();

        try {
            // Act
            $response = $this->post(route('admin.tools.combatlog.criteria.thresholds'), [
                'thresholds' => [$criterion->id => 42],
            ]);

            // Assert
            $response->assertRedirect(route('admin.tools.combatlog.criteria.view'));
            $this->assertSame(42, $criterion->fresh()->threshold);
        } finally {
            $criterion->delete();
        }
    }

    #[Test]
    public function criteriareset_givenExistingCountsForToday_resetsCountsToZero(): void
    {
        // Arrange
        $criterion = CombatLogParsingCriterion::factory()->forDungeon(999901)->withCount(75)->create();

        try {
            // Act
            $response = $this->post(route('admin.tools.combatlog.criteria.reset'));

            // Assert
            $response->assertRedirect(route('admin.tools.combatlog.criteria.view'));
            $this->assertEquals(0, $criterion->fresh()->count);
        } finally {
            $criterion->delete();
        }
    }
}
