<?php

namespace App\Service\Spell;

interface SpellServiceInterface
{
    public function importFromCsv(string $filePath): bool;

    /**
     * @return int[]
     */
    /**

     * @return array<int, mixed>
     */

    public function getMissingSpellIds(): array;
}
