<?php

namespace App\Service\Image;

interface ImageServiceInterface
{
    public function convertToItemImage(string $filePath, string $targetFilePath): bool;
}
