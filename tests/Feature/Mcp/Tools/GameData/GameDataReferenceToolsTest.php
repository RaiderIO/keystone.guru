<?php

namespace Tests\Feature\Mcp\Tools\GameData;

use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\GameData\GetNpcTool;
use App\Mcp\Tools\GameData\ListAffixesTool;
use App\Mcp\Tools\GameData\ListClassSpellsTool;
use App\Mcp\Tools\GameData\ListDungeonNpcsTool;
use App\Mcp\Tools\GameData\ListSeasonsTool;
use App\Models\Affix;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Characteristic;
use App\Models\GameServerRegion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcHealth;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellCategory;
use App\Models\Spell\SpellCooldownSource;
use App\Models\Spell\SpellSpecialization;
use App\Service\Season\SeasonServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Mcp\Server\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Mcp\McpTestCase;
use Tests\Fixtures\Traits\CreatesSpell;

/**
 * The read-only game data tools: class spells, NPCs per dungeon with scaled health, seasons and affixes.
 */
#[Group('Mcp')]
#[Group('McpGameData')]
final class GameDataReferenceToolsTest extends McpTestCase
{
    use CreatesSpell;

    #[Test]
    public function listClassSpells_givenClass_listsOnlyThatClassesNonPvpSpells(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $pvpTalent   = $this->createSpell(['category' => SpellCategory::Mage->translationKey(), 'is_pvp_talent' => true]);
        $expectedIds = Spell::query()
            ->where('category', SpellCategory::Mage->translationKey())
            ->where('is_pvp_talent', false)
            ->pluck('id')
            ->map(static fn($id) => (int)$id)
            ->sort()
            ->values()
            ->all();
        $this->assertNotEmpty($expectedIds, 'The seed has no mage spells');

        // Act
        $response = KeystoneGuruServer::tool(ListClassSpellsTool::class, ['class' => SpellCategory::Mage->value]);

        // Assert
        $response->assertHasNoErrors();
        $ids = array_column($this->structuredContent($response)['spells'], 'id');
        sort($ids);
        $this->assertSame($expectedIds, $ids);
        $this->assertNotContains($pvpTalent->id, $ids);
    }

    #[Test]
    public function listClassSpells_givenSpellWithCooldownAndSpecializations_returnsThem(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $fire  = $this->getSpecialization(CharacterClass::CHARACTER_CLASS_MAGE, 'fire');
        $spell = $this->createSpell([
            'category'        => SpellCategory::Mage->translationKey(),
            'cooldown_ms'     => 120000,
            'charges'         => 1,
            'cooldown_source' => SpellCooldownSource::Db2,
        ]);

        try {
            SpellSpecialization::create(['spell_id' => $spell->id, 'character_class_specialization_id' => $fire->id]);

            // Act
            $response = KeystoneGuruServer::tool(ListClassSpellsTool::class, ['class' => SpellCategory::Mage->value]);

            // Assert
            $response->assertHasNoErrors();
            $formatted = $this->findSpell($response, $spell->id);
            $this->assertSame(120, $formatted['cooldown_seconds']);
            $this->assertSame(1, $formatted['charges']);
            $this->assertSame(SpellCooldownSource::Db2->value, $formatted['cooldown_source']);
            $this->assertSame([['id' => $fire->id, 'class' => CharacterClass::CHARACTER_CLASS_MAGE, 'key' => 'fire']], $formatted['specializations']);
        } finally {
            SpellSpecialization::query()->where('spell_id', $spell->id)->delete();
        }
    }

    #[Test]
    public function listClassSpells_givenSpellWithCharges_returnsTheChargeRecoveryAsItsCooldown(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $spell = $this->createSpell([
            'category'           => SpellCategory::Mage->translationKey(),
            'cooldown_ms'        => 500,
            'charges'            => 2,
            'charge_cooldown_ms' => 14500,
            'cooldown_source'    => SpellCooldownSource::Db2,
        ]);

        // Act
        $response = KeystoneGuruServer::tool(ListClassSpellsTool::class, ['class' => SpellCategory::Mage->value]);

        // Assert
        $response->assertHasNoErrors();
        $formatted = $this->findSpell($response, $spell->id);
        $this->assertSame(14.5, $formatted['cooldown_seconds']);
        $this->assertSame(2, $formatted['charges']);
    }

