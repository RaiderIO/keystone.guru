<?php

namespace App\Service\DungeonRoute\Dtos;

use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Used as a filter for searching for dungeon routes. It can be used to search for routes with specific criteria, such as title, username, key level, etc.
 */
class DungeonRouteSearchFilter implements Arrayable
{
    public function __construct(
        private GameVersion    $gameVersion,
        private MappingVersion $mappingVersion,
        private int            $offset = 0,
        private int            $limit = 5,
        private ?string        $title = null,
        private ?string        $username = null,
        private ?int           $minKeyLevel = null,
        private ?int           $maxKeyLevel = null,
    ) {
    }

    public function toArray()
    {
        // TODO: Implement toArray() method.
    }

    public static function fromArray(
        GameVersion    $gameVersion,
        MappingVersion $mappingVersion,
        array          $data,
    ): self {
        return new self(
            gameVersion: $gameVersion,
            mappingVersion: $mappingVersion,
            offset: $data['offset'] ?? 0,
            limit: $data['limit'] ?? 5,
            title: $data['title'] ?? null,
            username: $data['username'] ?? null,
            minKeyLevel: $data['minKeyLevel'] ?? null,
            maxKeyLevel: $data['maxKeyLevel'] ?? null,
        );
    }
}
