<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Facades\View;
use Illuminate\View\ViewException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('RoutePicker')]
final class PickerTest extends PublicTestCase
{
    #[Test]
    public function render_givenALockedSeason_locksTheSeasonAndItsPoolAndOffersOnlyThePoolDungeons(): void
    {
        // Arrange
        /** @var Season $season */
        $season = Season::query()->has('dungeons')->with('dungeons')->firstOrFail();
        /** @var Dungeon $outsidePool */
        $outsidePool = Dungeon::query()->whereNotIn('id', $season->dungeons->pluck('id'))->firstOrFail();

        // Act
        [$html, $options] = $this->renderPicker(['lockedSeason' => $season]);

        // Assert
        $this->assertSame([
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'season_id'       => $season->id,
            'dungeon_ids'     => $season->dungeons->pluck('id')->all(),
        ], $options['lockedParameters']);
        $this->assertSame(['mine' => 1], $options['sourceParameters']);

        $dungeonOptions = $this->dungeonOptionValues($html);
        $this->assertContains('-1', $dungeonOptions);
        foreach ($season->dungeons as $dungeon) {
            $this->assertContains((string)$dungeon->id, $dungeonOptions);
        }
        $this->assertNotContains((string)$outsidePool->id, $dungeonOptions);
        $this->assertStringContainsString(e($season->name_long), $html);
    }

    #[Test]
    public function render_givenNoLockedSeason_locksOnlyTheGameVersion(): void
    {
        // Arrange - nothing beyond the defaults

        // Act
        [, $options] = $this->renderPicker();

        // Assert
        $this->assertSame(
            ['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]],
            $options['lockedParameters'],
        );
    }

    #[Test]
    public function render_givenAPreselectedDungeon_startsTheDungeonFilterOnIt(): void
    {
        // Arrange
        /** @var Season $season */
        $season = Season::query()->has('dungeons')->with('dungeons')->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = $season->dungeons->first();

        // Act
        [$html] = $this->renderPicker(['lockedSeason' => $season, 'preselectedDungeon' => $dungeon]);

        // Assert
        $this->assertMatchesRegularExpression(
            sprintf('/<option value="%d" selected="selected">/', $dungeon->id),
            $html,
        );
    }

    #[Test]
    public function render_givenTargetDetails_passesThemToTheDrawer(): void
    {
        // Arrange
        $existingPublicKeys = ['abc1234', 'def5678'];

        // Act
        [$html, $options] = $this->renderPicker([
            'title'              => 'Add routes to My set',
            'existingPublicKeys' => $existingPublicKeys,
            'max'                => 24,
            'actionUrl'          => '/some/target/add',
            'actionFieldName'    => 'route_keys',
        ]);

        // Assert
        $this->assertStringContainsString('Add routes to My set', $html);
        $this->assertSame($existingPublicKeys, $options['existingPublicKeys']);
        $this->assertSame(24, $options['max']);
        $this->assertSame('/some/target/add', $options['actionUrl']);
        $this->assertSame('route_keys', $options['actionFieldName']);
        $this->assertSame(25, $options['pageSize']);
    }

    #[Test]
    public function render_givenNoMax_passesNull(): void
    {
        // Arrange - nothing beyond the defaults

        // Act
        [, $options] = $this->renderPicker();

        // Assert
        $this->assertNull($options['max']);
    }

    #[Test]
    public function render_givenNoActionUrl_passesNullSoTheHostActsOnThePickedRoutes(): void
    {
        // Arrange - nothing beyond the defaults

        // Act
        [, $options] = $this->renderPicker(['actionUrl' => null]);

        // Assert
        $this->assertNull($options['actionUrl']);
    }

    #[Test]
    public function render_givenNoAction_picksTheAddAction(): void
    {
        // Arrange - nothing beyond the defaults

        // Act
        [$html, $options] = $this->renderPicker();

        // Assert
        $this->assertSame('dungeonroute_picker_add', $options['actionKeyPrefix']);
        $this->assertSame('POST', $options['actionMethod']);
        $this->assertFalse($options['confirmsAction']);
        $this->assertStringContainsString('btn btn-primary', $html);
    }

    #[Test]
    public function render_givenTheDeleteAction_confirmsItAndSendsADelete(): void
    {
        // Arrange - nothing beyond the defaults

        // Act
        [$html, $options] = $this->renderPicker(['action' => 'delete']);

        // Assert
        $this->assertSame('dungeonroute_picker_delete', $options['actionKeyPrefix']);
        $this->assertSame('DELETE', $options['actionMethod']);
        $this->assertTrue($options['confirmsAction']);
        $this->assertStringContainsString('btn btn-danger', $html);
        $this->assertStringContainsString(__('view_common.dungeonroute.picker.delete_none'), $html);
    }

    #[Test]
    public function render_givenAnUnknownAction_throws(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->renderPicker(['action' => 'publish']);

        // Assert - the expected exception
    }

    #[Test]
    public function render_givenNoLockedGameVersion_locksNothingAndOffersEveryGameVersionsDungeons(): void
    {
        // Arrange
        /** @var Dungeon $classicDungeon */
        $classicDungeon = Dungeon::query()
            ->whereHas('mappingVersions', static fn($query) => $query->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]))
            ->firstOrFail();

