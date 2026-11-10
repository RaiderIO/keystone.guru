<?php

namespace App\Service\OpenWorldNpc;

use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcFile;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcFileEntry;
use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcFileException;
use JsonException;

/**
 * Reads the assumed format: a flat JSON array of `{"npcId": 448, "name": "Hogger"}` objects. Raider.IO has not
 * published the file yet, so this is the one place to change once the real format is known.
 */
class OpenWorldNpcFileReader implements OpenWorldNpcFileReaderInterface
{
    private const string KEY_NPC_ID = 'npcId';
    private const string KEY_NAME   = 'name';

    /** The bounds of the open_world_npcs columns the entries end up in. */
    private const int MAX_NPC_ID      = 4_294_967_295;
    private const int MAX_NAME_LENGTH = 255;

    public function read(string $contents): OpenWorldNpcFile
    {
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new InvalidOpenWorldNpcFileException(sprintf('The file is not valid JSON: %s', $jsonException->getMessage()), 0, $jsonException);
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new InvalidOpenWorldNpcFileException('The file is not a JSON array');
        }

        $entries           = collect();
        $invalidEntryCount = 0;
        foreach ($decoded as $rawEntry) {
            $entry = $this->readEntry($rawEntry);

            if ($entry === null) {
                $invalidEntryCount++;
            } elseif (!$entries->has($entry->getNpcId())) {
                $entries->put($entry->getNpcId(), $entry);
            }
        }

        // A file in which nothing is readable has most likely changed format, which must not pass as an empty day
        if ($decoded !== [] && $entries->isEmpty()) {
            throw new InvalidOpenWorldNpcFileException(sprintf('None of the %d entries in the file could be read', count($decoded)));
        }

        return new OpenWorldNpcFile($entries, $invalidEntryCount);
    }

    private function readEntry(mixed $rawEntry): ?OpenWorldNpcFileEntry
    {
        if (!is_array($rawEntry)) {
            return null;
        }

        $rawNpcId = $rawEntry[self::KEY_NPC_ID] ?? null;
        $name     = $rawEntry[self::KEY_NAME] ?? null;

        // filter_var() alone would read `true` as 1
        $npcId = is_int($rawNpcId) || is_string($rawNpcId) ?
            filter_var($rawNpcId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::MAX_NPC_ID]]) :
            false;

        if ($npcId === false || !is_string($name) || trim($name) === '') {
            return null;
        }

        return new OpenWorldNpcFileEntry($npcId, mb_substr(trim($name), 0, self::MAX_NAME_LENGTH));
    }
}
