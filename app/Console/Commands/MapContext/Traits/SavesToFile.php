<?php

namespace App\Console\Commands\MapContext\Traits;

trait SavesToFile
{
    private ?string $version = null;

    protected function saveFileToMapContext(
        string $basePath,
        string $path,
        string $fileName,
        string $content,
    ): bool {
        $targetDir = sprintf(
            '%s/%s/mapcontext/%s',
            $basePath,
            $this->version ??= file_get_contents(base_path('version')),
            $path,
        );

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        return file_put_contents(
            sprintf('%s/%s', $targetDir, $fileName),
            $content,
        );
    }
}
