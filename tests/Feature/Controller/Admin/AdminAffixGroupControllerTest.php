<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupCoupling;
use App\Models\Expansion;
use App\Models\Season;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class AdminAffixGroupControllerTest extends PublicTestCase
{
    private Season $season;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));

        $this->season = Season::create([
            'expansion_id'            => Expansion::query()->firstOrFail()->id,
            'index'                   => 996,
            'start'                   => '2026-01-01 00:00:00',
            'presets'                 => 0,
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->season->affixGroups as $affixGroup) {
            $affixGroup->syncAffixGroupCouplings([]);
            $affixGroup->delete();
        }
        $this->season->delete();

        parent::tearDown();
    }

    #[Test]
    public function create_givenSeason_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.affixgroup.new', ['season' => $this->season]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function edit_givenAffixGroupNotBelongingToSeason_returnsNotFound(): void
    {
        // Arrange
        $otherSeason = Season::create([
            'expansion_id'            => Expansion::query()->firstOrFail()->id,
            'index'                   => 995,
            'start'                   => '2026-01-02 00:00:00',
            'presets'                 => 0,
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);

        $affixGroup = AffixGroup::create([
            'season_id'      => $this->season->id,
            'expansion_id'   => $this->season->expansion_id,
            'seasonal_index' => null,
            'confirmed'      => 0,
        ]);

        try {
            // Act
            $response = $this->get(route('admin.affixgroup.edit', ['season' => $otherSeason, 'affixGroup' => $affixGroup]));

            // Assert
            $response->assertNotFound();
        } finally {
            $affixGroup->delete();
            $otherSeason->delete();
        }
    }

    #[Test]
    public function savenew_givenValidData_createsAffixGroupWithCouplingsInSlotOrder(): void
    {
        // Arrange
        $affixes = Affix::query()->orderBy('id')->limit(3)->get();

        // Act
        $response = $this->post(route('admin.affixgroup.savenew', ['season' => $this->season]), [
            'seasonal_index' => 0,
            'confirmed'      => 1,
            'affix_id_1'     => $affixes->get(0)->id,
            'key_level_1'    => 2,
            'affix_id_2'     => $affixes->get(1)->id,
            'key_level_2'    => 4,
            'affix_id_3'     => -1,
            'affix_id_4'     => $affixes->get(2)->id,
            'key_level_4'    => 10,
        ]);

        // Assert
        $affixGroup = $this->season->affixGroups()->first();
        $this->assertNotNull($affixGroup);
        $response->assertRedirect(route('admin.affixgroup.edit', ['season' => $this->season, 'affixGroup' => $affixGroup]));
        $this->assertTrue((bool)$affixGroup->confirmed);
        $this->assertSame($this->season->expansion_id, $affixGroup->expansion_id);

        $couplings = $affixGroup->affixGroupCouplings()->orderBy('id')->get(['affix_id', 'key_level']);
        $this->assertSame(
            [
                ['affix_id' => $affixes->get(0)->id, 'key_level' => 2],
                ['affix_id' => $affixes->get(1)->id, 'key_level' => 4],
                ['affix_id' => $affixes->get(2)->id, 'key_level' => 10],
            ],
            $couplings->map(fn($coupling) => ['affix_id' => $coupling->affix_id, 'key_level' => $coupling->key_level])->toArray(),
        );
    }

    #[Test]
    public function update_givenUncheckedConfirmed_persistsFalse(): void
    {
        // Arrange
        $affix      = Affix::query()->firstOrFail();
        $affixGroup = AffixGroup::create([
            'season_id'      => $this->season->id,
            'expansion_id'   => $this->season->expansion_id,
            'seasonal_index' => null,
            'confirmed'      => 1,
        ]);
        $affixGroup->syncAffixGroupCouplings([['affix_id' => $affix->id, 'key_level' => 2]]);

        // Act - 'confirmed' checkbox omitted, as an unchecked checkbox would submit
        $response = $this->patch(route('admin.affixgroup.update', ['season' => $this->season, 'affixGroup' => $affixGroup]), [
            'affix_id_1'  => $affix->id,
            'key_level_1' => 2,
        ]);

        // Assert
        $response->assertOk();
        $this->assertFalse((bool)$affixGroup->fresh()->confirmed);
    }

    #[Test]
    public function delete_givenExistingAffixGroup_deletesItAndItsCouplings(): void
    {
        // Arrange
        $affix      = Affix::query()->firstOrFail();
        $affixGroup = AffixGroup::create([
            'season_id'      => $this->season->id,
            'expansion_id'   => $this->season->expansion_id,
            'seasonal_index' => null,
            'confirmed'      => 0,
        ]);
        $affixGroup->syncAffixGroupCouplings([['affix_id' => $affix->id, 'key_level' => 2]]);
        $affixGroupId = $affixGroup->id;

        // Act
        $response = $this->delete(route('admin.affixgroup.delete', ['season' => $this->season, 'affixGroup' => $affixGroup]));

        // Assert
        $response->assertRedirect(route('admin.season.edit', $this->season));
        $this->assertFalse(AffixGroup::query()->whereKey($affixGroupId)->exists());
        $this->assertSame(0, AffixGroupCoupling::query()->where('affix_group_id', $affixGroupId)->count());
    }
}
