<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\AdminDungeonMappingVersionsComposer;
use App\Http\View\Composers\AdminMessageBannerComposer;
use App\Http\View\Composers\AdminNpcHealthEditComposer;
use App\Http\View\Composers\AdminSpellEditComposer;
use App\Http\View\Composers\AffixesComposer;
use App\Http\View\Composers\AppLayoutComposer;
use App\Http\View\Composers\ChangelogFlagComposer;
use App\Http\View\Composers\CompositionComposer;
use App\Http\View\Composers\CreateRouteFormComposer;
use App\Http\View\Composers\DiscoverAffixGroupComposer;
use App\Http\View\Composers\DiscoverSearchComposer;
use App\Http\View\Composers\DungeonDifficultySelectComposer;
use App\Http\View\Composers\DungeonGridDiscoverComposer;
use App\Http\View\Composers\DungeonGridTabsComposer;
use App\Http\View\Composers\DungeonSelectComposer;
use App\Http\View\Composers\EmbedComposer;
use App\Http\View\Composers\GameVersionsNavComposer;
use App\Http\View\Composers\GlobalComposer;
use App\Http\View\Composers\HeaderComposer;
use App\Http\View\Composers\HeatmapSearchComposer;
use App\Http\View\Composers\HomeComposer;
use App\Http\View\Composers\MapComposer;
use App\Http\View\Composers\MappingVersionComposer;
use App\Http\View\Composers\OAuthRegisterFormComposer;
use App\Http\View\Composers\ProfileEditComposer;
use App\Http\View\Composers\ProfileNewRouteStyleComposer;
use App\Http\View\Composers\PullsComposer;
use App\Http\View\Composers\PullsWorkbenchComposer;
use App\Http\View\Composers\ReleaseComposer;
use App\Http\View\Composers\RollbarComposer;
use App\Http\View\Composers\RouteAttributesComposer;
use App\Http\View\Composers\RouteCoverageAffixGroupComposer;
use App\Http\View\Composers\RoutePublishComposer;
use App\Http\View\Composers\RouteTierComposer;
use App\Http\View\Composers\SimulateComposer;
use App\Http\View\Composers\SimulateOptionsComposer;
use App\Http\View\Composers\TeamSelectComposer;
use App\Models\AffixGroup\AffixGroup;
use App\Models\GameVersion\GameVersion;
use Illuminate\Contracts\View\View as ViewContract;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Verifies each composer puts the keys its view needs onto the view (not just that the page returns 200).
 * Composers are invoked directly on an unrendered view so a mis-keyed with() surfaces here.
 */