        // Act
        [$html, $options] = $this->renderPicker(['lockedGameVersion' => null]);

        // Assert
        $this->assertSame([], $options['lockedParameters']);
        $this->assertContains((string)$classicDungeon->id, $this->dungeonOptionValues($html));
    }

    #[Test]
    public function render_givenALockedSeason_carriesItsAffixGroupsForTheFilterIcons(): void
    {
        // Arrange
        /** @var Season $season */
        $season      = Season::query()->has('dungeons')->has('affixGroups')->with(['dungeons'])->firstOrFail();
        $affixGroups = $season->affixGroups()->with('affixes')->get();

        // Act
        [, $options] = $this->renderPicker(['lockedSeason' => $season]);

        // Assert
        $this->assertSame(
            $affixGroups->pluck('id')->all(),
            array_keys($options['affixGroups']),
        );
        /** @var AffixGroup $affixGroup */
        $affixGroup = $affixGroups->first();
        $this->assertSame(
            $affixGroup->affixes->map(static fn(Affix $affix): array => ['class' => $affix->image_name, 'name' => $affix->name])->all(),
            $options['affixGroups'][$affixGroup->id],
        );
    }

    /**
     * The drawer must carry everything it needs to be rebuilt: the new-collection form swaps it in when the
     * user picks another season, without the page scripts that set it up on load.
     */
    #[Test]
    public function render_givenTheDrawer_carriesItsOwnInlineCodeAttributes(): void
    {
        // Arrange - nothing beyond the defaults

        // Act
        [$html, $options] = $this->renderPicker();

        // Assert
        $this->assertStringContainsString('data-inline-id="test_picker"', $html);
        $this->assertStringContainsString('data-inline-path="common/dungeonroute/picker"', $html);
        $this->assertStringContainsString(e(json_encode($options)), $html);
    }

    #[Test]
    public function render_givenAnUnknownSourceScope_throws(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->renderPicker(['sourceScope' => 'everyone']);

        // Assert - the expected exception
    }

    #[Test]
    public function render_givenTheUnassignedByMembersScope_listsTheTeamsUnassignedMemberRoutes(): void
    {
        // Arrange
        $team = new Team(['name' => 'Picker Raiders', 'public_key' => 'abc1234']);

        // Act
        [$html, $options] = $this->renderPicker(['sourceScope' => 'unassigned_by_members', 'sourceTeam' => $team]);

        // Assert
        $this->assertSame(['team_public_key' => 'abc1234', 'available' => 1], $options['sourceParameters']);
        $this->assertStringContainsString('Picker Raiders', $html);
        $this->assertStringNotContainsString('id="test_picker_tags"', $html);
    }

    #[Test]
    public function render_givenTheUnassignedByMembersScopeWithoutATeam_throws(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->renderPicker(['sourceScope' => 'unassigned_by_members']);

        // Assert - the expected exception
    }

    /**
     * @param  array<string, mixed>                      $parameters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function renderPicker(array $parameters = []): array
    {
        $view = view('common.dungeonroute.picker', array_merge([
            'id'                => 'test_picker',
            'title'             => 'Add routes',
            'lockedGameVersion' => GameVersion::findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]),
            'addUrl'            => '/target/add',
        ], $parameters));

        $scripts = '';

        try {
            // Sections are flushed once rendering finishes, so the inline script is read in the callback
            $html = $view->render(static function () use (&$scripts): void {
                $scripts = View::yieldContent('scripts');
            });
        } catch (ViewException $viewException) {
            throw $viewException->getPrevious() ?? $viewException;
        }

        $this->assertSame(1, preg_match("/_inlineManager\\.init\\('test_picker', 'common\\/dungeonroute\\/picker', (\\{.*?\\})\\);/s", $scripts, $matches));

        return [$html, json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)];
    }

    /**
     * @return array<int, string>
     */
    private function dungeonOptionValues(string $html): array
    {
        preg_match('/<select[^>]*id="test_picker_dungeon"[^>]*>(.*?)<\/select>/s', $html, $select);
        preg_match_all('/<option value="(-?\d+)"/', $select[1] ?? '', $values);

        return $values[1];
    }
}
