<?php

namespace App\Service\OpenWorldNpc\Dtos;

/**
 * One NPC as Raider.IO's deadly-NPC list describes it.
 */
class OpenWorldNpcFileEntry
{
    public function __construct(
        private readonly int    $npcId,
        private readonly string $name,
    ) {
    }

    public function getNpcId(): int
    {
        return $this->npcId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
