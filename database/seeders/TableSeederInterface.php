<?php

namespace Database\Seeders;

interface TableSeederInterface
{
    public function run(): void;

    public static function getAffectedModelClasses(): array;

    public static function getAffectedEnvironments(): ?array;
}
