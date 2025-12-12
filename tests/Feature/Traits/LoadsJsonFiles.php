<?php

namespace Tests\Feature\Traits;

use ReflectionClass;

trait LoadsJsonFiles
{
    protected function writeJsonData(string $fileName, array $data, string $fromRootPath = ''): bool
    {
        $filePath = sprintf('%s/%sFixtures/%s.json', $this->getImplementingClassDir(), $fromRootPath, $fileName);

        return (bool)file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function getJsonData(string $fileName, string $fromRootPath = ''): array
    {
        $filePath = sprintf('%s/%sFixtures/%s.json', $this->getImplementingClassDir(), $fromRootPath, $fileName);

        return json_decode(file_get_contents($filePath), true);
    }

    private function getImplementingClassDir(): string
    {
        $reflector = new ReflectionClass($this);

        return dirname($reflector->getFileName());
    }
}
