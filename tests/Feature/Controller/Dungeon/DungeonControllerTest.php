<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Dungeon')]
final class DungeonControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    /**
     * @param  list<int>              $difficulties
     * @param  array<string, mixed>   $attributes
     * @return TestResponse<Response>
     */
    private function updateDungeon(Dungeon $dungeon, array $difficulties, array $attributes = []): TestResponse
    {
        return $this->patch(route('admin.dungeon.update', $dungeon), [...[
            'name'                  => __($dungeon->name, [], 'en_US'),
            'abbreviation'          => $dungeon->abbreviation,
            'key'                   => $dungeon->key,
            'slug'                  => $dungeon->slug,
            'zone_id'               => $dungeon->zone_id,
            'map_id'                => $dungeon->map_id,
            'mdt_id'                => $dungeon->mdt_id,
            'speedrun_enabled'      => 1,
            'speedrun_difficulties' => $difficulties,
        ], ...$attributes]);
    }

    #[Test]
    public function update_givenSpeedrunDifficulties_syncsRelation(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalAttributes   = $dungeon->getAttributes();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        $expected = [
            DungeonDifficulty::TEN_MAN->value,
            DungeonDifficulty::TWENTY_FIVE_MAN->value,
        ];

        try {
            // Act
            $response = $this->updateDungeon($dungeon, $expected);

            // Assert
            $response->assertOk();
            $this->assertEqualsCanonicalizing(
                $expected,
                $dungeon->fresh()->getEnabledSpeedrunDifficulties(),
            );
        } finally {
            $this->restoreDungeon($dungeon, $originalAttributes, $originalDifficulties);
        }
    }

    #[Test]
    public function update_givenChangedDifficulties_replacesPreviousDifficulties(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalAttributes   = $dungeon->getAttributes();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        try {
            // Act — first set two difficulties, then re-sync to a single one
            $this->updateDungeon($dungeon, [
                DungeonDifficulty::TEN_MAN->value,
                DungeonDifficulty::TWENTY_FIVE_MAN->value,
            ]);
            $this->updateDungeon($dungeon, [
                DungeonDifficulty::TWENTY_MAN->value,
            ]);

            // Assert — only the last set remains
            $this->assertEqualsCanonicalizing(
                [DungeonDifficulty::TWENTY_MAN->value],
                $dungeon->fresh()->getEnabledSpeedrunDifficulties(),
            );
        } finally {
            $this->restoreDungeon($dungeon, $originalAttributes, $originalDifficulties);
        }
    }

    #[Test]
    public function update_givenInvalidDifficulty_redirectsWithValidationError(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalAttributes   = $dungeon->getAttributes();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        try {
            // Act
            $response = $this->updateDungeon($dungeon, [9999]);

            // Assert
            $response->assertSessionHasErrors('speedrun_difficulties.0');
        } finally {
            $this->restoreDungeon($dungeon, $originalAttributes, $originalDifficulties);
        }
    }

    #[Test]
    public function update_givenSuggestedLevels_savesThem(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalAttributes   = $dungeon->getAttributes();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        try {
            // Act
            $response = $this->updateDungeon($dungeon, [], [
                'min_suggested_level' => 13,
                'max_suggested_level' => 18,
            ]);

            // Assert
            $response->assertOk();
            $response->assertSessionHasNoErrors();
            $fresh = $dungeon->fresh();
            $this->assertSame(13, $fresh->min_suggested_level);
            $this->assertSame(18, $fresh->max_suggested_level);
        } finally {
            $this->restoreDungeon($dungeon, $originalAttributes, $originalDifficulties);
        }
    }

    #[Test]
    public function update_givenOnlyMaxSuggestedLevel_savesIt(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalAttributes   = $dungeon->getAttributes();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        try {
            // Act
            $response = $this->updateDungeon($dungeon, [], [
                'min_suggested_level' => '',
                'max_suggested_level' => 60,
            ]);

            // Assert
            $response->assertOk();
            $response->assertSessionHasNoErrors();
            $fresh = $dungeon->fresh();
            $this->assertNull($fresh->min_suggested_level);
            $this->assertSame(60, $fresh->max_suggested_level);
        } finally {
            $this->restoreDungeon($dungeon, $originalAttributes, $originalDifficulties);
        }
    }

    #[Test]
    public function update_givenEmptySuggestedLevels_clearsThem(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalAttributes   = $dungeon->getAttributes();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();
        Dungeon::query()->whereKey($dungeon->id)->update(['min_suggested_level' => 13, 'max_suggested_level' => 18]);

        try {
            // Act
            $response = $this->updateDungeon($dungeon, [], [
                'min_suggested_level' => '',
                'max_suggested_level' => '',
            ]);

            // Assert
            $response->assertOk();
            $fresh = $dungeon->fresh();
            $this->assertNull($fresh->min_suggested_level);
            $this->assertNull($fresh->max_suggested_level);
        } finally {
            $this->restoreDungeon($dungeon, $originalAttributes, $originalDifficulties);
        }
    }

    #[Test]
    public function update_givenMaxSuggestedLevelBelowMin_redirectsWithValidationError(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalAttributes   = $dungeon->getAttributes();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        try {
            // Act
            $response = $this->updateDungeon($dungeon, [], [
                'min_suggested_level' => 18,
                'max_suggested_level' => 13,
            ]);

            // Assert
            $response->assertSessionHasErrors('max_suggested_level');
            $response->assertSessionDoesntHaveErrors('min_suggested_level');
            $this->assertSame($originalAttributes['max_suggested_level'], $dungeon->fresh()->max_suggested_level);
        } finally {
            $this->restoreDungeon($dungeon, $originalAttributes, $originalDifficulties);
        }
    }

    /**
     * The update form posts no `active` (nor the other checkbox columns), so the controller unchecks them on the
     * seeded dungeon; every column is put back, not only the difficulties.
     *
     * @param array<string, mixed> $attributes
     * @param list<int>            $difficulties
     */
    private function restoreDungeon(Dungeon $dungeon, array $attributes, array $difficulties): void
    {
        Dungeon::query()->whereKey($dungeon->id)->update($attributes);

        $dungeon->dungeonSpeedrunDifficulties()->delete();
        foreach ($difficulties as $difficulty) {
            $dungeon->dungeonSpeedrunDifficulties()->create(['difficulty' => $difficulty]);
        }
    }
}
