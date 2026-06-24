<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Dungeon;
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
     * @param  list<int>            $difficulties
     * @return TestResponse<Response>
     */
    private function updateDungeon(Dungeon $dungeon, array $difficulties): TestResponse
    {
        return $this->patch(route('admin.dungeon.update', $dungeon), [
            'name'                  => __($dungeon->name, [], 'en_US'),
            'abbreviation'          => $dungeon->abbreviation,
            'key'                   => $dungeon->key,
            'slug'                  => $dungeon->slug,
            'zone_id'               => $dungeon->zone_id,
            'map_id'                => $dungeon->map_id,
            'mdt_id'                => $dungeon->mdt_id,
            'speedrun_enabled'      => 1,
            'speedrun_difficulties' => $difficulties,
        ]);
    }

    #[Test]
    public function update_givenSpeedrunDifficulties_syncsRelation(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        $expected = [
            Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_10_MAN],
            Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_25_MAN],
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
            $this->restoreDifficulties($dungeon, $originalDifficulties);
        }
    }

    #[Test]
    public function update_givenChangedDifficulties_replacesPreviousDifficulties(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        try {
            // Act — first set two difficulties, then re-sync to a single one
            $this->updateDungeon($dungeon, [
                Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_10_MAN],
                Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_25_MAN],
            ]);
            $this->updateDungeon($dungeon, [
                Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_20_MAN],
            ]);

            // Assert — only the last set remains
            $this->assertEqualsCanonicalizing(
                [Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_20_MAN]],
                $dungeon->fresh()->getEnabledSpeedrunDifficulties(),
            );
        } finally {
            $this->restoreDifficulties($dungeon, $originalDifficulties);
        }
    }

    #[Test]
    public function update_givenInvalidDifficulty_redirectsWithValidationError(): void
    {
        // Arrange
        $dungeon              = Dungeon::firstOrFail();
        $originalDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

        try {
            // Act
            $response = $this->updateDungeon($dungeon, [9999]);

            // Assert
            $response->assertSessionHasErrors('speedrun_difficulties.0');
        } finally {
            $this->restoreDifficulties($dungeon, $originalDifficulties);
        }
    }

    /**
     * @param list<int> $difficulties
     */
    private function restoreDifficulties(Dungeon $dungeon, array $difficulties): void
    {
        $dungeon->dungeonSpeedrunDifficulties()->delete();
        foreach ($difficulties as $difficulty) {
            $dungeon->dungeonSpeedrunDifficulties()->create(['difficulty' => $difficulty]);
        }
    }
}
