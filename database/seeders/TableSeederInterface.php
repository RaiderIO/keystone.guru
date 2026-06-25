<?php

namespace Database\Seeders;

interface TableSeederInterface
{
    public function run(): void;

    /**
     * @return array<int, class-string>
     */
    public static function getAffectedModelClasses(): array;

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array;
}
