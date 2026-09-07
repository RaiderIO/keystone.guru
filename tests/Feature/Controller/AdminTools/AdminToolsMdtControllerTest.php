<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\MDTImport;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsMdtControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function mdtImportList_givenImportStringContainingMarkup_returnsTheStringEscaped(): void
    {
        // Arrange - the import string is stored exactly as it was posted, before any decoding, so
        // the list must not render it as markup.
        $mdtImport = null;

        try {
            $mdtImport = MDTImport::create([
                'dungeon_route_id' => null,
                'error'            => 'Test error',
                'import_string'    => '<b>bold</b>',
            ]);

            // Act
            $response = $this->get(route('admin.tools.mdt.string.list'));

            // Assert
            $response->assertOk();
            $response->assertSee('&lt;b&gt;bold&lt;/b&gt;', false);
            $response->assertDontSee('<b>bold</b>', false);
        } finally {
            $mdtImport?->delete();
        }
    }
}
