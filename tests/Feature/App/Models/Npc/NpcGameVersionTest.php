<?php

namespace Tests\Feature\App\Models\Npc;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcType;
use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3987: an NPC id can be shared across game versions - Naxxramas exists in both Classic and Wrath - so
 * `npcs.game_version_id` cannot be the answer to "which Wowhead database holds this NPC". The mapping
 * version being viewed decides whenever its game version is one the NPC actually appears in.
 */
#[Group('Npc')]
final class NpcGameVersionTest extends PublicTestCase
{
    private const int TEST_NPC_ID = 900010;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Npc/NpcDungeon use the SeederModel trait, which blocks delete() for non-admins.
        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function getGameVersionId_givenViewedMappingVersionAmongCandidates_returnsThatMappingVersionsGameVersion(): void
    {
        // Arrange
        $retailDungeon = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $wrathDungeon  = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_WRATH);

        $npc = null;

        try {
            $npc = $this->createNpcInDungeons(
                [$retailDungeon, $wrathDungeon],
                GameVersion::ALL[GameVersion::GAME_VERSION_WRATH],
            );

            // Act
            $gameVersionId = $npc->getGameVersionId($this->getMappingVersion($retailDungeon));

            // Assert
            $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL], $gameVersionId);
        } finally {
            $this->cleanUpNpc($npc);
        }
    }

    #[Test]
    public function getGameVersionId_givenNpcInSeveralGameVersions_returnsTheViewedOneForEachOfThem(): void
    {
        // Arrange - the Naxxramas case: the same NPC id is mapped in both a Classic and a Wrath dungeon
        $classicDungeon = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $wrathDungeon   = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_WRATH);

        $npc = null;

        try {
            $npc = $this->createNpcInDungeons(
                [$classicDungeon, $wrathDungeon],
                GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            );

            // Act
            $classicGameVersionId = $npc->getGameVersionId($this->getMappingVersion($classicDungeon));
            $wrathGameVersionId   = $npc->getGameVersionId($this->getMappingVersion($wrathDungeon));

            // Assert
            $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA], $classicGameVersionId);
            $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_WRATH], $wrathGameVersionId);
        } finally {
            $this->cleanUpNpc($npc);
        }
    }

    #[Test]
    public function getGameVersionId_givenViewedMappingVersionNotAmongCandidates_returnsTheStoredColumn(): void
    {
        // Arrange
        $classicDungeon = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $mopDungeon     = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_MOP);

        $npc = null;

        try {
            $npc = $this->createNpcInDungeons(
                [$classicDungeon],
                GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            );

            // Act
            $gameVersionId = $npc->getGameVersionId($this->getMappingVersion($mopDungeon));

            // Assert
            $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA], $gameVersionId);
        } finally {
            $this->cleanUpNpc($npc);
        }
    }

    #[Test]
    public function getGameVersionId_givenNoMappingVersionInScope_returnsTheStoredColumn(): void
    {
        // Arrange
        $classicDungeon = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);

        $npc = null;

        try {
            $npc = $this->createNpcInDungeons(
                [$classicDungeon],
                GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            );

            // Act
            $gameVersionId = $npc->getGameVersionId();

            // Assert
            $this->assertSame(GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA], $gameVersionId);
        } finally {
            $this->cleanUpNpc($npc);
        }
    }

    #[Test]
    public function getWowheadUrl_givenViewedMappingVersion_linksToThatGameVersionsWowheadDatabase(): void
    {
        // Arrange
        $classicDungeon = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $wrathDungeon   = $this->getDungeonForGameVersion(GameVersion::GAME_VERSION_WRATH);

        $npc = null;

        try {
            $npc = $this->createNpcInDungeons(
                [$classicDungeon, $wrathDungeon],
                GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            );

            // Act
            $wowheadUrl = $npc->getWowheadUrl($this->getMappingVersion($wrathDungeon));

            // Assert
            $this->assertStringStartsWith(
                sprintf('https://www.wowhead.com/wrath/npc=%d', self::TEST_NPC_ID),
                $wowheadUrl,
            );
        } finally {
            $this->cleanUpNpc($npc);
        }
    }

    /**
     * A dungeon whose mapping versions belong to exactly one game version, so it cannot make a
     * candidate list ambiguous.
     */
    private function getDungeonForGameVersion(string $gameVersionKey): Dungeon
    {
        /** @var Collection<int, Dungeon> $dungeons */
        $dungeons = Dungeon::query()->with(['mappingVersions'])->get();

        foreach ($dungeons as $dungeon) {
            $gameVersionIds = $dungeon->getMappingVersionGameVersions()->pluck('id')->unique();

            if ($gameVersionIds->count() === 1 && $gameVersionIds->first() === GameVersion::ALL[$gameVersionKey]) {
                return $dungeon;
            }
        }

        $this->fail(sprintf('The seeded DB has no dungeon mapped for game version %s alone.', $gameVersionKey));
    }

    private function getMappingVersion(Dungeon $dungeon): MappingVersion
    {
        return $dungeon->loadMappingVersions()->mappingVersions->firstOrFail();
    }

    /**
     * @param array<int, Dungeon> $dungeons
     */
    private function createNpcInDungeons(array $dungeons, int $storedGameVersionId): Npc
    {
        $npc = Npc::create([
            'id'                => self::TEST_NPC_ID,
            'game_version_id'   => $storedGameVersionId,
            'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
            'npc_type_id'       => NpcType::HUMANOID,
            'name'              => 'Test NPC for #3987 game version resolution',
            'aggressiveness'    => 'aggressive',
        ]);

        foreach ($dungeons as $dungeon) {
            NpcDungeon::create([
                'npc_id'     => $npc->id,
                'dungeon_id' => $dungeon->id,
            ]);
        }

        new Npc()->flushCache();
        new NpcDungeon()->flushCache();

        // The npc_dungeons rows must be visible to the relation, and getCandidateGameVersionIds()
        // memoizes per instance
        return Npc::query()->findOrFail(self::TEST_NPC_ID);
    }

    private function cleanUpNpc(?Npc $npc): void
    {
        NpcDungeon::query()->where('npc_id', self::TEST_NPC_ID)->delete();
        $npc?->delete();

        new Npc()->flushCache();
        new NpcDungeon()->flushCache();
    }
}
