<?php

namespace Tests\Feature\Mcp\Tools\Route;

use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\Route\GetRouteOptionsTool;
use App\Mcp\Tools\Route\UpdateRouteSettingsTool;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\DungeonStart;
use App\Models\PublishedState;
use App\Models\RouteAttribute;
use App\Models\Spell\Spell;
use App\Service\User\ApiTokenScope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Mcp\McpTestCase;

/**
 * update_route_settings (party composition and the rest of the editor's settings tab) and get_route_options.
 */
#[Group('Mcp')]
#[Group('McpRouteSettings')]
final class RouteSettingsToolsTest extends McpTestCase
{
    #[Test]
    public function updateRouteSettings_givenParty_replacesTheCompositionAndKeepsEverythingElse(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon(requireDungeonStart: true);
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $dungeonStartId             = (int)$mappingVersion->dungeonStarts()->value('id');
        $dungeonRoute->update([
            'title'            => 'Keep this title',
            'pull_gradient'    => '0 #ff0000,100 #00ff00',
            'level_min'        => 4,
            'level_max'        => 9,
            'dungeon_start_id' => $dungeonStartId,
        ]);
        $specializations = CharacterClassSpecialization::query()->orderBy('id')->limit(2)->get();
        $race            = CharacterRace::query()->orderBy('id')->firstOrFail();

        try {
            // Act
            $response = KeystoneGuruServer::tool(UpdateRouteSettingsTool::class, [
                'route' => $dungeonRoute->public_key,
                'party' => $specializations->map(static fn(CharacterClassSpecialization $specialization) => [
                    'class_id'          => $specialization->character_class_id,
                    'specialization_id' => $specialization->id,
                    'race_id'           => $race->id,
                ])->all(),
            ]);

            // Assert
            $response->assertHasNoErrors();
            $this->assertEqualsCanonicalizing(
                $specializations->pluck('character_class_id')->unique()->values()->all(),
                DungeonRoutePlayerClass::query()->where('dungeon_route_id', $dungeonRoute->id)->pluck('character_class_id')->map(static fn($id) => (int)$id)->all(),
            );
            $this->assertEqualsCanonicalizing(
                $specializations->pluck('id')->all(),
                DungeonRoutePlayerSpecialization::query()->where('dungeon_route_id', $dungeonRoute->id)->pluck('character_class_specialization_id')->map(static fn($id) => (int)$id)->all(),
            );
            $this->assertSame(2, DungeonRoutePlayerRace::query()->where('dungeon_route_id', $dungeonRoute->id)->count());

            $dungeonRoute->refresh();
            $this->assertSame('Keep this title', $dungeonRoute->title);
            $this->assertSame('0 #ff0000,100 #00ff00', $dungeonRoute->pull_gradient);
            $this->assertSame(4, $dungeonRoute->level_min);
            $this->assertSame(9, $dungeonRoute->level_max);
            $this->assertSame($dungeonStartId, $dungeonRoute->dungeon_start_id);
            $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $dungeonRoute->published_state_id);

            $party = $this->structuredContent($response)['party'];
            $this->assertEqualsCanonicalizing($specializations->pluck('id')->all(), $party['specialization_ids']);
        } finally {
            $this->deletePartyRows($dungeonRoute->id);
        }
    }

    #[Test]
    public function updateRouteSettings_givenClassOnlyParty_dropsThePreviousSpecializationsAndRaces(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $specialization             = CharacterClassSpecialization::query()->orderBy('id')->firstOrFail();
        $race                       = CharacterRace::query()->orderBy('id')->firstOrFail();
        $otherClass                 = CharacterClass::query()->whereKeyNot($specialization->character_class_id)->orderBy('id')->firstOrFail();
        DungeonRoutePlayerClass::create(['dungeon_route_id' => $dungeonRoute->id, 'character_class_id' => $specialization->character_class_id]);
        DungeonRoutePlayerSpecialization::create(['dungeon_route_id' => $dungeonRoute->id, 'character_class_specialization_id' => $specialization->id]);
        DungeonRoutePlayerRace::create(['dungeon_route_id' => $dungeonRoute->id, 'character_race_id' => $race->id]);

        // Act
        $response = KeystoneGuruServer::tool(UpdateRouteSettingsTool::class, [
            'route' => $dungeonRoute->public_key,
            'party' => [['class_id' => $otherClass->id]],
        ]);

        // Assert
        $response->assertHasNoErrors();
        $this->assertSame([$otherClass->id], DungeonRoutePlayerClass::query()->where('dungeon_route_id', $dungeonRoute->id)->pluck('character_class_id')->map(static fn($id) => (int)$id)->all());
        $this->assertSame(0, DungeonRoutePlayerSpecialization::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        $this->assertSame(0, DungeonRoutePlayerRace::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
    }

    #[Test]
    public function updateRouteSettings_givenEmptyPartyAndAttributes_clearsThem(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $routeAttribute             = RouteAttribute::query()->orderBy('id')->firstOrFail();
        DungeonRoutePlayerClass::create(['dungeon_route_id' => $dungeonRoute->id, 'character_class_id' => CharacterClass::query()->value('id')]);
        DungeonRouteAttribute::create(['dungeon_route_id' => $dungeonRoute->id, 'route_attribute_id' => $routeAttribute->id]);

        // Act
        $response = KeystoneGuruServer::tool(UpdateRouteSettingsTool::class, [
            'route'         => $dungeonRoute->public_key,
            'party'         => [],
            'attribute_ids' => [],
        ]);

        // Assert
        $response->assertHasNoErrors();
        $this->assertSame(0, DungeonRoutePlayerClass::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        $this->assertSame(0, DungeonRouteAttribute::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
    }

    #[Test]
    public function updateRouteSettings_givenSpecializationOfAnotherClass_refusesTheParty(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $specialization             = CharacterClassSpecialization::query()->orderBy('id')->firstOrFail();
        $otherClass                 = CharacterClass::query()->whereKeyNot($specialization->character_class_id)->firstOrFail();

        try {
            // Act
            $response = KeystoneGuruServer::tool(UpdateRouteSettingsTool::class, [
                'route' => $dungeonRoute->public_key,
                'party' => [['class_id' => $otherClass->id, 'specialization_id' => $specialization->id]],
            ]);

            // Assert
            $response->assertHasErrors([__('mcp.error.specialization_not_of_class', ['member' => 1])]);
            $this->assertSame(0, DungeonRoutePlayerSpecialization::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            $this->deletePartyRows($dungeonRoute->id);
        }
    }

    #[Test]
    public function updateRouteSettings_givenTitle_changesTheTitle(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);

        // Act
        $response = KeystoneGuruServer::tool(UpdateRouteSettingsTool::class, [
            'route' => $dungeonRoute->public_key,
            'title' => 'A better title',
        ]);

        // Assert
        $response->assertHasNoErrors();
        $this->assertSame('A better title', $dungeonRoute->fresh()->title);
    }

    #[Test]
    public function updateRouteSettings_givenDungeonStartOfAnotherMapping_refusesIt(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $foreignStartId             = DungeonStart::query()->where('mapping_version_id', '<>', $mappingVersion->id)->value('id');
        $this->assertNotNull($foreignStartId, 'The seed holds no dungeon start of another mapping');

        // Act
        $response = KeystoneGuruServer::tool(UpdateRouteSettingsTool::class, [
            'route'            => $dungeonRoute->public_key,
            'dungeon_start_id' => $foreignStartId,
            'title'            => 'Not saved',
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.unknown_dungeon_start', ['start' => $foreignStartId])]);
        $this->assertNotSame('Not saved', $dungeonRoute->fresh()->title);
    }

    #[Test]
    public function updateRouteSettings_givenSomeoneElsesRoute_returnsRouteNotFound(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $dungeonRoute               = $this->createDungeonRoute($this->createUser(), $dungeon, $mappingVersion);
        $originalTitle              = $dungeonRoute->title;
        $this->actingAsMcpUser($this->createUser());

        // Act
        $response = KeystoneGuruServer::tool(UpdateRouteSettingsTool::class, [
            'route' => $dungeonRoute->public_key,
            'title' => 'Hijacked',
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.route_not_found', ['route' => $dungeonRoute->public_key])]);
        $this->assertSame($originalTitle, $dungeonRoute->fresh()->title);
    }

    #[Test]
    public function getRouteOptions_givenDungeon_listsClassesSpellsAndDungeonStarts(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(
            challengeMode: true,
            dungeonActive: true,
            constraint: static fn($query) => $query->whereHas('expansion', static fn($expansion) => $expansion->where('active', true)),
            resolve: static fn($dungeon, $mappingVersion) => $mappingVersion->dungeonStarts()->exists() ? true : null,
        );
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $selectableSpellIds = Spell::query()->where('selectable', true)->pluck('id')->map(static fn($id) => (int)$id)->sort()->values()->all();

        // Act
        $response = KeystoneGuruServer::tool(GetRouteOptionsTool::class, ['dungeon' => $dungeon->key]);

        // Assert
        $response->assertHasNoErrors();
        $content = $this->structuredContent($response);
        $this->assertCount(CharacterClass::query()->count(), $content['classes']);
        $spellIds = array_column($content['pull_spells'], 'id');
        sort($spellIds);
        $this->assertSame($selectableSpellIds, $spellIds);
        $this->assertEqualsCanonicalizing(
            $mappingVersion->dungeonStarts()->pluck('id')->map(static fn($id) => (int)$id)->all(),
            array_column($content['dungeon_starts'], 'id'),
        );
    }

    private function deletePartyRows(int $dungeonRouteId): void
    {
        DungeonRoutePlayerClass::query()->where('dungeon_route_id', $dungeonRouteId)->delete();
        DungeonRoutePlayerSpecialization::query()->where('dungeon_route_id', $dungeonRouteId)->delete();
        DungeonRoutePlayerRace::query()->where('dungeon_route_id', $dungeonRouteId)->delete();
    }
}
