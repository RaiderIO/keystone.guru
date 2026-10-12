<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\Dungeon;
use App\Models\MDTImport;
use App\Models\Npc\NpcType;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsMdtControllerTest extends PublicTestCase
{
    use CreatesNpc;

    private const int ADMIN_USER_ID = 1;

    #[Test]
    public function mdtImportList_givenFailedAndSuccessfulImports_listsOnlyTheFailedOnes(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $failed     = null;
        $successful = null;

        try {
            $failed     = MDTImport::create(['error' => 'Test failure', 'import_string' => 'failed-import-test']);
            $successful = MDTImport::create(['error' => null, 'import_string' => 'successful-import-test']);

            // Act
            $response = $this->get(route('admin.tools.mdt.string.list'));

            // Assert
            $response->assertOk();
            $response->assertViewHas('mdtImports', static fn($mdtImports): bool => $mdtImports->contains('id', $failed->id)
                && !$mdtImports->contains('id', $successful->id));
        } finally {
            $failed?->delete();
            $successful?->delete();
        }
    }

    #[Test]
    public function dungeonmappingversiontomdtmapping_givenAdmin_listsMappingVersionsGroupedByDungeonName(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.mdt.dungeonmappingversiontomdtmapping'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('mappingVersionsSelect', static fn($select): bool => $select->isNotEmpty()
            && $select->first()->isNotEmpty());
    }

    #[Test]
    public function dungeonmappingversiontomdtmappingsubmit_givenUnknownMappingVersion_returnsNotFound(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->post(route('admin.tools.mdt.dungeonmappingversiontomdtmapping.submit'), ['mapping_version_id' => 0]);

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function mdtdungeonmappinghashsubmit_givenUnknownDungeon_returnsNotFound(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->post(route('admin.tools.mdt.dungeonmappinghash.submit'), ['dungeon_id' => 0]);

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function mdtviewasstringsubmit_givenUnknownPublicKey_returnsNotFound(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->post(route('admin.tools.mdt.dungeonroute.viewasstring.submit'), ['public_key' => 'zzzzzzzz']);

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function applyChange_givenMismatchedEnemyType_updatesTheNpcType(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $npc  = $this->createNpcInDatabase(['npc_type_id' => NpcType::BEAST]);
        $type = NpcType::findOrFail(NpcType::UNDEAD);

        // Act
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->postJson('/ajax/tools/mdt/diff/apply', [
            'category'   => 'mismatched_enemy_type',
            'npc_id'     => $npc->id,
            'dungeon_id' => Dungeon::firstOrFail()->id,
            'value'      => $type->type,
        ]);

        // Assert
        $response->assertOk();
        $this->assertSame(NpcType::UNDEAD, $npc->fresh()->npc_type_id);
    }

    #[Test]
    public function applyChange_givenUnknownCategory_returnsServerError(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));
        $npc = $this->createNpcInDatabase();

        // Act
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->postJson('/ajax/tools/mdt/diff/apply', [
            'category'   => 'unknown_category',
            'npc_id'     => $npc->id,
            'dungeon_id' => Dungeon::firstOrFail()->id,
            'value'      => 1,
        ]);

        // Assert
        $response->assertStatus(500);
    }
}
