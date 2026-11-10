<?php

namespace Tests\Unit\App\Service\OpenWorldNpc;

use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcFileEntry;
use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcFileException;
use App\Service\OpenWorldNpc\OpenWorldNpcFileReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('OpenWorldNpc')]
final class OpenWorldNpcFileReaderTest extends TestCase
{
    #[Test]
    public function read_givenArrayOfNpcs_returnsAnEntryPerNpcKeyedById(): void
    {
        // Arrange
        $reader = new OpenWorldNpcFileReader();

        // Act
        $file = $reader->read('[{"npcId": 448, "name": "Hogger"}, {"npcId": "3", "name": " Flesh Eating Worm "}]');

        // Assert
        $this->assertSame([448 => 'Hogger', 3 => 'Flesh Eating Worm'], $file->getEntries()->map(static fn(OpenWorldNpcFileEntry $entry) => $entry->getName())->all());
        $this->assertSame(448, $file->getEntries()->get(448)?->getNpcId());
        $this->assertSame(0, $file->getInvalidEntryCount());
    }

    #[Test]
    public function read_givenEmptyArray_returnsNoEntries(): void
    {
        // Arrange
        $reader = new OpenWorldNpcFileReader();

        // Act
        $file = $reader->read('[]');

        // Assert
        $this->assertTrue($file->getEntries()->isEmpty());
        $this->assertSame(0, $file->getInvalidEntryCount());
    }

    #[Test]
    public function read_givenTheSameNpcTwice_keepsTheFirstEntry(): void
    {
        // Arrange
        $reader = new OpenWorldNpcFileReader();

        // Act
        $file = $reader->read('[{"npcId": 448, "name": "Hogger"}, {"npcId": 448, "name": "Not Hogger"}]');

        // Assert
        $this->assertCount(1, $file->getEntries());
        $this->assertSame('Hogger', $file->getEntries()->get(448)?->getName());
    }

    #[Test]
    #[DataProvider('invalidEntryProvider')]
    public function read_givenAnInvalidEntryNextToAValidOne_skipsAndCountsTheInvalidEntry(string $invalidEntry): void
    {
        // Arrange
        $reader = new OpenWorldNpcFileReader();

        // Act
        $file = $reader->read(sprintf('[{"npcId": 448, "name": "Hogger"}, %s]', $invalidEntry));

        // Assert
        $this->assertSame([448], $file->getEntries()->keys()->all());
        $this->assertSame(1, $file->getInvalidEntryCount());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidEntryProvider(): array
    {
        return [
            'not an object'       => ['448'],
            'missing npc id'      => ['{"name": "Hogger"}'],
            'missing name'        => ['{"npcId": 449}'],
            'empty name'          => ['{"npcId": 449, "name": "  "}'],
            'name not a string'   => ['{"npcId": 449, "name": 12}'],
            'npc id zero'         => ['{"npcId": 0, "name": "Hogger"}'],
            'npc id negative'     => ['{"npcId": -5, "name": "Hogger"}'],
            'npc id fractional'   => ['{"npcId": 449.5, "name": "Hogger"}'],
            'npc id boolean'      => ['{"npcId": true, "name": "Hogger"}'],
            'npc id not numeric'  => ['{"npcId": "abc", "name": "Hogger"}'],
            'npc id out of range' => ['{"npcId": 4294967296, "name": "Hogger"}'],
        ];
    }

    #[Test]
    #[DataProvider('malformedFileProvider')]
    public function read_givenMalformedFile_throwsInvalidOpenWorldNpcFileException(string $contents): void
    {
        // Arrange
        $reader = new OpenWorldNpcFileReader();

        // Assert
        $this->expectException(InvalidOpenWorldNpcFileException::class);

        // Act
        $reader->read($contents);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedFileProvider(): array
    {
        return [
            'zero bytes'                 => [''],
            'invalid json'               => ['[{"npcId": 448, "name": "Hogger"'],
            'an object, not an array'    => ['{"npcId": 448, "name": "Hogger"}'],
            'an object keyed by npc id'  => ['{"448": {"name": "Hogger"}}'],
            'a scalar'                   => ['448'],
            'an object of valid entries' => ['{"hogger": {"npcId": 448, "name": "Hogger"}}'],
            'no entry readable'          => ['[{"id": 448, "npcName": "Hogger"}]'],
        ];
    }
}
