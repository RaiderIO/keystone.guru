<?php

namespace Tests\Feature\App\Models;

use App\Models\PublishedState;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('PublishedState')]
final class PublishedStateTest extends PublicTestCase
{
    #[Test]
    public function PublishedState_givenSeededRows_haveTranslationKeyName(): void
    {
        // Arrange
        $expectedKeysById = array_flip(PublishedState::ALL);

        // Act
        $rows = PublishedState::query()->orderBy('id')->get(['id', 'key', 'name']);

        // Assert
        $this->assertCount(count($expectedKeysById), $rows);
        foreach ($rows as $row) {
            $this->assertSame($expectedKeysById[$row->id], $row->key, sprintf('%s %d has the wrong key', PublishedState::class, $row->id));
            $this->assertSame(sprintf('publishedstates.%s', $row->key), $row->name, sprintf('%s %d has the wrong translation key as its name', PublishedState::class, $row->id));
            $this->assertNotSame($row->name, __($row->name), sprintf('%s %d has a name that does not translate', PublishedState::class, $row->id));
        }
    }
}
