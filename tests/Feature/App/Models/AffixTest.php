<?php

namespace Tests\Feature\App\Models;

use App\Models\Affix;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Affix')]
final class AffixTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Affix uses the SeederModel trait, which blocks delete() for non-admins - authenticate as
        // admin so this test's own cleanup in `finally` actually removes the row.
        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function create_givenExplicitId_persistsExplicitId(): void
    {
        // Arrange - mirrors the exact attribute shape AffixController::store() passes to
        // Affix::create() when creating a new affix through the admin panel: an explicit id (a
        // bulk query-builder insert() would not reflect an explicit AUTO_INCREMENT value through
        // LAST_INSERT_ID(), but Eloquent's create() - which internally calls save() - does).
        $affix = null;

        try {
            // Act
            $affix = Affix::create([
                'id'          => 90000,
                'affix_id'    => 90000,
                'key'         => 'TestAffixExplicitId',
                'name'        => 'Test Affix Explicit Id',
                'description' => 'Test affix for explicit id regression coverage.',
            ]);

            // Assert
            $this->assertSame(90000, $affix->id);
            $this->assertSame(90000, $affix->fresh()->id);
        } finally {
            $affix?->delete();
        }
    }
}
