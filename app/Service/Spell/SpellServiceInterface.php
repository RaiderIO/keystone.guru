<?php

namespace App\Service\Spell;

interface SpellServiceInterface
{
    public function importFromCsv(string $filePath): bool;
}
