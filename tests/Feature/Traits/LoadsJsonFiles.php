<?php

namespace Tests\Feature\Traits;

use ReflectionClass;

trait LoadsJsonFiles
{
    /**
     * @param array<string, mixed> $data
     */
    protected function writeJsonData(string $fileName, array $data, string $fromRootPath = ''): bool
    {
        $filePath = sprintf('%s/%sFixtures/%s.json', $this->getImplementingClassDir(), $fromRootPath, $fileName);

        return (bool)file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getJsonData(string $fileName, string $fromRootPath = ''): array
    {
        $filePath = sprintf('%s/%sFixtures/%s.json', $this->getImplementingClassDir(), $fromRootPath, $fileName);

        return json_decode(file_get_contents($filePath), true);
    }

    protected function hasJsonData(string $fileName, string $fromRootPath = ''): bool
    {
        $filePath = sprintf('%s/%sFixtures/%s.json', $this->getImplementingClassDir(), $fromRootPath, $fileName);

        return file_exists($filePath);
    }

    private function getImplementingClassDir(): string
    {
        $reflector = new ReflectionClass($this);

        return dirname($reflector->getFileName());
    }
}
