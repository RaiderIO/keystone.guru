<?php

namespace Database\Seeders;

interface TableSeederInterface
{
    public function run(): void;

    /**
     * @return array
     */
    public static function getAffectedModelClasses(): array;
}