#[Group('ViewComposers')]
final class ViewComposerTest extends PublicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsGuest();
    }

    #[Test]
    public function globalComposer_givenView_setsRequestScopedKeys(): void
    {
        $this->assertComposerSetsKeys(GlobalComposer::class, 'home', [
            'isMobile', 'isLocal', 'isMapping', 'isProduction', 'viewName',
            'theme', 'isUserAdmin', 'adFree', 'userOrDefaultRegion', 'currentUserGameVersion', 'numUserReports',
        ]);
    }

    #[Test]
    public function homeComposer_givenView_setsHomeKeys(): void
    {
        $this->assertComposerSetsKeys(HomeComposer::class, 'home', [
            'userCount', 'demoRoutes', 'demoRouteDungeons', 'demoRouteMapping', 'currentSeason', 'defaultGameVersion',
        ]);
    }

    #[Test]
    public function appLayoutComposer_givenView_setsLayoutKeys(): void
    {
        $this->assertComposerSetsKeys(AppLayoutComposer::class, 'layouts.app', [
            'version', 'revision', 'nameAndVersion', 'latestRelease', 'latestReleaseSpotlight', 'messageBanner', 'readOnlyEnabled',
        ]);
    }

    #[Test]
    public function mapComposer_givenView_setsBaseUrlKeys(): void
    {
        $this->assertComposerSetsKeys(MapComposer::class, 'common.maps.map', [
            'assetsBaseUrl', 'tilesBaseUrl',
        ]);
    }

    #[Test]
    public function changelogFlagComposer_givenView_setsHasNewChangelog(): void
    {
        $this->assertComposerSetsKeys(ChangelogFlagComposer::class, 'common.layout.footer', [
            'hasNewChangelog',
        ]);
    }

    #[Test]
    public function gameVersionsNavComposer_givenView_setsAllGameVersions(): void
    {
        $this->assertComposerSetsKeys(GameVersionsNavComposer::class, 'common.layout.nav.gameversions', [
            'allGameVersions',
        ]);
    }

    #[Test]
    public function headerComposer_givenView_setsHeaderKeys(): void
    {
        $this->assertComposerSetsKeys(HeaderComposer::class, 'common.layout.header', [
            'activeExpansions', 'currentSeason', 'nextSeason', 'allGameVersions', 'gameVersionDungeons',
        ]);
    }

    #[Test]
    public function embedComposer_givenView_setsSpecializations(): void
    {
        $this->assertComposerSetsKeys(EmbedComposer::class, 'misc.embedexplore', [
            'characterClassSpecializations',
        ]);
    }

    #[Test]
    public function discoverSearchComposer_givenView_setsSearchKeys(): void
    {
        $this->assertComposerSetsKeys(DiscoverSearchComposer::class, 'dungeonroute.discover.search', [
            'currentExpansion', 'allAffixGroupsByActiveExpansion', 'featuredAffixesByActiveExpansion', 'currentSeason', 'nextSeason',
        ]);
    }

    #[Test]
    public function dungeonDifficultySelectComposer_givenView_setsSpeedrunDungeons(): void
    {
        $this->assertComposerSetsKeys(DungeonDifficultySelectComposer::class, 'common.dungeonroute.create.dungeondifficultyselect', [
            'allSpeedrunDungeons',
        ]);
    }

    #[Test]
    public function oAuthRegisterFormComposer_givenView_setsAllRegions(): void
    {
        $this->assertComposerSetsKeys(OAuthRegisterFormComposer::class, 'common.forms.oauth', [
            'allRegions',
        ]);
    }

    #[Test]
    public function createRouteFormComposer_givenView_setsRouteKeys(): void
    {
        $this->assertComposerSetsKeys(CreateRouteFormComposer::class, 'common.forms.createroute', [
            'routeKeyLevelFrom', 'routeKeyLevelTo', 'currentSeason', 'nextSeason',
        ]);
    }

    #[Test]
    public function releaseComposer_givenView_setsCategories(): void
    {
        $this->assertComposerSetsKeys(ReleaseComposer::class, 'common.release.release', [
            'categories',
        ]);
    }

    #[Test]
    public function affixesComposer_givenView_setsAffixKeys(): void
    {
        $this->assertComposerSetsKeys(AffixesComposer::class, 'common.group.affixes', [
            'allExpansions', 'dungeonExpansions', 'affixes', 'currentSeason', 'nextSeason', 'allAffixGroups', 'expansionsData', 'currentAffixes',
        ]);
    }

    #[Test]
    public function compositionComposer_givenView_setsCompositionKeys(): void
    {
        $this->assertComposerSetsKeys(CompositionComposer::class, 'common.group.composition', [
            'specializations', 'classes', 'racesClasses', 'allFactions',
        ]);
    }

    #[Test]
    public function dungeonGridTabsComposer_givenView_setsGridTabsKeys(): void
    {
        $this->assertComposerSetsKeys(DungeonGridTabsComposer::class, 'common.dungeon.gridtabs', [
            'activeExpansions', 'currentSeason', 'nextSeason',
        ]);
    }

    #[Test]
    public function dungeonSelectComposer_givenView_setsSelectKeys(): void
    {
        $this->assertComposerSetsKeys(DungeonSelectComposer::class, 'common.dungeon.select', [
            'currentSeason', 'nextSeason', 'allExpansions', 'allDungeons', 'allRaids', 'allActiveDungeons', 'allActiveRaids', 'siegeOfBoralus',
        ]);
    }

    #[Test]
    public function routeAttributesComposer_givenView_setsRouteAttributes(): void
    {
        $this->assertComposerSetsKeys(RouteAttributesComposer::class, 'common.dungeonroute.attributes', [
            'allRouteAttributes',
        ]);
    }

    #[Test]
    public function routePublishComposer_givenView_setsPublishedStates(): void
    {
        $this->assertComposerSetsKeys(RoutePublishComposer::class, 'common.dungeonroute.publish', [
            'allPublishedStates',
        ]);
    }

    #[Test]
    public function routeTierComposer_givenView_setsEaseTiers(): void
    {
        $this->assertComposerSetsKeys(RouteTierComposer::class, 'common.dungeonroute.tier', [
            'affixGroupEaseTiersByAffixGroup',
        ]);
    }

    #[Test]
    public function routeCoverageAffixGroupComposer_givenView_setsCoverageKeys(): void
    {
        $this->assertComposerSetsKeys(RouteCoverageAffixGroupComposer::class, 'common.dungeonroute.coverage.affixgroup', [
            'currentSeason', 'nextSeason', 'selectedSeason', 'currentAffixGroup', 'affixGroups', 'dungeons',
        ]);
    }

    #[Test]
    public function heatmapSearchComposer_givenView_setsHeatmapKeys(): void
    {
        $this->assertComposerSetsKeys(HeatmapSearchComposer::class, 'common.maps.controls.heatmapsearch', [
            'showAllEnabled', 'allAffixGroupsByActiveExpansion', 'featuredAffixesByActiveExpansion',
            'characterClassSpecializations', 'characterClasses', 'selectableSpellsByCategory', 'allRegions',
        ]);
    }

    #[Test]
    public function pullsComposer_givenView_setsShowAllEnabled(): void
    {
        $this->assertComposerSetsKeys(PullsComposer::class, 'common.maps.controls.pulls', [
            'showAllEnabled',
        ]);
    }

    #[Test]
    public function pullsWorkbenchComposer_givenView_setsSpellsSelect(): void
    {
        $this->assertComposerSetsKeys(PullsWorkbenchComposer::class, 'common.maps.controls.pullsworkbench', [
            'spellsSelect',
        ]);
    }

    #[Test]
    public function adminDungeonMappingVersionsComposer_givenView_setsAllGameVersions(): void
    {
        $this->assertComposerSetsKeys(AdminDungeonMappingVersionsComposer::class, 'admin.dungeon.mappingversions', [
            'allGameVersions',
        ]);
    }

    #[Test]
    public function adminNpcHealthEditComposer_givenView_setsAllGameVersions(): void
    {
        $this->assertComposerSetsKeys(AdminNpcHealthEditComposer::class, 'admin.npchealth.edit', [
            'allGameVersions',
        ]);
    }

    #[Test]
    public function adminSpellEditComposer_givenView_setsAllGameVersions(): void
    {
        $this->assertComposerSetsKeys(AdminSpellEditComposer::class, 'admin.spell.edit', [
            'allGameVersions',
        ]);
    }

    #[Test]
    public function adminMessageBannerComposer_givenView_setsMessageBanner(): void
    {
        $this->assertComposerSetsKeys(AdminMessageBannerComposer::class, 'admin.tools.messagebanner.set', [
            'messageBanner',
        ]);
    }

    #[Test]
    public function teamSelectComposer_givenView_setsTeams(): void
    {
        $this->assertComposerSetsKeys(TeamSelectComposer::class, 'common.team.select', [
            'teams',
        ]);
    }

    #[Test]
    public function simulateComposer_givenView_setsIsThundering(): void
    {
        $this->assertComposerSetsKeys(SimulateComposer::class, 'common.modal.simulate', [
            'isThundering',
        ]);
    }

    #[Test]
    public function simulateOptionsComposer_givenView_setsSimulateOptionsKeys(): void
    {
        $this->assertComposerSetsKeys(SimulateOptionsComposer::class, 'common.modal.simulateoptions.default', [
            'shroudedBountyTypes', 'affixes', 'isShrouded', 'raidBuffsOptions',
        ]);
    }

    #[Test]
    public function mappingVersionComposer_givenView_setsAllGameVersions(): void
    {
        $this->assertComposerSetsKeys(MappingVersionComposer::class, 'common.modal.mappingversion', [
            'allGameVersions',
        ]);
    }

    #[Test]
    public function rollbarComposer_givenView_setsLatestRelease(): void
    {
        $this->assertComposerSetsKeys(RollbarComposer::class, 'common.thirdparty.rollbar.rollbar', [
            'latestRelease',
        ]);
    }

    #[Test]
    public function profileEditComposer_givenView_setsProfileKeys(): void
    {
        $this->assertComposerSetsKeys(ProfileEditComposer::class, 'profile.edit', [
            'allClasses', 'allRegions',
        ]);
    }

    #[Test]
    public function profileNewRouteStyleComposer_givenView_setsNewRouteStyle(): void
    {
        $this->assertComposerSetsKeys(ProfileNewRouteStyleComposer::class, 'profile.overview', [
            'newRouteStyle',
        ]);
    }

    #[Test]
    public function discoverAffixGroupComposer_givenGameVersionData_setsAffixGroupKeys(): void
    {
        // Arrange
        $gameVersion = GameVersion::where('key', 'retail')->firstOrFail();
        $view        = view('dungeonroute.discover.category', ['gameVersion' => $gameVersion]);

        // Act
        app(DiscoverAffixGroupComposer::class)->compose($view);

        // Assert
        $this->assertViewHasKeys($view, ['currentAffixGroup', 'nextAffixGroup']);
    }

    #[Test]
    public function dungeonGridDiscoverComposer_givenAffixGroupData_setsTiers(): void
    {
        // Arrange
        $affixGroup = AffixGroup::firstOrFail();
        $view       = view('common.dungeon.griddiscover', [
            'currentAffixGroup' => $affixGroup,
            'nextAffixGroup'    => null,
        ]);

        // Act
        app(DungeonGridDiscoverComposer::class)->compose($view);

        // Assert
        $this->assertViewHasKeys($view, ['tiers']);
    }

    /**
     * @param class-string       $composerClass
     * @param array<int, string> $expectedKeys
     */
    private function assertComposerSetsKeys(string $composerClass, string $viewName, array $expectedKeys): void
    {
        // Arrange
        $view = view($viewName);

        // Act
        app($composerClass)->compose($view);

        // Assert
        $this->assertViewHasKeys($view, $expectedKeys);
    }

    /**
     * @param array<int, string> $expectedKeys
     */
    private function assertViewHasKeys(ViewContract $view, array $expectedKeys): void
    {
        $data = $view->getData();
        foreach ($expectedKeys as $expectedKey) {
            $this->assertArrayHasKey($expectedKey, $data, sprintf('Composer did not set expected view key "%s"', $expectedKey));
        }
    }
}
