<?php

namespace App\Service\Wowhead\Dtos;

class LocalizedNpcName
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $locale
    ) {
    }
}
