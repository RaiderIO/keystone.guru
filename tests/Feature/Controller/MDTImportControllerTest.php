<?php

namespace Tests\Feature\Controller;

use App\Http\Requests\MDT\ImportStringFormRequest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MDT')]
#[Group('MDTImportController')]
final class MDTImportControllerTest extends PublicTestCase
{
    #[Test]
    public function import_givenImportStringOverSizeLimit_returnsUnprocessableWithFieldError(): void
    {
        // Arrange
        $importString = str_repeat('a', ImportStringFormRequest::IMPORT_STRING_MAX_LENGTH + 1);

        // Act
        $response = $this->postJson(route('dungeonroute.new.mdtimport'), [
            'import_string' => $importString,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['import_string']);
    }

    #[Test]
    public function import_givenImportStringAtSizeLimit_passesValidation(): void
    {
        // Arrange - a string of the maximum length that is not an MDT string: it must get past
        // validation and be rejected by the import itself instead
        $importString = str_repeat('a', ImportStringFormRequest::IMPORT_STRING_MAX_LENGTH);

        // Act
        $response = $this->postJson(route('dungeonroute.new.mdtimport'), [
            'import_string' => $importString,
        ]);

        // Assert
        $response->assertBadRequest();
    }
}
