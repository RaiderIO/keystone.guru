<?php

namespace Tests\Feature\Traits;

use ReflectionClass;

trait LoadsJsonFiles
{
    private function getJsonData(string $fileName): array
    {
        $filePath = sprintf('%s/Fixtures/%s.json', $this->getImplementingClassDir(), $fileName);
        return json_decode(file_get_contents($filePath), true);
    }

    private function getImplementingClassDir(): string
    {
        $reflector = new ReflectionClass($this);
        return dirname($reflector->getFileName());
    }
}
