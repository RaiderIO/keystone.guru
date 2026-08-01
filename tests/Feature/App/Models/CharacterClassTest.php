<?php

namespace Tests\Feature\App\Models;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CharacterClass')]
final class CharacterClassTest extends PublicTestCase
{
    #[Test]
    public function toArray_givenSeededCharacterClass_containsIconUrlAndOmitsIconFile(): void
    {
        // Arrange
        $characterClass = CharacterClass::query()->firstOrFail();

        // Act
        $array = $characterClass->toArray();

        // Assert - the icon comes from the assets project, never from a File upload.
        $this->assertArrayHasKey('icon_url', $array);
        $this->assertArrayNotHasKey('iconfile', $array);
        $this->assertArrayNotHasKey('icon_file_id', $array);
    }

    #[Test]
    public function iconUrl_givenSeededCharacterClassSpecialization_returnsAssetsImageUrl(): void
    {
        // Arrange
        $specialization = CharacterClassSpecialization::query()->firstOrFail();
        $className      = str_replace('_', '', $specialization->class->key);

        // Act
        $iconUrl = $specialization->icon_url;

        // Assert
        $this->assertSame(
            ksgAssetImage(sprintf(
                '/specializations/%s/%s_%s.png',
                $className,
                $className,
                str_replace('_', '', $specialization->key),
            )),
            $iconUrl,
        );
        $this->assertArrayNotHasKey('iconfile', $specialization->toArray());
    }
}
