<?php

namespace Tests\Feature\App\Models;

use App\Models\Expansion;
use App\Models\Season;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Season')]
final class SeasonTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Season uses the SeederModel trait, which blocks delete() for non-admins - authenticate
        // as admin so this test's own cleanup in `finally` actually removes the row.
        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function save_givenExplicitIdOnNewModel_persistsTheExplicitId(): void
    {
        // Arrange - the admin panel assigns a season's id explicitly (see SeasonController::store())
        // rather than letting it auto-increment. A bulk query-builder insert() would not reflect an
        // explicit AUTO_INCREMENT value through LAST_INSERT_ID(), but a normal Eloquent save() does.
        $season = new Season([
            'expansion_id'            => Expansion::query()->firstOrFail()->id,
            'index'                   => 1,
            'start'                   => '2026-01-01 00:00:00',
            'presets'                 => 0,
            'affix_group_count'       => 4,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 30,
        ]);
        $season->id = 90000;

        try {
            // Act
            $season->save();

            // Assert
            $this->assertSame(90000, $season->id);
            $this->assertSame(90000, $season->fresh()->id);
        } finally {
            $season->delete();
        }
    }
}