    #[Test]
    public function listClassSpells_givenSpellWithUnknownCooldown_returnsNullRatherThanLeavingItOut(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $spell = $this->createSpell(['category' => SpellCategory::Mage->translationKey()]);

        // Act
        $response = KeystoneGuruServer::tool(ListClassSpellsTool::class, ['class' => SpellCategory::Mage->value]);

        // Assert
        $response->assertHasNoErrors();
        $formatted = $this->findSpell($response, $spell->id);
        $this->assertSame(
            ['cooldown_seconds' => null, 'charges' => null, 'cooldown_source' => null, 'specializations' => null],
            array_intersect_key($formatted, array_flip(['cooldown_seconds', 'charges', 'cooldown_source', 'specializations'])),
        );
    }

    #[Test]
    public function listClassSpells_givenSpecialization_listsItsSpellsAndThoseWithUnknownSpecializations(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $fire         = $this->getSpecialization(CharacterClass::CHARACTER_CLASS_MAGE, 'fire');
        $frost        = $this->getSpecialization(CharacterClass::CHARACTER_CLASS_MAGE, 'frost');
        $fireSpell    = $this->createSpell(['category' => SpellCategory::Mage->translationKey()]);
        $frostSpell   = $this->createSpell(['category' => SpellCategory::Mage->translationKey()]);
        $unknownSpell = $this->createSpell(['category' => SpellCategory::Mage->translationKey()]);

        try {
            SpellSpecialization::create(['spell_id' => $fireSpell->id, 'character_class_specialization_id' => $fire->id]);
            SpellSpecialization::create(['spell_id' => $frostSpell->id, 'character_class_specialization_id' => $frost->id]);

            // Act
            $response = KeystoneGuruServer::tool(ListClassSpellsTool::class, [
                'class'             => SpellCategory::Mage->value,
                'specialization_id' => $fire->id,
            ]);

            // Assert
            $response->assertHasNoErrors();
            $ids = array_column($this->structuredContent($response)['spells'], 'id');
            $this->assertContains($fireSpell->id, $ids);
            $this->assertContains($unknownSpell->id, $ids);
            $this->assertNotContains($frostSpell->id, $ids);
        } finally {
            SpellSpecialization::query()->whereIn('spell_id', [$fireSpell->id, $frostSpell->id])->delete();
        }
    }

