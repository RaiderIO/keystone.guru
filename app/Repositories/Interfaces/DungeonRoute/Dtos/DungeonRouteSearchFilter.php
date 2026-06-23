<?php

namespace App\Repositories\Interfaces\DungeonRoute\Dtos;

use App\Models\Mapping\MappingVersion;

/**
 * Used as a filter for searching for dungeon routes. It can be used to search for routes with specific criteria, such as title, username, key level, etc.
 */
readonly class DungeonRouteSearchFilter
{
    /**
     * @param array<int, int>|null $includedEnemies
     * @param array<int, int>|null $excludedEnemies
     */
    public function __construct(
        public MappingVersion $mappingVersion,
        public int            $offset = 0,
        public int            $limit = 5,
        public ?string        $title = null,
        public ?string        $username = null,
        public ?int           $minKeyLevel = null,
        public ?int           $maxKeyLevel = null,
        public ?array         $includedEnemies = null,
        public ?array         $excludedEnemies = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        MappingVersion $mappingVersion,
        array          $data,
    ): self {
        return new self(
            mappingVersion: $mappingVersion,
            offset: $data['offset'] ?? 0,
            limit: $data['limit'] ?? 5,
            title: $data['title'] ?? null,
            username: $data['username'] ?? null,
            minKeyLevel: $data['minMythicLevel'] ?? null,
            maxKeyLevel: $data['maxMythicLevel'] ?? null,
            includedEnemies: $data['includedEnemies'] ?? null,
            excludedEnemies: $data['excludedEnemies'] ?? null,
        );
    }
}
