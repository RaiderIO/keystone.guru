<?php

namespace Tests\Feature\Controller;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\GameVersion\GameVersion;
use App\Models\RouteAttribute;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Repositories\Interfaces\MapIconRepositoryInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

/**
 * Contract tests that POST the shared FE/BE payload fixtures (`tests/Fixtures/CreateRoute/payloads/*.json`) to the
 * real dungeon route creation endpoints. These fixtures are also consumed by the Vitest integration suite, which
 * builds the real form DOM and serializes it - this test proves the backend accepts exactly what the browser sends.
 *
 * Deliberately NOT DungeonRouteControllerTest.php: that file is owned by open MR #3533 (issue #3514) and this MR
 * must stay conflict-free with it. Once both merge, the two files' overlapping "empty dungeon_difficulty" coverage
 * should be deduplicated.
 */
#[Group('Controller')]
final class DungeonRouteCreateContractTest extends PublicTestCase
{
    private const string FIXTURE_PATH = __DIR__ . '/../../Fixtures/CreateRoute/payloads/%s.json';

    #[Test]
    public function saveNew_givenRetailMinimalDefaultsFixture_createsRouteWithNullDifficulty(): void
    {
        // Arrange
        Queue::fake();
        $user     = User::factory()->create();
        $dungeon  = $this->retailDungeon();
        $season   = $this->retailSeasonFor($dungeon);
        $affixIds = $this->nonTeemingAffixGroupIds($season, 1);

        $fields = $this->loadFixture('retail-minimal-defaults', [
            'dungeon_id'     => $dungeon->id,
            'key_level_min'  => $season->key_level_min,
            'key_level_max'  => $season->key_level_max,
            'affix_group_id' => $affixIds[0],
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            // Pins the #3514 regression: Tom Select submits "" for the untouched, optionless difficulty select.
            $this->assertNull($dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenRetailMultipleAffixesFixture_createsRouteWithAllSelectedAffixes(): void
    {
        // Arrange
        Queue::fake();
        $user     = User::factory()->create();
        $dungeon  = $this->retailDungeon();
        $season   = $this->retailSeasonFor($dungeon);
        $affixIds = $this->nonTeemingAffixGroupIds($season, 3);

        $fields = $this->loadFixture('retail-multiple-affixes', [
            'dungeon_id'       => $dungeon->id,
            'key_level_min'    => $season->key_level_min,
            'key_level_max'    => $season->key_level_max,
            'affix_group_id_1' => $affixIds[0],
            'affix_group_id_2' => $affixIds[1],
            'affix_group_id_3' => $affixIds[2],
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertNull($dungeonRoute->getRawOriginal('dungeon_difficulty'));
            $this->assertEqualsCanonicalizing($affixIds, $dungeonRoute->affixgroups()->pluck('affix_group_id')->all());
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenRetailWithTeamFixture_createsRouteAttachedToTeam(): void
    {
        // Arrange
        Queue::fake();
        $user     = User::factory()->create();
        $dungeon  = $this->retailDungeon();
        $season   = $this->retailSeasonFor($dungeon);
        $affixIds = $this->nonTeemingAffixGroupIds($season, 1);
        $team     = $this->createTeamFor($user);

        $fields = $this->loadFixture('retail-with-team', [
            'dungeon_id'     => $dungeon->id,
            'key_level_min'  => $season->key_level_min,
            'key_level_max'  => $season->key_level_max,
            'affix_group_id' => $affixIds[0],
            'team_id'        => $team->id,
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($team->id, $dungeonRoute->team_id);
        } finally {
            $dungeonRoute?->delete();
            TeamUser::where('team_id', $team->id)->delete();
            $team->delete();
        }
    }

    #[Test]
    public function saveNew_givenRetailNoTeamsFixture_createsRouteWithoutTeam(): void
    {
        // Arrange
        Queue::fake();
        $user     = User::factory()->create();
        $dungeon  = $this->retailDungeon();
        $season   = $this->retailSeasonFor($dungeon);
        $affixIds = $this->nonTeemingAffixGroupIds($season, 1);

        $fields = $this->loadFixture('retail-no-teams', [
            'dungeon_id'     => $dungeon->id,
            'key_level_min'  => $season->key_level_min,
            'key_level_max'  => $season->key_level_max,
            'affix_group_id' => $affixIds[0],
        ]);

        $this->assertArrayNotHasKey('team_id', $fields, 'retail-no-teams.json must not submit team_id at all.');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertNull($dungeonRoute->team_id);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenRetailFullCompositionFixture_createsRouteWithPartyMemberOne(): void
    {
        // Arrange
        Queue::fake();
        $user     = User::factory()->create();
        $dungeon  = $this->retailDungeon();
        $season   = $this->retailSeasonFor($dungeon);
        $affixIds = $this->nonTeemingAffixGroupIds($season, 1);

        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_WARRIOR)->firstOrFail();
        $specialization = CharacterClassSpecialization::where('character_class_id', $characterClass->id)->firstOrFail();
        $characterRace  = CharacterRace::where('key', CharacterRace::CHARACTER_RACE_HUMAN)->firstOrFail();

        $fields = $this->loadFixture('retail-full-composition', [
            'dungeon_id'        => $dungeon->id,
            'key_level_min'     => $season->key_level_min,
            'key_level_max'     => $season->key_level_max,
            'affix_group_id'    => $affixIds[0],
            'faction_id'        => Faction::ALL[Faction::FACTION_ALLIANCE],
            'class_id'          => $characterClass->id,
            'specialization_id' => $specialization->id,
            'race_id'           => $characterRace->id,
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame(Faction::ALL[Faction::FACTION_ALLIANCE], $dungeonRoute->faction_id);
            $this->assertSame([$characterClass->id], $dungeonRoute->playerclasses()->pluck('character_class_id')->all());
            // Surprising pre-existing behaviour (not caused by this MR, not fixed here): DungeonRouteSubmitFormRequest
            // has validation rules for 'class' and 'race' but none for 'specialization', so FormRequest::validated()
            // silently drops the submitted specialization[] values before DungeonRouteSaveService ever sees them.
            $this->assertSame([], $dungeonRoute->playerspecializations()->pluck('character_class_specialization_id')->all());
            // Unlike 'class', DungeonRouteSaveService::syncRequestRelations() inserts 'race' values verbatim
            // (no existence filtering against real race ids), so party members #2-5's sentinel "0" values are
            // persisted as-is alongside party member #1's real race id.
            $this->assertEqualsCanonicalizing(
                [$characterRace->id, 0, 0, 0, 0],
                $dungeonRoute->playerraces()->pluck('character_race_id')->all(),
            );
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenRetailAttributesFixture_createsRouteWithBothAttributes(): void
    {
        // Arrange
        Queue::fake();
        $user     = User::factory()->create();
        $dungeon  = $this->retailDungeon();
        $season   = $this->retailSeasonFor($dungeon);
        $affixIds = $this->nonTeemingAffixGroupIds($season, 1);

        $attributeIds = RouteAttribute::query()->orderBy('id')->limit(2)->pluck('id')->all();
        $this->assertCount(2, $attributeIds, 'Seeded DB must have at least 2 route_attributes rows.');

        $fields = $this->loadFixture('retail-attributes', [
            'dungeon_id'     => $dungeon->id,
            'key_level_min'  => $season->key_level_min,
            'key_level_max'  => $season->key_level_max,
            'affix_group_id' => $affixIds[0],
            'attribute_id_1' => $attributeIds[0],
            'attribute_id_2' => $attributeIds[1],
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertEqualsCanonicalizing(
                $attributeIds,
                $dungeonRoute->routeattributesraw()->pluck('route_attribute_id')->all(),
            );
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenRetailMultipleStartIconsFixture_createsRouteWithChosenStartIcon(): void
    {
        // Arrange
        Queue::fake();
        $user     = User::factory()->create();
        $dungeon  = $this->retailDungeon();
        $season   = $this->retailSeasonFor($dungeon);
        $affixIds = $this->nonTeemingAffixGroupIds($season, 1);

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion);

        $startIcons = app(MapIconRepositoryInterface::class)->getDungeonStartsForMappingVersion($mappingVersion->id);
        // Judgment call: the seeded DB's currently-active season dungeons only ever have a single dungeon start
        // icon. retailDungeon() prefers a dungeon with >1 start icons where one exists (documented in its
        // docblock); if none exists at all in the seeded data we fall back to the single icon so the fixture is
        // still exercised (with a lone icon instead of a real "multiple" choice).
        $this->assertNotEmpty($startIcons, 'Chosen dungeon must have at least one dungeon start icon.');
        $startIconId = (int)$startIcons->first()['id'];

        $fields = $this->loadFixture('retail-multiple-start-icons', [
            'dungeon_id'                => $dungeon->id,
            'key_level_min'             => $season->key_level_min,
            'key_level_max'             => $season->key_level_max,
            'affix_group_id'            => $affixIds[0],
            'dungeon_start_map_icon_id' => $startIconId,
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($startIconId, $dungeonRoute->getRawOriginal('dungeon_start_map_icon_id'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenClassicNonSpeedrunFixture_createsRouteWithNullDifficulty(): void
    {
        // Arrange
        Queue::fake();
        $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $user               = User::factory()->create(['game_version_id' => $classicGameVersion->id]);
        $dungeon            = $this->classicNonSpeedrunDungeon($classicGameVersion);

        $fields = $this->loadFixture('classic-nonspeedrun', [
            'dungeon_id' => $dungeon->id,
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertNull($dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenClassicSpeedrunSsc10ManFixture_createsRouteWithDifficultyOne(): void
    {
        // Arrange
        Queue::fake();
        $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $user               = User::factory()->create(['game_version_id' => $classicGameVersion->id]);
        // Judgment call: not literally Serpentshrine Cavern - the seeded DB's SSC only has 25-man enabled.
        // Any classic speedrun dungeon with both 10-man and 25-man enabled exercises the same "first enabled
        // difficulty" behaviour the fixture describes.
        $dungeon = $this->classicSpeedrunDungeonWithDifficulties($classicGameVersion, [1, 2]);

        $fields = $this->loadFixture('classic-speedrun-ssc-10man', [
            'dungeon_id' => $dungeon->id,
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame(1, $dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenClassicSpeedrunSsc25ManFixture_createsRouteWithDifficultyTwo(): void
    {
        // Arrange
        Queue::fake();
        $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $user               = User::factory()->create(['game_version_id' => $classicGameVersion->id]);
        $dungeon            = $this->classicSpeedrunDungeonWithDifficulties($classicGameVersion, [1, 2]);

        $fields = $this->loadFixture('classic-speedrun-ssc-25man', [
            'dungeon_id' => $dungeon->id,
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame(2, $dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenClassicSpeedrunTempestKeepDefaultFixture_createsRouteWithDifficultyTwo(): void
    {
        // Arrange
        Queue::fake();
        $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $user               = User::factory()->create(['game_version_id' => $classicGameVersion->id]);
        // Judgment call: not literally Tempest Keep - the seeded DB's actual Tempest Keep/SSC dungeons only have
        // 25-man enabled too (same as the -like standin below), so this simply picks a dungeon with only
        // 25-man enabled to exercise the "single enabled difficulty is auto-selected" behaviour.
        $dungeon = $this->classicSpeedrunDungeonWithDifficulties($classicGameVersion, [2]);

        $fields = $this->loadFixture('classic-speedrun-tempest-keep-default', [
            'dungeon_id' => $dungeon->id,
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame(2, $dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNew_givenClassicSpeedrunThenSwitchFixture_pinsStaleDifficultyBugIssue3535(): void
    {
        // Arrange
        Queue::fake();
        $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $user               = User::factory()->create(['game_version_id' => $classicGameVersion->id]);
        // The target dungeon after switching away from the speedrun dungeon - itself non-speedrun.
        $dungeon = $this->classicNonSpeedrunDungeon($classicGameVersion);

        $fields = $this->loadFixture('classic-speedrun-then-switch', [
            'dungeon_id' => $dungeon->id,
        ]);

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->findCreatedRoute($dungeon, $user);
            $this->assertNotNull($dungeonRoute);
            // BUG #3535, pinned as-is (not a fix): dungeondifficultyselect.js only hides the difficulty container
            // on switch-away, it never clears the select's stale value, so a non-speedrun dungeon can end up with
            // a persisted dungeon_difficulty left over from a previously-selected speedrun dungeon.
            $this->assertSame(1, $dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenGuestMinimalFixture_createsTemporaryRouteAsGuest(): void
    {
        // Arrange
        Queue::fake();
        $dungeon = $this->retailDungeon();
        $season  = $this->retailSeasonFor($dungeon);

        $fields = $this->loadFixture('temporary-guest-minimal', [
            'dungeon_id'    => $dungeon->id,
            'key_level_min' => $season->key_level_min,
            'key_level_max' => $season->key_level_max,
        ]);

        $dungeonRoute = null;

        try {
            // Act (no actingAs - guest request)
            $response = $this->post(route('dungeonroute.temporary.savenew'), $fields);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = DungeonRoute::query()
                ->where('dungeon_id', $dungeon->id)
                ->where('author_id', -1)
                ->orderByDesc('id')
                ->first();
            $this->assertNotNull($dungeonRoute);
            $this->assertNull($dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function new_givenRetailUser_rendersFormFieldsMatchingRetailFixtureFieldNames(): void
    {
        // Arrange
        $user = User::factory()->create();
        // common/team/select.blade.php only renders the team select when the user has at least one team
        // (see retail-no-teams.json) - give the user a team so team_id is part of the rendered field set too.
        $team = $this->createTeamFor($user);

        $retailFixtureNames = [
            'retail-minimal-defaults',
            'retail-multiple-affixes',
            'retail-with-team',
            'retail-no-teams',
            'retail-full-composition',
            'retail-attributes',
            'retail-multiple-start-icons',
        ];

        try {
            $expectedFieldNames = collect($retailFixtureNames)
                ->flatMap(fn(string $name): array => $this->rawFixtureFieldNames($name))
                ->map(static fn(string $name): string => Str::before($name, '[]'))
                ->unique()
                ->values();

            // Act
            $response = $this->actingAs($user)->get(route('dungeonroute.new'));

            // Assert
            $response->assertOk();

            $renderedFieldNames = $this->extractCreateRouteFormFieldNames((string)$response->getContent())
                ->map(static fn(string $name): string => Str::before($name, '[]'))
                ->unique()
                ->values();

            // Every field the retail fixtures rely on must still exist as a named control in the real create-route
            // form - this catches a renamed/removed form field even though this test never runs any JS.
            foreach ($expectedFieldNames as $expectedFieldName) {
                $this->assertTrue(
                    $renderedFieldNames->contains($expectedFieldName),
                    sprintf(
                        'Expected form field "%s" (used by a retail create-route fixture) to be present in the rendered create-route form. Rendered fields: %s',
                        $expectedFieldName,
                        $renderedFieldNames->implode(', '),
                    ),
                );
            }
        } finally {
            TeamUser::where('team_id', $team->id)->delete();
            $team->delete();
        }
    }

    /**
     * Reads a payload fixture, substitutes its `{{placeholder}}` tokens, and converts duplicate `name[]` entries
     * into the array shape Laravel's form POST parsing expects.
     *
     * @param  array<string, int|string> $substitutions
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function loadFixture(string $fixtureName, array $substitutions): array
    {
        $fixture = $this->decodeFixture($fixtureName);

        $fields = [];
        foreach ($fixture['fields'] as $field) {
            $name  = (string)$field['name'];
            $value = $this->substitutePlaceholders((string)$field['value'], $substitutions, $fixtureName);

            if (str_ends_with($name, '[]')) {
                $key            = substr($name, 0, -2);
                $fields[$key][] = $value;
            } else {
                $fields[$name] = $value;
            }
        }

        return $fields;
    }

    /**
     * @return list<string>
     *
     * @throws JsonException
     */
    private function rawFixtureFieldNames(string $fixtureName): array
    {
        $fixture = $this->decodeFixture($fixtureName);

        return collect($fixture['fields'])
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{description: string, endpoint: string, gameVersion: string, auth: bool, fields: list<array{name: string, value: string}>}
     *
     * @throws JsonException
     */
    private function decodeFixture(string $fixtureName): array
    {
        $path = sprintf(self::FIXTURE_PATH, $fixtureName);

        /** @var array{description: string, endpoint: string, gameVersion: string, auth: bool, fields: list<array{name: string, value: string}>} $decoded */
        $decoded = json_decode(
            file_get_contents($path),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $decoded;
    }

    /**
     * @param array<string, int|string> $substitutions
     */
    private function substitutePlaceholders(string $value, array $substitutions, string $fixtureName): string
    {
        return preg_replace_callback('/\{\{(\w+)}}/', function (array $matches) use ($substitutions, $fixtureName): string {
            $token = $matches[1];

            if (!array_key_exists($token, $substitutions)) {
                throw new RuntimeException(sprintf('Fixture "%s" uses unresolved placeholder {{%s}}.', $fixtureName, $token));
            }

            return (string)$substitutions[$token];
        }, $value);
    }

    /**
     * Finds a dungeon eligible for a retail route creation: active (same criteria as
     * DungeonRouteSubmitFormRequest's dungeon_id rule), not speedrun-enabled, and with at least one season (so
     * DungeonRouteSaveService resolves a non-null active season). Prefers a dungeon whose current mapping version
     * has more than one dungeon-start map icon, so retail-multiple-start-icons.json gets a real multi-icon
     * scenario; falls back to any season-eligible dungeon if none has multiple start icons in the seeded DB.
     */
    private function retailDungeon(): Dungeon
    {
        $candidates = Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', false)
            ->select('dungeons.*')
            ->get()
            ->filter(static fn(Dungeon $dungeon): bool => $dungeon->hasMappingVersionWithSeasons())
            ->values();

        $mapIconRepository = app(MapIconRepositoryInterface::class);

        $withMultipleStartIcons = $candidates->first(static function (Dungeon $dungeon) use ($mapIconRepository): bool {
            $mappingVersion = $dungeon->getCurrentMappingVersion();

            return $mappingVersion !== null
                && $mapIconRepository->getDungeonStartsForMappingVersion($mappingVersion->id)->count() > 1;
        });

        $dungeon = $withMultipleStartIcons ?? $candidates->first();

        if ($dungeon === null) {
            throw new RuntimeException('Seeded DB has no active, non-speedrun, season-eligible retail dungeon.');
        }

        return $dungeon;
    }

    /**
     * Resolves the season the same way DungeonRouteSaveService::persist() does for a new route (see its private
     * resolveSeasonForEdit()): the upcoming season for the dungeon, or otherwise its most recent season.
     */
    private function retailSeasonFor(Dungeon $dungeon): Season
    {
        $seasonService = app(SeasonServiceInterface::class);

        $season = $seasonService->getUpcomingSeasonForDungeon($dungeon)
            ?? $seasonService->getMostRecentSeasonForDungeon($dungeon);

        if ($season === null) {
            throw new RuntimeException(sprintf('Dungeon #%d has no resolvable season.', $dungeon->id));
        }

        return $season;
    }

    /**
     * @return list<int>
     */
    private function nonTeemingAffixGroupIds(Season $season, int $count): array
    {
        $ids = $season->affixGroups()
            ->get()
            ->reject(static fn(AffixGroup $affixGroup): bool => $affixGroup->hasAffix(Affix::AFFIX_TEEMING))
            ->take($count)
            ->pluck('id')
            ->values()
            ->all();

        if (count($ids) < $count) {
            throw new RuntimeException(sprintf('Season #%d does not have %d non-Teeming affix groups.', $season->id, $count));
        }

        return $ids;
    }

    /**
     * Finds a classic-active, non-speedrun dungeon reachable under the given (classic) game version.
     */
    private function classicNonSpeedrunDungeon(GameVersion $gameVersion): Dungeon
    {
        $dungeon = Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', false)
            ->select('dungeons.*')
            ->get()
            ->first(static fn(Dungeon $dungeon): bool => $dungeon->hasMappingVersionForGameVersion($gameVersion));

        if ($dungeon === null) {
            throw new RuntimeException('Seeded DB has no active, non-speedrun classic dungeon.');
        }

        return $dungeon;
    }

    /**
     * Finds an active, speedrun-enabled dungeon (reachable under the given game version) whose enabled speedrun
     * difficulties exactly match the given set.
     *
     * @param list<int> $expectedDifficulties
     */
    private function classicSpeedrunDungeonWithDifficulties(GameVersion $gameVersion, array $expectedDifficulties): Dungeon
    {
        sort($expectedDifficulties);

        $dungeon = Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', true)
            ->select('dungeons.*')
            ->with('dungeonSpeedrunDifficulties')
            ->get()
            ->first(static function (Dungeon $dungeon) use ($gameVersion, $expectedDifficulties): bool {
                if (!$dungeon->hasMappingVersionForGameVersion($gameVersion)) {
                    return false;
                }

                $enabled = $dungeon->getEnabledSpeedrunDifficulties();
                sort($enabled);

                return $enabled === $expectedDifficulties;
            });

        if ($dungeon === null) {
            throw new RuntimeException(sprintf(
                'Seeded DB has no active classic speedrun dungeon with difficulties [%s].',
                implode(',', $expectedDifficulties),
            ));
        }

        return $dungeon;
    }

    private function createTeamFor(User $user): Team
    {
        $team = Team::create([
            'public_key'               => Team::generateRandomPublicKey(),
            'name'                     => 'Contract Test Team',
            'description'              => '',
            'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
            'default_role'             => TeamUser::ROLE_MEMBER,
            'route_publishing_enabled' => true,
        ]);

        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role'    => TeamUser::ROLE_MODERATOR,
        ]);

        return $team;
    }

    private function findCreatedRoute(Dungeon $dungeon, User $user): ?DungeonRoute
    {
        return DungeonRoute::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('author_id', $user->id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Extracts the `name="..."` control names from the create-route form specifically (not the create-temporary
     * or MDT-import forms/tabs that share the same page).
     *
     * @return Collection<int, non-empty-string>
     */
    private function extractCreateRouteFormFieldNames(string $html): Collection
    {
        $formAction  = route('dungeonroute.savenew');
        $startNeedle = sprintf('action="%s"', $formAction);
        $startPos    = strpos($html, $startNeedle);

        $this->assertNotFalse($startPos, sprintf('Could not find the create-route form (action="%s") in the rendered page.', $formAction));

        $endPos = strpos($html, '</form>', $startPos);
        $this->assertNotFalse($endPos, 'Could not find the closing </form> tag for the create-route form.');

        $formHtml = substr($html, $startPos, $endPos - $startPos);

        preg_match_all('/name="([^"]+)"/', $formHtml, $matches);

        return collect($matches[1])->unique()->values();
    }
}