    #[Test]
    public function listClassSpells_givenSpecializationOfAnotherClass_returnsAnError(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $restoration = $this->getSpecialization(CharacterClass::CHARACTER_CLASS_SHAMAN, 'restoration');

        // Act
        $response = KeystoneGuruServer::tool(ListClassSpellsTool::class, [
            'class'             => SpellCategory::Mage->value,
            'specialization_id' => $restoration->id,
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.specialization_not_of_spell_class', [
            'specialization' => $restoration->id,
            'class'          => SpellCategory::Mage->value,
        ])]);
    }

    #[Test]
    public function listDungeonNpcs_givenKeyLevel_returnsEveryNpcWithScaledHealth(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $npcIds = $this->getNpcIds($mappingVersion);
        /** @var Npc $npc */
        // A trash NPC at a key level where Fortified applies only when it is the week's affix
        $npc = Npc::query()
            ->with('npcHealths')
            ->whereIn('id', $npcIds)
            ->whereHas('npcHealths')
            ->where('classification_id', '<', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
            ->orderBy('id')
            ->firstOrFail();
        $expectedHealth    = (int)$npc->calculateHealthForKey($mappingVersion->gameVersion, 8, [Affix::AFFIX_FORTIFIED]);
        $unfortifiedHealth = (int)$npc->calculateHealthForKey($mappingVersion->gameVersion, 8);
        $this->assertNotSame($unfortifiedHealth, $expectedHealth, 'Fortified must change this NPC\'s health at this key level');
        // npc_characteristics is filled from combat logs, so the seed holds none: give the NPC one of its own
        $npcCharacteristic = NpcCharacteristic::create(['npc_id' => $npc->id, 'characteristic_id' => Characteristic::query()->orderBy('id')->firstOrFail()->id]);

        try {
            // Act
            $response = KeystoneGuruServer::tool(ListDungeonNpcsTool::class, [
                'dungeon'   => $dungeon->key,
                'key_level' => 8,
                'affixes'   => [Affix::AFFIX_FORTIFIED],
                'page'      => intdiv($npcIds->search($npc->id), (int)config('keystoneguru.api.mcp.npcs_per_page')) + 1,
            ]);

            // Assert
            $response->assertHasNoErrors();
            $content = $this->structuredContent($response);
            $this->assertSame($npcIds->count(), $content['total']);
            /** @var array<int, array<string, mixed>> $npcs */
            $npcs      = $content['npcs'];
            $npcOutput = collect($npcs)->firstWhere('id', $npc->id);
            $this->assertNotNull($npcOutput);
            $this->assertSame($expectedHealth, $npcOutput['health_at_key_level']);
            $this->assertGreaterThan($npcOutput['base_health'], $npcOutput['health_at_key_level']);
            $this->assertSame($mappingVersion->enemies()->where('npc_id', $npc->id)->count(), $npcOutput['enemy_count']);
            $this->assertArrayNotHasKey('characteristics', $npcOutput);
        } finally {
            $npcCharacteristic->delete();
        }
    }

    #[Test]
    public function getNpc_givenPlaceholderHealth_returnsNoHealth(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $npcId     = $this->getNpcIds($mappingVersion)->first();
        $npcHealth = NpcHealth::query()->where('npc_id', $npcId)->where('game_version_id', $mappingVersion->game_version_id)->first();
        $original  = $npcHealth?->only(['health', 'percentage']);

        try {
            if ($npcHealth === null) {
                $npcHealth = NpcHealth::create(['npc_id' => $npcId, 'game_version_id' => $mappingVersion->game_version_id, 'health' => NpcHealth::HEALTH_PLACEHOLDER]);
            } else {
                $npcHealth->update(['health' => NpcHealth::HEALTH_PLACEHOLDER, 'percentage' => null]);
            }

            // Act
            $response = KeystoneGuruServer::tool(GetNpcTool::class, ['npc_id' => $npcId, 'dungeon' => $dungeon->key, 'key_level' => 10]);

            // Assert
            $response->assertHasNoErrors();
            $content = $this->structuredContent($response);
            $this->assertSame($npcId, $content['id']);
            $this->assertArrayNotHasKey('base_health', $content);
            $this->assertArrayNotHasKey('health_at_key_level', $content);
        } finally {
            if ($original === null) {
                $npcHealth?->delete();
            } else {
                $npcHealth->update($original);
            }
        }
    }

    #[Test]
    public function listDungeonNpcs_givenSecondPage_returnsTheNextNpcs(): void
    {
        // Arrange
        config(['keystoneguru.api.mcp.npcs_per_page' => 2]);
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $npcIds = $this->getNpcIds($mappingVersion);
        $this->assertGreaterThan(2, $npcIds->count(), 'The picked dungeon has too few NPCs for a second page');

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonNpcsTool::class, ['dungeon' => $dungeon->key, 'page' => 2]);

        // Assert
        $response->assertHasNoErrors();
        $this->assertSame($npcIds->slice(2, 2)->values()->all(), array_column($this->structuredContent($response)['npcs'], 'id'));
    }

    #[Test]
    public function getNpc_givenNpcWithSpellsAndCharacteristics_returnsItsEnemyForcesWithoutEither(): void
    {
        // Arrange - npc_spells and npc_characteristics are filled from combat logs, so the seed holds none
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        /** @var NpcEnemyForces $npcEnemyForces */
        $npcEnemyForces = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereIn('npc_id', $this->getNpcIds($mappingVersion))
            ->where('enemy_forces', '>', 0)
            ->orderBy('npc_id')
            ->firstOrFail();
        $npcId               = $npcEnemyForces->npc_id;
        $expectedEnemyForces = $npcEnemyForces->enemy_forces;
        $spells              = Spell::query()->orderBy('id')->limit(2)->get();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $npcSpells         = [];
        $npcCharacteristic = null;

        try {
            foreach ($spells as $spell) {
                $npcSpells[] = NpcSpell::create(['npc_id' => $npcId, 'spell_id' => $spell->id]);
            }
            $npcCharacteristic = NpcCharacteristic::create(['npc_id' => $npcId, 'characteristic_id' => Characteristic::query()->orderBy('id')->firstOrFail()->id]);

            // Act
            $response = KeystoneGuruServer::tool(GetNpcTool::class, ['npc_id' => $npcId, 'dungeon' => $dungeon->key]);

            // Assert
            $response->assertHasNoErrors();
            $content = $this->structuredContent($response);
            $this->assertSame($npcId, $content['id']);
            $this->assertSame($expectedEnemyForces, $content['enemy_forces']);
            $this->assertArrayNotHasKey('spells', $content);
            $this->assertArrayNotHasKey('characteristics', $content);
        } finally {
            foreach ($npcSpells as $npcSpell) {
                $npcSpell->delete();
            }
            $npcCharacteristic?->delete();
        }
    }

    #[Test]
    public function getNpc_givenUnknownNpc_returnsNpcNotFound(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $unknownId = (int)Npc::query()->max('id') + 1000;

        // Act
        $response = KeystoneGuruServer::tool(GetNpcTool::class, ['npc_id' => $unknownId]);

        // Assert
        $response->assertHasErrors([__('mcp.error.npc_not_found', ['npc' => $unknownId])]);
    }

    #[Test]
    public function listSeasons_givenRequest_marksTheCurrentSeason(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $currentSeason = app(SeasonServiceInterface::class)->getCurrentSeason();
        $this->assertNotNull($currentSeason, 'The seed has no current season');

        // Act
        $response = KeystoneGuruServer::tool(ListSeasonsTool::class);

        // Assert
        $response->assertHasNoErrors();
        /** @var array<int, array<string, mixed>> $seasonRows */
        $seasonRows = $this->structuredContent($response)['seasons'];
        $seasons    = collect($seasonRows);
        $this->assertSame([$currentSeason->id], $seasons->where('is_current', true)->pluck('id')->values()->all());
        $this->assertSame(
            $currentSeason->dungeons()->pluck('key')->all(),
            $seasons->firstWhere('id', $currentSeason->id)['dungeons'],
        );
        $this->assertSame(
            $currentSeason->start(GameServerRegion::getUserOrDefaultRegion())->toIso8601String(),
            $seasons->firstWhere('id', $currentSeason->id)['start'],
        );
    }

    #[Test]
    public function listAffixes_givenRequest_listsEveryAffix(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);

        // Act
        $response = KeystoneGuruServer::tool(ListAffixesTool::class);

        // Assert
        $response->assertHasNoErrors();
        $this->assertSame(Affix::query()->count(), count($this->structuredContent($response)['affixes']));
        $this->assertContains(Affix::AFFIX_FORTIFIED, array_column($this->structuredContent($response)['affixes'], 'key'));
    }

    /**
     * @return Collection<int, int> the mapping version's NPC ids, ascending
     */
    private function getNpcIds(MappingVersion $mappingVersion): Collection
    {
        return $mappingVersion->enemies()
            ->whereNotNull('npc_id')
            ->pluck('npc_id')
            ->map(static fn($id) => (int)$id)
            ->unique()
            ->sort()
            ->values();
    }

    private function getSpecialization(string $classKey, string $specializationKey): CharacterClassSpecialization
    {
        return CharacterClassSpecialization::query()
            ->whereHas('class', static fn(Builder $query) => $query->where('key', $classKey))
            ->where('key', $specializationKey)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function findSpell(TestResponse $response, int $spellId): array
    {
        $spells = array_values(array_filter(
            $this->structuredContent($response)['spells'],
            static fn(array $spell) => $spell['id'] === $spellId,
        ));
        $this->assertCount(1, $spells, sprintf('Spell %d is not listed', $spellId));

        return $spells[0];
    }
}
